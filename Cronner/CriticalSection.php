<?php

namespace stekycz\Cronner;

use Nette\Object;
use Nette\Utils\FileSystem;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class CriticalSection extends Object
{

	/**
	 * @var resource[]
	 */
	private $locks = array();

	/**
	 * @var string
	 */
	private $lockFilesDir;



	/**
	 * @param string $lockFilesDir
	 */
	public function __construct($lockFilesDir)
	{
		$lockFilesDir = rtrim($lockFilesDir, DIRECTORY_SEPARATOR);
		FileSystem::createDir($lockFilesDir);
		$this->lockFilesDir = $lockFilesDir;
	}



	/**
	 * Enters critical section.
	 *
	 * @param string $label
	 * @return bool
	 */
	public function enter($label)
	{
		if ($this->isEntered($label)) {
			return FALSE;
		}

		$handle = fopen($this->getFilePath($label), "w+b");
		if ($handle === FALSE) {
			return FALSE;
		}

		$locked = flock($handle, LOCK_EX | LOCK_NB);
		if ($locked === FALSE) {
			fclose($handle);
			return FALSE;
		}
		$this->locks[$label] = $handle;

		return TRUE;
	}



	/**
	 * Leaves critical section.
	 *
	 * @param string $label
	 * @return bool
	 */
	public function leave($label)
	{
		if (!$this->isEntered($label)) {
			return FALSE;
		}

		$unlocked = flock($this->locks[$label], LOCK_UN);
		if ($unlocked === FALSE) {
			return FALSE;
		}

		fclose($this->locks[$label]);
		unset($this->locks[$label]);

		return TRUE;
	}



	/**
	 * Returns TRUE if critical section is entered.
	 *
	 * @param string $label
	 * @return bool
	 */
	public function isEntered($label)
	{
		return array_key_exists($label, $this->locks) && $this->locks[$label] !== NULL;
	}



	/**
	 * @param string $label
	 * @return string
	 */
	private function getFilePath($label)
	{
		return $this->lockFilesDir . "/" . sha1($label);
	}

}
