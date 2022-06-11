<?php declare(strict_types=1);

namespace Citrus\FileSystem\Parser;

use Citrus\Contracts\ParserInterface;
use Citrus\Exceptions\ParserException;

class JSONParser implements ParserInterface
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
        $associative = array_key_exists('associative', $attributes)? $attributes['associative']: true;
        $depth = $attributes['depth'] ?? 512;
        $flags = $attributes['flags'] ?? \JSON_OBJECT_AS_ARRAY | \JSON_THROW_ON_ERROR;

        return json_decode($content, $associative, $depth, $flags);
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
        $content = file_get_contents($filepath);

        $associative = array_key_exists('associative', $attributes)? $attributes['associative']: true;
        $depth = $attributes['depth'] ?? 512;
        $flags = $attributes['flags'] ?? \JSON_OBJECT_AS_ARRAY | \JSON_THROW_ON_ERROR;

        return json_decode($content, $associative, $depth, $flags);
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

        $associative = array_key_exists('associative', $attributes)? $attributes['associative']: true;
        $depth = $attributes['depth'] ?? 512;
        $flags = $attributes['flags'] ?? \JSON_OBJECT_AS_ARRAY | \JSON_THROW_ON_ERROR;

        return json_decode($content, $associative, $depth, $flags);
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

        $depth = $attributes['depth'] ?? 512;
        $flags = $attributes['flags'] ?? \JSON_THROW_ON_ERROR;
        return json_encode($content, $flags, $depth);
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
        $depth = $attributes['depth'] ?? 512;
        $flags = $attributes['flags'] ?? \JSON_THROW_ON_ERROR;
        $result = json_encode($content, $flags, $depth);

        file_put_contents($filepath, $result);
        return true;
    }
    
}
