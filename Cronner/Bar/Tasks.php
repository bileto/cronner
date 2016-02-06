<?php

namespace stekycz\Cronner\Bar;

use Nette\Object;
use stekycz\Cronner\Cronner;
use Tracy\IBarPanel;

class Tasks extends Object implements IBarPanel
{

	/**
	 * @var \stekycz\Cronner\Cronner
	 */
	protected $cronner;

	public function __construct(Cronner $cronner)
	{
		$this->cronner = $cronner;
	}

	public function getPanel()
	{
		$tasks = array();
		foreach ($this->cronner->getTasks() as $task) {
			if (!array_key_exists($task->getObjectName(), $tasks)) {
				$tasks[$task->getObjectName()] = array();
			}

			$tasks[$task->getObjectName()][] = $task;
		}
		ob_start();
		require __DIR__ . '/templates/panel.phtml';
		return ob_get_clean();
	}

	public function getTab()
	{
		ob_start();
		$count = $this->cronner->countTasks();
		require __DIR__ . '/templates/tab.phtml';
		return ob_get_clean();
	}

}
