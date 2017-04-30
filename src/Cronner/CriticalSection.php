<?php

declare(strict_types=1);

namespace stekycz\Cronner;

use Nette\Object;
use Nette\Utils\FileSystem;
use stekycz\CriticalSection\ICriticalSection;

/**
 * @deprecated use stekycz\CriticalSection\CriticalSection
 */
class CriticalSection extends Object implements ICriticalSection
{

	/**
	 * @var resource[]
	 */
	private $locks = array();

	/**
	 * @var string
	 */
	private $lockFilesDir;

	public function __construct(string $lockFilesDir)
	{
		$className = self::class;
		$newClass = \stekycz\CriticalSection\CriticalSection::class;
		trigger_error("Class {$className} is deprecated, use {$newClass}", E_USER_DEPRECATED);

		$lockFilesDir = rtrim($lockFilesDir, DIRECTORY_SEPARATOR);
		FileSystem::createDir($lockFilesDir);
		$this->lockFilesDir = $lockFilesDir;
	}

	/**
	 * Enters critical section.
	 */
	public function enter(string $label) : bool
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
	 */
	public function leave(string $label) : bool
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
	 */
	public function isEntered(string $label) : bool
	{
		return array_key_exists($label, $this->locks) && $this->locks[$label] !== NULL;
	}

	private function getFilePath(string $label) : string
	{
		return $this->lockFilesDir . "/" . sha1($label);
	}

}
