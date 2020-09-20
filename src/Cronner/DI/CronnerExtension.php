<?php

declare(strict_types=1);

namespace Bileto\Cronner\DI;

use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\ServiceDefinition;
use Nette\DI\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Json;
use Nette\Utils\Validators;
use Bileto\CriticalSection\CriticalSection;
use Bileto\CriticalSection\Driver\FileDriver;
use Bileto\CriticalSection\Driver\IDriver;
use Bileto\Cronner\Bar\Tasks;
use Bileto\Cronner\Cronner;
use Bileto\Cronner\ITimestampStorage;
use Bileto\Cronner\TimestampStorage\FileStorage;

class CronnerExtension extends CompilerExtension
{

    const TASKS_TAG = 'cronner.tasks';

    const DEFAULT_STORAGE_CLASS = FileStorage::class;
    const DEFAULT_STORAGE_DIRECTORY = '%tempDir%/cronner';

    /** @var array<mixed> */
    public $defaults = [
        'timestampStorage' => null,
        'maxExecutionTime' => null,
        'criticalSectionTempDir' => "%tempDir%/critical-section",
        'criticalSectionDriver' => null,
        'tasks' => [],
        'bar' => '%debugMode%',
    ];

    public function loadConfiguration()
    {
        $container = $this->getContainerBuilder();

        $config = $this->getConfig($this->defaults);
        Validators::assert($config['timestampStorage'], 'string|object|null', 'Timestamp storage definition');
        Validators::assert($config['maxExecutionTime'], 'integer|null', 'Script max execution time');
        Validators::assert($config['criticalSectionTempDir'], 'string|null', 'Critical section files directory path (for critical section files driver only)');
        Validators::assert($config['criticalSectionDriver'], 'string|object|null', 'Critical section driver definition');

        $storage = $this->createServiceByConfig(
            $container,
            $this->prefix('timestampStorage'),
            $config['timestampStorage'],
            ITimestampStorage::class,
            self::DEFAULT_STORAGE_CLASS,
            [
                self::DEFAULT_STORAGE_DIRECTORY,
            ]
        );

        $criticalSectionDriver = $this->createServiceByConfig(
            $container,
            $this->prefix('criticalSectionDriver'),
            $config['criticalSectionDriver'],
            IDriver::class,
            FileDriver::class,
            [
                $config['criticalSectionTempDir'],
            ]
        );

        $criticalSection = $container->addDefinition($this->prefix("criticalSection"))
            ->setClass(CriticalSection::class, [
                $criticalSectionDriver,
            ])
            ->setAutowired(false)
            ->setInject(false);

        $runner = $container->addDefinition($this->prefix('runner'))
            ->setClass(Cronner::class, [
                $storage,
                $criticalSection,
                $config['maxExecutionTime'],
                array_key_exists('debugMode', $config) ? !$config['debugMode'] : true,
            ]);

        Validators::assert($config['tasks'], 'array');
        foreach ($config['tasks'] as $task) {
            $def = $container->addDefinition($this->prefix('task.' . md5(is_string($task) ? $task : sprintf('%s-%s', $task->getEntity(), Json::encode($task)))));
            list($def->factory) = Compiler::filterArguments([
                is_string($task) ? new Statement($task) : $task,
            ]);

            if (class_exists($def->factory->entity)) {
                $def->setClass($def->factory->entity);
            }

            $def->setAutowired(false);
            $def->setInject(false);
            $def->addTag(static::TASKS_TAG);
        }

        if ($config['bar'] && class_exists('Tracy\Bar')) {
            $container->addDefinition($this->prefix('bar'))
                ->setClass(Tasks::class, [
                    $this->prefix('@runner'),
                    $this->prefix('@timestampStorage'),
                ]);
        }
    }

    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();

        $runner = $builder->getDefinition($this->prefix('runner'));
        foreach (array_keys($builder->findByTag(static::TASKS_TAG)) as $serviceName) {
            $runner->addSetup('addTasks', ['@' . $serviceName]);
        }
    }

    public function afterCompile(ClassType $class)
    {
        $builder = $this->getContainerBuilder();
        $init = $class->getMethod('initialize');

        if ($builder->hasDefinition($this->prefix('bar'))) {
            $init->addBody('$this->getByType(?)->addPanel($this->getService(?));', [
                'Tracy\Bar',
                $this->prefix('bar'),
            ]);
        }
    }

    public static function register(Configurator $configurator): void
    {
        $configurator->onCompile[] = function (Configurator $config, Compiler $compiler) {
            $compiler->addExtension('cronner', new CronnerExtension());
        };
    }

    /**
     * @param ContainerBuilder $container
     * @param string $serviceName
     * @param mixed $config
     * @param string $fallbackType
     * @param string $fallbackClass
     * @param array<mixed> $fallbackArguments
     * @return ServiceDefinition
     */
    private function createServiceByConfig(
        ContainerBuilder $container,
        string $serviceName,
        $config,
        string $fallbackType,
        string $fallbackClass,
        array $fallbackArguments
    ): ServiceDefinition
    {
        if (is_string($config) && $container->getServiceName($config)) {
            $definition = $container->addDefinition($serviceName)
                ->setFactory($config);
        } elseif ($config instanceof Statement) {
            $definition = $container->addDefinition($serviceName)
                ->setClass($config->entity, $config->arguments);
        } else {
            $foundServiceName = $container->getByType($fallbackType);
            if ($foundServiceName) {
                $definition = $container->addDefinition($serviceName)
                    ->setFactory('@' . $foundServiceName);
            } else {
                $definition = $container->addDefinition($serviceName)
                    ->setClass($fallbackClass, $container->expand($fallbackArguments));
            }
        }

        return $definition
            ->setAutowired(false)
            ->setInject(false);
    }

}
