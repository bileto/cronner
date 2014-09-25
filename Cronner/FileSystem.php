<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

/**
 * Contains only selected methods.
 */

namespace Nette\Utils;

use Nette;


/**
 * File system tool.
 *
 * @author     David Grudl
 */
class FileSystem
{

	/**
	 * Creates a directory.
	 *
	 * @return void
	 */
	public static function createDir($dir, $mode = 0777)
	{
		if (!is_dir($dir) && !@mkdir($dir, $mode, TRUE)) { // intentionally @; not atomic
			throw new Nette\IOException("Unable to create directory '$dir'.");
		}
	}



	/**
	 * Deletes a file or directory.
	 *
	 * @return void
	 */
	public static function delete($path)
	{
		if (is_file($path) || is_link($path)) {
			$func = DIRECTORY_SEPARATOR === '\\' && is_dir($path) ? 'rmdir' : 'unlink';
			if (!@$func($path)) {
				throw new Nette\IOException("Unable to delete '$path'.");
			}

		} elseif (is_dir($path)) {
			foreach (new \FilesystemIterator($path) as $item) {
				static::delete($item);
			}
			if (!@rmdir($path)) {
				throw new Nette\IOException("Unable to delete directory '$path'.");
			}
		}
	}

}
