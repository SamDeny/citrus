<?php declare(strict_types=1);

namespace Citrus\FileSystem;

use Citrus\Exceptions\CitrusException;

class FileSystem
{

    /**
     * Create a new File and initialize the FileSystem handler.
     *
     * @param string $path
     * @param string|null $file
     * @param boolean $recursive
     * @return void
     */
    static public function create(string $path, ?string $file, bool $recursive = false)
    {
        if (!file_exists($path)) {
            if (file_exists(dirname($path)) || $recursive) {
                if (@mkdir($path, 0666, $recursive) === false) {
                    return false;
                }
            } else {
                return false;
            }
        }

        if ($file) {
            $root = realpath($path) . DIRECTORY_SEPARATOR . $file;

            if (!file_exists($file)) {
                if (@touch($file) === false) {
                    return false;
                }
            }

            return new self($root);
        } else {
            $root = realpath($path);
            return new self($path);
        }
    }


    /**
     * Root Path
     *
     * @var string
     */
    protected string $root;

    /**
     * Root Path Type
     *
     * @var string
     */
    protected string $type;

    /**
     * Root PathInfo
     *
     * @var array
     */
    protected array $info;

    /**
     * Create a new FileSystem handler.
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new CitrusException("The FileSystem class must be initialized with an existing path, '$path' does not exist.");
        }
        $this->root = $path;
        $this->type = is_dir($path)? 'DIR': (is_link($path)? 'LINK': 'FILE');
        $this->info = pathinfo($path);
    }

    /**
     * Equals two files or directory names.
     *
     * @param string $path The desired path to compare.
     * @param bool $weak
     * @return boolean
     */
    public function equals(string $path, bool $weak = false): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        // Source or Target is a directory
        if ($this->type === 'DIR') {
            return is_dir($path) && realpath($path) === $this->root;
        } else if (is_dir($path)) {
            return false;
        }

        // Source or Target is a link or file
        if ($this->type === 'LINK' && !is_link($path)) {
            return readlink($this->root) === realpath($path);
        } else if (is_link($path) && $this->type === 'FILE') {
            return realpath($this->root) === readlink($path);
        } else {
            if ($weak) {
                return filemtime($this->root) === filemtime($path);
            } else {
                return md5_file($this->root, true) === md5_file($path, true);
            }
        }
    }
    
    /**
     * Create a Symblink to the passed link path.
     *
     * @param string $link The link path.
     * @param boolean $recursive Recursive creation of the path to the link path.
     * @param boolean $force Force the symlink even if it already exists.
     * @return boolean
     */
    public function symlink(string $link, bool $recursive = true, bool $force = false): bool
    {
        $folder = dirname($link);
        if (!file_exists($folder)) {
            if ($recursive) {
                if (@mkdir($folder, 0666, true) === false) {
                    return false;
                }
            } else {
                return false;
            }
        }

        if (!file_exists($link) || $force) {
            if (stripos(\PHP_OS, 'WIN') === 0) {
                if (!function_exists('exec') || @exec('mklink "'. $link .'" "'. $this->root .'"') === false) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return @symlink($this->root, $link);
            }
        }
    }

}
