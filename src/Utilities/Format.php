<?php declare(strict_types=1);

namespace Citrus\Utilities;

class Format
{

    /**
     * Format File Size / Bytes
     * @link https://stackoverflow.com/a/2510459/4657432
     *
     * @param integer $bytes
     * @param integer $precision
     * @return string
     */
    static public function bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];

        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 

        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];  
    }

}
