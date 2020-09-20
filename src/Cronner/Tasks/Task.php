<?php

declare(strict_types=1);

namespace Bileto\Cronner\Tasks;

use DateTime;
use Nette\Reflection\Method;
use Nette\SmartObject;
use ReflectionClass;
use Bileto\Cronner\ITimestampStorage;
use ReflectionException;

final class Task
{
    use SmartObject;

    /** @var object */
    private $object;

    /** @var Method */
    private $method;

    /** @var ITimestampStorage */
    private $timestampStorage;

    /** @var Parameters|null */
    private $parameters = null;

    /** @var DateTime|null */
    private $now = null;

    /**
     * Creates instance of one task.
     *
     * @param object $object
     * @param Method $method
     * @param ITimestampStorage $timestampStorage
     */
    public function __construct($object, Method $method, ITimestampStorage $timestampStorage, DateTime $now = null)
    {
        $this->object = $object;
        $this->method = $method;
        $this->timestampStorage = $timestampStorage;
        $this->setNow($now);
    }

    public function getObjectName(): string
    {
        return get_class($this->object);
    }

    public function getMethodReflection(): Method
    {
        return $this->method;
    }

    /**
     * @return string
     * @throws ReflectionException
     */
    public function getObjectPath(): string
    {
        $reflection = new ReflectionClass($this->object);

        return $reflection->getFileName();
    }

    /**
     * Returns True if given parameters should be run.
     * @param DateTime|null $now
     * @return bool
     */
    public function shouldBeRun(DateTime $now = null): bool
    {
        if ($now === null) {
            $now = new DateTime();
        }

        $parameters = $this->getParameters();
        if (!$parameters->isTask()) {
            return false;
        }
        $this->timestampStorage->setTaskName($parameters->getName());

        return $parameters->isInDay($now)
            && $parameters->isInTime($now)
            && $parameters->isNextPeriod($now, $this->timestampStorage->loadLastRunTime())
            && $parameters->isInDayOfMonth($now);
    }

    public function getName(): string
    {
        return $this->getParameters()->getName();
    }

    public function __invoke(DateTime $now): void
    {
        $this->method->invoke($this->object);
        $this->timestampStorage->setTaskName($this->getName());
        $this->timestampStorage->saveRunTime($now);
        $this->timestampStorage->setTaskName();
    }

    /**
     * Returns instance of parsed parameters.
     */
    private function getParameters(): Parameters
    {
        if ($this->parameters === null) {
            $this->parameters = new Parameters(Parameters::parseParameters($this->method, $this->getNow()));
        }

        return $this->parameters;
    }


    public function setNow(?DateTime $now): void
    {
        if ($now === null) {
            $now = new DateTime();
        }

        $this->now = $now;
    }

    public function getNow(): DateTime
    {
        if ($this->now === null) {
            $this->now = new DateTime();
        }
        return $this->now;
    }

}

