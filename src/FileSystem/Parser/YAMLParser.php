<?php declare(strict_types=1);

namespace Citrus\FileSystem\Parser;

use Citrus\Contracts\ParserInterface;
use Citrus\Exceptions\ParserException;
use Symfony\Component\Yaml\Yaml;

class YAMLParser implements ParserInterface
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
    static public function parse(string $content, array $attributes = []): null | array | object
    {
        return Yaml::parse($content, $attributes['flags'] ?? 0);
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

        return Yaml::parseFile($filepath, $attributes['flags'] ?? 0);
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

        return Yaml::parse($content, $attributes['flags'] ?? 0);
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
        return Yaml::dump($content, $attributes['inline'] ?? 2, $attributes['indent'] ?? 4, $attributes['flags'] ?? 0);
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

        $result = Yaml::dump($content, $attributes['inline'] ?? 2, $attributes['indent'] ?? 4, $attributes['flags'] ?? 0);
        file_put_contents($filepath, $result);
        return true;
    }
    
}
