<?php declare(strict_types=1);

namespace Citrus\Http\Messages;

use Psr\Http\Message\UploadedFileInterface;

/**
 * @internal Used internally to fulfill PSR-7.
 */
class HttpUploadedFile implements UploadedFileInterface
{

    /**
     * Indicator if the file has been moved already.
     *
     * @var boolean
     */
    protected bool $moved;
    
    /**
     * Original filename from the client machine.
     *
     * @var string
     */
    protected string $filename;
    
    /**
     * Original transmitted File MIME/Type from the client machine.
     *
     * @var string
     */
    protected string $filetype;

    /**
     * Filesize in bytes.
     *
     * @var integer|null
     */
    protected ?int $filesize;

    /**
     * Temporary filename on the server.
     *
     * @var string
     */
    protected string $tempName;

    /**
     * Error code
     *
     * @var integer
     */
    protected int $error;

    /**
     * Create a new UploadedFile instance.
     * 
     * @param array $file
     */
    public function __construct(array $file)
    {
        $this->filename = $file['name'] ?? null;
        $this->filetype = $file['type'] ?? null;
        $this->filesize = $file['size'] ?? null;
        $this->tempName = $file['tmp_name'];
        $this->error = $file['error'];
        $this->moved = false;
    }

    /**
     * @inheritDoc
     */
    public function getStream()
    {
        if ($this->moved) {
            throw new \RuntimeException('This uploaded file has already been moved.');
        }

        if (($stream = fopen($this->tempName, 'r')) === false) {
            throw new \RuntimeException('The file could not be opened.');
        }

        return $stream;
    }

    /**
     * @inheritDoc
     */
    public function moveTo($targetPath)
    {
        if ($this->moved) {
            throw new \RuntimeException('The file has already been moved.');
        }

        if (is_uploaded_file($this->tempName)) {
            move_uploaded_file($this->tempName, $targetPath);
        } else {
            rename($this->tempName, $targetPath);
        }

        $this->moved = true;
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        return $this->filesize;
    }

    /**
     * @inheritDoc
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @inheritDoc
     */
    public function getClientFilename()
    {
        return $this->filename;
    }

    /**
     * @inheritDoc
     */
    public function getClientMediaType()
    {
        return $this->filetype;
    }

}
