<?php

declare(strict_types=1);

namespace Bileto\Cronner\Utils;

use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;
use ReflectionMethod;

class ReflectionSupport
{
	/** @internal single & double quoted PHP string */
	private const RE_STRING = '\'(?:\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*"';

	/** @internal identifier */
	private const RE_IDENTIFIER = '[_a-zA-Z\x7F-\xFF][_a-zA-Z0-9\x7F-\xFF-\\\]*';

	/**
	 * @return mixed|null
	 */
	public function getMethodAnnotation(ReflectionMethod $method, string $name)
	{
		$methodDoc = self::parseDocComment($method->getDocComment());

		return (array_key_exists($name, $methodDoc) && count($methodDoc[$name]) > 0) ? $methodDoc[$name][0] : null;
	}

	public function hasMethodAnnotation(ReflectionMethod $method, string $name): bool
	{
		$methodDoc = self::parseDocComment($method->getDocComment());

		return array_key_exists($name, $methodDoc);
	}

	/**
	 * @return array<mixed>
	 */
	private function parseDocComment(string $docComment): array
	{
		static $tokens = ['true' => true, 'false' => false, 'null' => null, '' => true];

		$res = [];
		$docComment = preg_replace('#^\s*\*\s?#ms', '', trim($docComment, '/*'));
		$parts = preg_split('#^\s*(?=@' . self::RE_IDENTIFIER . ')#m', $docComment, 2);

		$description = trim($parts[0]);
		if ($description !== '') {
			$res['description'] = [$description];
		}

		$matches = Strings::matchAll(
			isset($parts[1]) ? $parts[1] : '',
			'~
				(?<=\s|^)@(' . self::RE_IDENTIFIER . ')[ \t]*      ##  annotation
				(
					\((?>' . self::RE_STRING . '|[^\'")@]+)+\)|  ##  (value)
					[^(@\r\n][^@\r\n]*|)                     ##  value
			~xi'
		);

		foreach ($matches as $match) {
			list(, $name, $value) = $match;

			if (substr($value, 0, 1) === '(') {
				$items = [];
				$key = '';
				$val = true;
				$value[0] = ',';
				while ($m = Strings::match(
					$value,
					'#\s*,\s*(?>(' . self::RE_IDENTIFIER . ')\s*=\s*)?(' . self::RE_STRING . '|[^\'"),\s][^\'"),]*)#A')
				) {
					$value = substr($value, strlen($m[0]));
					list(, $key, $val) = $m;
					$val = rtrim($val);
					if ($val[0] === "'" || $val[0] === '"') {
						$val = substr($val, 1, -1);

					} elseif (is_numeric($val)) {
						$val = 1 * $val;

					} else {
						$lval = strtolower($val);
						$val = array_key_exists($lval, $tokens) ? $tokens[$lval] : $val;
					}

					if ($key === '') {
						$items[] = $val;

					} else {
						$items[$key] = $val;
					}
				}

				$value = count($items) < 2 && $key === '' ? $val : $items;

			} else {
				$value = trim($value);
				if (is_numeric($value)) {
					$value = 1 * $value;

				} else {
					$lval = strtolower($value);
					$value = array_key_exists($lval, $tokens) ? $tokens[$lval] : $value;
				}
			}

			$res[$name][] = is_array($value) ? ArrayHash::from($value) : $value;
		}

		return $res;
	}
}