<?php declare(strict_types=1);


if (!function_exists('array_is_list')) {
    /**
     * Checks whether a given array is a list
     *
     * @param array $array The array being evaluated. 
     * @return boolean Returns true if array is a list, false otherwise. 
     */
    function array_is_list(array $array): bool
    {
        $i = 0;
        foreach ($array AS $key => $val) {
            if ($key !== $i++) {
                return false;
            }
        }
        return true;
    }
}
