<?php

namespace ArturDoruch\ArrayUtil;

use ArturDoruch\StringUtil\StringCaseConverter;

/**
 * @author Artur Doruch <arturdoruch@interia.pl>
 */
class ArrayUtils
{
    /**
     * @var callable[]
     */
    private static $caseConvertFunctions;

    /**
     * Whether an array is indexed.
     *
     * @param array $array
     *
     * @return bool When array is indexed or is empty.
     */
    public static function isIndexed(array $array): bool
    {
        if (!$totalItems = count($array)) {
            return true;
        }

        return array_keys($array) === range(0, $totalItems - 1);
    }

    /**
     * Checks whether the multi-dimensional array has keys path.
     *
     * @param array $array The multi-dimensional array.
     * @param array $keys The keys path. Keys must be a string or numeric value.
     *
     * @return bool
     */
    public static function keyExists(array $array, array $keys): bool
    {
        if (!$keys) {
            throw new \InvalidArgumentException('The $keys argument cannot be empty.');
        }

        static $null;
        if (!$null) {
            $null = uniqid(mt_rand(), true);
        }

        return self::find($array, $keys, $null) !== $null;
    }

    /**
     * Checks if a single-dimensional array contains a value.
     *
     * @param array $array A single-dimensional array.
     * @param mixed $search Any type of value to check.
     * @param bool $strict Whether to compare strings case-sensitively and type of the numeric values.
     *
     * @return bool
     */
    public static function contains(array $array, $search, bool $strict = false): bool
    {
        $searchType = gettype($search);
        $searchNumeric = is_numeric($search);

        foreach ($array as $key => $value) {
            if ($searchNumeric) {
                if ($value === $search || $value == $search && !$strict) {
                    return true;
                }
            } elseif (is_string($value) ) {
                if ($searchType === 'string' && (!$strict ? mb_strtolower($search) === mb_strtolower($value) : strcmp($search, $value) === 0)) {
                    return true;
                }
            } elseif ($value === $search) {
                return true;
            }
        }

        return false;
    }

    /**
     * Finds a value in multi-dimensional array by array keys path.
     *
     * @param array $array
     * @param array $keys The keys path of the nested arrays for which to get the value.
     *                    Keys must be a string or numeric value.
     * @param mixed $default The default value to return when array do not have the keys path.
     *
     * @return array|mixed The value of the last key from the keys path, or default value when keys path
     *                     does not exist, or specified $array when the $keys argument array is empty.
     */
    public static function find(array $array, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (is_array($array) && array_key_exists($key, $array)) {
                $array = $array[$key];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Checks if the multi-dimensional arrays are equal, contains the same keys and values.
     * NOTE: Order of the array keys and values of indexed array is ignored.
     *
     * @param array $array1
     * @param array $array2
     * @param bool $strict Whether to compare value types and strings case-sensitively.
     *
     * @return bool True when compared arrays are the same.
     */
    public static function equals(array $array1, array $array2, bool $strict = true): bool
    {
        self::keySort($array1);
        self::keySort($array2);
        self::formatValues($array1, $strict);
        self::formatValues($array2, $strict);

        return $strict ? $array1 === $array2 : $array1 == $array2;
    }


    private static function formatValues(array &$array, $strict)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                if (self::isIndexed($value)) {
                    sort($value);
                }

                self::formatValues($value, $strict);
            /*} elseif (is_numeric($value)) {
                if (!$strict) {
                    $value = (string) $value;
                }*/
            } elseif (is_string($value) && !$strict) {
                $value = mb_strtolower($value);
            }
        }
    }

    /**
     * Sorts arrays in multi-dimensional array by keys.
     *
     * @param array $array
     * @param int $flags
     */
    public static function keySort(array &$array, $flags = SORT_NATURAL | SORT_FLAG_CASE)
    {
        ksort($array, $flags);

        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                self::keySort($value);
            }
        }
    }

    /**
     * Inserts items into indexed array at specific position.
     *
     * @param array $array An indexed array.
     * @param array $items The items to insert.
     * @param int $position The index (starting at 0) of the array in which to insert the items.
     *                      If it is negative then position will be counting from the end of the input array.
     */
    public static function insert(array &$array, array $items, int $position)
    {
        array_splice($array, $position, 0, $items);
    }

    /**
     * Concatenates string and numeric values with the same index in all specified arrays into one string.
     *
     * @param array|string $separators The value separators. An array or a string if the same separator should
     *                                 be used for all concatenations. The length of the array should be one less
     *                                 than the first array of the $arrays.
     * @param array ...$arrays Indexed arrays with the values to concat. All arrays should have the same length.
     *
     * @return string[]
     * @throws \InvalidArgumentException
     */
    public static function concatStrings($separators, ...$arrays): array
    {
        if (2 > $totalArrays = func_num_args() - 1) {
            throw new \InvalidArgumentException('Missing $arrays with values to concatenate. At least two arrays must be specified.');
        }

        $expectedTotalSeparators = $totalArrays - 1;

        if (is_string($separators)) {
            $separators = array_pad([], $expectedTotalSeparators, $separators);
        } elseif (($totalSeparators = count($separators)) < $expectedTotalSeparators) {
            throw new \InvalidArgumentException(sprintf(
                'Missing %d value separator%s of %d expected.',
                $missingTotalSeps = $expectedTotalSeparators - $totalSeparators, ($missingTotalSeps > 1 ? 's' : ''), $expectedTotalSeparators
            ));
        }

        $totalItems = count($arrays[0]);
        $strings = [];

        for ($i = 0; $i < $totalItems; $i++) {
            $string = '';

            foreach ($arrays as $index => $array) {
                if (!array_key_exists($i, $array)) {
                    throw new \InvalidArgumentException(sprintf('Missing index #%d in array #%d.', $i, $index+1));
                }

                $value = $array[$i];

                if ($value !== null && !is_string($value) && !is_numeric($value) && !(is_object($value) && method_exists($value, '__toString'))) {
                    throw new \InvalidArgumentException(sprintf(
                        'Invalid type "%s" of value of index #%d in array #%d. Allowed types are: '.
                        'scalar, numeric, null or an object with __toString() method.', gettype($value), $i, $index+1
                    ));
                }

                $string .= $value;

                if ($index < $expectedTotalSeparators) {
                    $string .= $separators[$index];
                }
            }

            $strings[] = $string;
        }

        return $strings;
    }

    /**
     * Converts multi-dimensional array into an object.
     * NOTE: Only array keys with type of string and not empty are converted to object property.
     *
     * @param array $array The multi-dimensional array.
     * @param bool $recursive Whether to convert to an object nested arrays.
     * @param string|null $propertyCase Case of an object property name. One of the values: "camel", "snake" or null
     *                                  when array keys should be used as is.
     *
     * @return \stdClass
     */
    public static function toObject(array $array, bool $recursive = true, ?string $propertyCase = 'camel'): \stdClass
    {
        if ($propertyCase && !in_array($propertyCase, ['camel', 'snake'])) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid property case "%s". Permissible values are: "camel", "snake" or null.', $propertyCase)
            );
        }

        $caseConvert = self::getCaseConvertFunction($propertyCase);
        $object = new \stdClass();

        foreach ($array as $key => $value) {
            if ($key && is_string($key)) {
                if (is_array($value) && $recursive === true && !self::isIndexed($value)) {
                    $value = self::toObject($value, $recursive, $propertyCase);
                }

                $object->{$caseConvert($key)} = $value;
            }
        }

        return $object;
    }

    /**
     * Flattens multi-dimensional array.
     *
     * @param array $array
     * @param bool $preserveKeys Whether to leave the arrays current keys. If true values with the same key are overridden.
     *
     * @return array A single-dimensional indexed or associative (when $preserveKeys is true) array.
     */
    public static function flatten(array $array, bool $preserveKeys = false): array
    {
        return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array)), $preserveKeys);
    }

    /**
     * Recursively merges the values of two multi-dimensional arrays.
     *
     * Example:
     *      $baseArray =   ['key' => 'value', 'key2' => 'value 2'];
     *      $mergedArray = ['key' => 'new value']
     *
     *      return:        ['key' => 'new value', 'key2' => 'value 2']
     *
     * @param array $baseArray
     * @param array $mergedArray An array to merge.
     * @param bool $mergeIndexedArrays Whether to merge values of the indexed arrays.
     *
     * @return array The merged arrays.
     */
    public static function mergeDistinct(array $baseArray, array $mergedArray, bool $mergeIndexedArrays = false): array
    {
        $merged = $baseArray;

        foreach ($mergedArray as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::mergeDistinct($merged[$key], $value, $mergeIndexedArrays);
            } elseif (!is_array($value) && $mergeIndexedArrays && self::isIndexed($merged)) {
                $merged[] = $value;
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    private static function getCaseConvertFunction($case): callable
    {
        if (!isset(self::$caseConvertFunctions[$case])) {
            if (!$case) {
                $function = function ($key) { return $key; };
            } else {
                if (!class_exists(StringCaseConverter::class)) {
                    throw new \LogicException('To convert case of an array stringable keys install the "arturdoruch/string" package.');
                }

                if ($case === 'camel') {
                    $function = function ($key) { return StringCaseConverter::toCamel($key); };
                } elseif ($case === 'snake') {
                    $function = function ($key) { return StringCaseConverter::toSnake($key); };
                }
            }

            self::$caseConvertFunctions[$case] = $function;
        }

        return self::$caseConvertFunctions[$case];
    }
}
