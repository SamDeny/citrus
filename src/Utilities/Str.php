<?php declare(strict_types=1);

namespace Citrus\Utilities;

class Str
{

    /**
     * Normalize End-of-Line Character.
     *
     * @param string $content
     * @param string $normalized
     * @return string
     */
    static public function normalizeEol(string $content, string $normalized = "\n"): string
    {
        $content = str_replace(["\r\n", "\n\r", "\r", "\n"], $normalized, $content);
        return $content;
    }

}