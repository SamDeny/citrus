<?php declare(strict_types=1);

namespace Citrus\FileSystem\Parser;

use Citrus\Contracts\ParserContract;
use Citrus\Exceptions\ParserException;

class PHPParser implements ParserContract
{

    /**
     * Parse the passed raw data and return the content as an array, object or
     * null when empty. Throw an [Parser]Exception when something went wrong.
     *
     * @param string $content
     * @param array $attributes Requires at least one hash value (see below) to 
     *              identify that the passed $content has not been modified after
     *              you initially stringified it. This is required for security 
     *              reasons, since this function uses eval() to turn the string 
     *              back to valid PHP code, which is not a really good idea tbh.
     *              You can also throw away every security aspect and pass 
     *              'force' => true... in this case... I hope god doesn't take 
     *              a look in this moment.
     * @return null|array|object
     */
    static public function parse(string $content, array $attributes = []): null | array | object
    {
        $algos = ['sha512', 'sha384', 'sha256', 'sha1', 'md5'];

        foreach ($algos AS $algo) {
            if (isset($attributes[$algo])) {
                if (hash_equals($attributes[$algo], hash($algo, $content))) {
                    return eval($content);
                } else {
                    return null;
                }
            }
        }

        if (($attributes['force'] ?? false) === true) {
            return eval($content);  // <!-- It's your fault! 
        } else {
            return null;
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
     * @throws ParserException The passed filepath is invalid or not a file.
     * @throws ParserException The passed filepath is not a PHP file or does not end with .php at least
     * @throws ParserException The passed filepath is outside of citrus' root directory.
     * @throws ParserException The following error occured while parsing the file '%s': %s.
     */
    static public function parseFile(string $filepath, array $attributes = []): null | array | object
    {

        if (($filepath = realpath($filepath)) === false || !is_file($filepath)) {
            throw new ParserException('The passed filepath is invalid or not a file.');
        } 

        // Check Extension
        if (($attrbiutes['skip-extension'] ?? false) !== true) {
            if (pathinfo($filepath, \PATHINFO_EXTENSION) !== 'php') {
                throw new ParserException('The passed filepath is not a PHP file or does not end with .php at least.');
            }
        }

        // Check within root
        if (($attrbiutes['skip-root'] ?? false) !== true) {
            if (strpos($filepath, citrus()->getRoot()) !== 0) {
                throw new ParserException('The passed filepath is outside of citrus\' root directory.');
            }
        }

        // Include File
        try {
            $content = include $filepath;
            if (!is_array($content) && !is_object($content)) {
                throw new \Exception('File did not return a valid array or object.');
            }
        } catch(\Exception $e) {
            throw new ParserException("The following error occured while parsing the file '$filepath': " . $e->getMessage());
        }
        return $content;
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

        return static::parse($content, $attributes);
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

        $result  = '<?php' . "\n";
        $result .= '    return ' . var_export($content, true);
        $result .= '?>';
        return $result;
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

        $result  = '<?php' . "\n";
        $result .= '    return ' . var_export($content, true);
        $result .= '?>';

        file_put_contents($filepath, $result);
        return true;
    }
    
}
