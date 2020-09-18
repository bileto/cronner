<?php

declare(strict_types=1);

namespace CronnerTests\DI;

require_once(__DIR__ . "/../bootstrap.php");

use Mockery;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\Statement;
use Bileto\CriticalSection\CriticalSection;
use Bileto\Cronner\Cronner;
use Bileto\Cronner\DI\CronnerExtension;
use Bileto\Cronner\TimestampStorage\DummyStorage;
use Bileto\Cronner\TimestampStorage\FileStorage;
use Tester\Assert;
use Tester\Helpers;
use Tester\TestCase;

class CronnerExtensionTest extends TestCase
{

    /** @var Compiler */
    private $compiler;

    protected function setUp()
    {
        $this->compiler = new Compiler();
        $this->compiler->addConfig([
            'parameters' => [
                'appDir' => __DIR__ . '/../..',
                'wwwDir' => __DIR__ . '/../..',
                'tempDir' => TEMP_DIR,
                'debugMode' => FALSE,
                'productionMode' => TRUE,
            ],
        ]);
        $this->compiler->addExtension('cronner', new CronnerExtension());
    }

    protected function tearDown()
    {
        Mockery::close();
    }

    public function testDefaultConfiguration()
    {
        $compiler = $this->compiler;
        $compiler->compile();

        $timestampStorage = $compiler->getContainerBuilder()->getDefinition('cronner.timestampStorage');
        $criticalSection = $compiler->getContainerBuilder()->getDefinition('cronner.criticalSection');
        $runner = $compiler->getContainerBuilder()->getDefinition('cronner.runner');

        Assert::same(FileStorage::class, $timestampStorage->getClass());
        Assert::same(CriticalSection::class, $criticalSection->getClass());
        Assert::same(Cronner::class, $runner->getClass());
    }

    public function testCompleteConfiguration()
    {
        $compiler = $this->compiler;
        $compiler->addConfig([
            'cronner' => [
                'timestampStorage' => new Statement(DummyStorage::class),
                'maxExecutionTime' => 120,
                'criticalSectionTempDir' => '%tempDir%/cronner',
            ],
        ]);
        $compiler->compile();

        $timestampStorage = $compiler->getContainerBuilder()->getDefinition('cronner.timestampStorage');
        $criticalSection = $compiler->getContainerBuilder()->getDefinition('cronner.criticalSection');
        $runner = $compiler->getContainerBuilder()->getDefinition('cronner.runner');

        Assert::same(DummyStorage::class, $timestampStorage->getClass());
        Assert::same(CriticalSection::class, $criticalSection->getClass());
        Assert::same(Cronner::class, $runner->getClass());
    }

    public function testRegisterTasks()
    {
        Helpers::purge(__DIR__ . '/../../tmp/');

        $config = new Configurator();
        $config->setTempDirectory(__DIR__ . '/../../tmp/');
        $config->addConfig(__DIR__ . '/../config/config.neon');
        $container = $config->createContainer();

        $cronner = $container->getByType('Bileto\Cronner\Cronner');

        Assert::same(2, count($cronner->getTasks()));
    }

}

(new CronnerExtensionTest())->run();
