<?php declare(strict_types=1);

namespace Citrus\Utilities;

class Arr
{

    /**
     * Recursively concatenate (sub-)arrays to a specified depth.
     *
     * @param array $array The target array to flatten.
     * @param integer $depth The depth level specifying how deep a nested array 
     *                structure should be flattened. -1 to reduce to a single
     *                array.
     * @return array
     */
    static public function flat(array $array, int $depth = -1): array
    {
        if ($depth < 0 || $depth > 0) {
            $s = static::class;
            return array_reduce(
                $array,
                fn($carry, $item) => $s::concat($carry, is_array($item)? $s::flat($item, $depth < 0? -1: $depth-1): $item),
                []
            );
        } else {
            return $array;
        }
    }

    /**
     * The concat() method is used to merge two or more arrays / datas.
     *
     * @param array $array The target array.
     * @param mixed ...$values The additional values. Compared to array_merge, 
     *              you can also pass non-array values.
     * @return array
     */
    static public function concat(array $array, ...$values)
    {
        $values = array_map(fn($item) => is_array($item)? $item: [$item], $values);
        return array_merge($array, ...$values);
    }

    /**
     * Find the first needle in the haystack.
     *
     * @param array $haystack
     * @param array $needles
     * @return mixed
     */
    static public function findFirst(array $haystack, array $needles): mixed
    {
        foreach ($haystack AS $value) {
            if (in_array($value, $needles)) {
                return $value;
            }
        }
        return null;
    }

}
