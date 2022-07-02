<?php declare(strict_types=1);

namespace Citrus\Utilities;

class Str
{
    
    /**
     * Titlecase A String
     *
     * @param string $string
     * @param ?string $charset
     * @return string
     */
    static public function titlecase(string $string, ?string $charset = 'UTF-8'): string
    {
        return mb_convert_case($string, \MB_CASE_TITLE, $charset);
    }

    /**
     * lowercase a string
     *
     * @param string $string
     * @param ?string $charset
     * @return string
     */
    static public function lowercase(string $string, ?string $charset = 'UTF-8'): string
    {
        return mb_strtolower($string, $charset);
    }

    /**
     * UPPERCASE as string
     *
     * @param string $string
     * @param ?string $charset
     * @return string
     */
    static public function uppercase(string $string, ?string $charset = 'UTF-8'): string
    {
        return mb_strtoupper($string, $charset);
    }
    
    /**
     * camelCase a string
     *
     * @param string $string
     * @param string $charset
     * @return string
     */
    static public function camelcase(string $string, string $charset = 'UTF-8'): string
    {
        $string = strtr($string, ['_' => ' ', '-' => ' ']);
        $string = str_replace(' ', '', mb_convert_case($string, MB_CASE_TITLE, $charset));
        return mb_strtolower(mb_substr($string, 0, 1, $charset), $charset) + mb_substr($string, 1, null, $charset);
    }
    
    /**
     * PascalCase a string
     *
     * @param string $string
     * @param string $charset
     * @return string
     */
    static public function pascalcase(string $string, string $charset = 'UTF-8'): string
    {
        $string = strtr($string, ['_' => ' ', '-' => ' ']);
        $string = str_replace(' ', '', mb_convert_case($string, MB_CASE_TITLE, $charset));
        return $string;
    }
    
    /**
     * snake_case a string
     *
     * @param string $string
     * @param string $charset
     * @return string
     */
    static public function snakecase(string $string, string $charset = 'UTF-8'): string
    {
        $string = strtr($string, [' ' => '_', '-' => '_']);
        $string = preg_replace('/(?<=\\w)([A-Z])/u', '_$1', $string);
        return str_replace('__', '_', mb_strtolower($string, $charset));
    }
    
    /**
     * kebab-case a string
     *
     * @param string $string
     * @param string $charset
     * @param string $mode Additional kebeb-case mode, supports:
     *               'lowercase'    ex.: kebab-case
     *               'uppercase'    ex.: KEBAB-CASE
     *               'camelcase'    ex.: kebab-Case
     *               'pascalcase'   ex.: Kebab-Case
     * @return string
     */
    static public function kebabcase(string $string, string $charset = 'UTF-8', string $mode = 'lowercase'): string
    {
        $string = strtr($string, [' ' => '-', '_' => '-']);
        $string = preg_replace('/(?<=\\w)([A-Z])/u', '-$1', $string);
        return str_replace('--', '-', mb_strtolower($string, $charset));
    }

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

    /**
     * Return a random UUID
     *
     * @return void
     */
    static public function uuid()
    {
        return uuid_create(\UUID_TYPE_RANDOM);
    }

    /**
     * Return a random UUIDv4
     *
     * @return void
     */
    static public function uuidv4()
    {
        return uuid_create(\UUID_TYPE_TIME);
    }

}
