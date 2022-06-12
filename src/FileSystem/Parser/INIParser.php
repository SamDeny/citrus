<?php declare(strict_types=1);

namespace Citrus\FileSystem\Parser;

use Citrus\Contracts\ParserContract;
use Citrus\Exceptions\ParserException;

class INIParser implements ParserContract
{

    /**
     * Parse the passed raw data and return the content as an array, object or
     * null when empty. Throw an [Parser]Exception when something went wrong.
     *
     * @param string $content
     * @param array $attributes Additional attributes for the underlying 
     *              Parser engine.
     * @return null|array|object
     */
    static public function parse(string $content, array $attributes = []): ?object
    {
        $sections = $attributes['process_sections'] ?? true;
        $scannerMode = $attributes['scanner_mode'] ?? \INI_SCANNER_TYPED;

        if (($content = parse_ini_string($content, $sections, $scannerMode)) === false) {
            return null;
        } else {
            return $content;
        }
    }

    /**
     * Parse the filepath content and return the content as an array, object or
     * null when empty. Throw an [Parser]Exception when something went wrong.
     *
     * @param string $filepath
     * @param array $attributes Additional attributes for the underlying 
     *              Parser engine.
     * @return null|array|object
     */
    static public function parseFile(string $filepath, array $attributes = []): null | array | object
    {
        if (!file_exists($filepath) || !is_file($filepath)) {
            throw new ParserException('The passed filepath does not exist.', [
                'parser' => self::class,
                'filepath' => $filepath,
                'attributes' => $attributes
            ]);
        }

        $sections = $attributes['process_sections'] ?? true;
        $scannerMode = $attributes['scanner_mode'] ?? \INI_SCANNER_TYPED;

        if (($content = parse_ini_file($filepath, $sections, $scannerMode)) === false) {
            return null;
        } else {
            return $content;
        }
    }

    /**
     * Parse the stream content and return the content as an array, object or
     * null when empty. Throw an [Parser]Exception when something went wrong.
     *
     * @param string $resource
     * @param array $attributes Additional attributes for the underlying 
     *              Parser engine.
     * @return null|array|object
     */
    static public function parseStream($resource, array $attributes = []): null | array | object
    {
        if (stream_get_meta_data($resource)['seekable']) {
            fseek($resource, 0);
        }

        $content = '';
        while (!feof($resource)) {
            $content .= fread($resource, 8192);
        }
        fclose($resource);

        $sections = $attributes['process_sections'] ?? true;
        $scannerMode = $attributes['scanner_mode'] ?? \INI_SCANNER_TYPED;
        if (($content = parse_ini_string($content, $sections, $scannerMode)) === false) {
            return null;
        } else {
            return $content;
        }
    }

    /**
     * Return the respective representation of the passed content or null when
     * empty- Throw an [Parser]Exception when something went wrong.
     *
     * @param mixed $content
     * @param array $attributes Additional attributes for the Parser engine.
     * @return null|string
     */
    static public function emit(mixed $content, array $attributes = [])
    {
        if (!is_iterable($content) || empty($content)) {
            return null;
        }
        return self::encode($content);
    }

    /**
     * Send the respective representation to the passed filepath, and return 
     * a boolean status. Throw an [Parser]Exception when something went wrong.
     *
     * @param mixed $content
     * @param string $filepath
     * @param array $attributes Additional attributes for the Parser engine.
     * @return boolean
     */
    static public function emitFile(mixed $content, string $filepath, array $attributes = [])
    {
        if (!is_iterable($content) || empty($content)) {
            return false;
        }
        $result = self::encode($content);

        file_put_contents($filepath, $result);
        return true;
    }

    /**
     * Stringify different value types.
     *
     * @param mixed $value
     * @return mixed
     */
    static protected function stringify(mixed $value): mixed
    {
        if ($value === true) {
            return 'true';
        } else if ($value === false) {
            return 'false';
        } else if ($value === null) {
            return 'null';
        } else {
            if (!is_numeric($value) && !is_string($value)) {
                try {
                    $temp = strval($value);
                } catch(\Exception $e) {
                    return '';
                }
                return $temp;
            } else {
                return $value;
            }
        }
    }

    /**
     * Encode iterable to valid INI format.
     *
     * @param iterable $iterable
     * @return string
     */
    static protected function encode(iterable $iterable): string
    {
        $result = '';

        foreach ($iterable AS $key => $value) {
            if (is_iterable($value)) {
                $result .= "[$key]" . PHP_EOL;
                $result .= self::encode($value);
            } else {
                $result .= "$key = " . self::stringify($value) . PHP_EOL;
            }
        }

        return $result;
    }

}
