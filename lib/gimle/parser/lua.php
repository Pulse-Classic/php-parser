<?php
declare(strict_types=1);
namespace gimle\parser;

class Lua
{
	/**
	 * Parses a .lua file.
	 *
	 * @param string $filename The location of the file to parse.
	 *
	 * @return array
	 */
	public static function parse (string $filename): array
	{
		$builder = [['', []]];
		$depth = 0;

		$f = fopen($filename, 'r');
		if (flock($f, LOCK_SH)) {
			clearstatcache(true, $filename);
			while (!feof($f)) {
				$line = trim((string) fgets($f));
				if ($line === '') {
					continue;
				}
				if (substr($line, 0, 1) === '}') {
					$temp = $builder[$depth];
					unset($builder[$depth]);
					$depth--;
					$builder[$depth][1][$temp[0]] = $temp[1];
					continue;
				}

				if (strpos($line, '=') !== false) {
					list($key, $value) = explode('=', $line);
					$key = rtrim($key);
					$value = trim($value, ', ');
					if (substr($key, 0, 1) === '[') {
						$key = trim($key, '[]"');
					}
				}
				else {
					if (empty($builder[$depth][1])) {
						$key = 1;
					}
					else {
						$key = max(array_keys($builder[$depth][1]));
						if (is_int($key)) {
							$key++;
						}
						else {
							$key = 0;
						}
					}

					$value = $line;
					if (strpos($value, '-- [') !== false) {
						$value = substr($value, 0, strrpos($value, '-- ['));
					}
					$value = trim($value, ', ');
				}

				if (substr($value, 0, 1) === '{') {
					$depth++;
					$builder[$depth] = [$key, []];
					continue;
				}

				if (substr($value, 0, 1) === '"') {
					$value = substr($value, 1, -1);
				}
				elseif ($value === 'true') {
					$value = true;
				}
				elseif ($value === 'false') {
					$value = false;
				}
				elseif ($value === 'null') {
					$value = null;
				}
				elseif ($value === 'nil') {
					$value = null;
				}
				elseif (ctype_digit($value)) {
					$value = (int) $value;
				}
				elseif ((substr($value, 0, 1) === '-') && (ctype_digit(substr($value, 1)))) {
					$value = (int) $value;
				}
				elseif (is_numeric($value)) {
					$value = (float) $value;
				}
				else {
					throw new \Exception('Unknown value: ' . $value);
				}

				$builder[$depth][1][$key] = $value;
			}
		}

		fclose($f);

		return $builder[0][1];
	}
}
