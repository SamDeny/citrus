<?php declare(strict_types=1);

namespace Citrus\Http;

use Citrus\Exceptions\CitrusException;

class File
{
    
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
     * Indicator if the file has been moved already.
     *
     * @var boolean
     */
    protected bool $moved;

    /**
     * Create a new File instance.
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
     * Move file to passed target location.
     *
     * @param string $targetPath
     * @return void
     * @throws CitrusException The file has already been moved.
     */
    public function moveTo(string $targetPath): void
    {
        if ($this->moved) {
            throw new CitrusException('The file has already been moved.');
        }

        if (is_uploaded_file($this->tempName)) {
            move_uploaded_file($this->tempName, $targetPath);
        } else {
            rename($this->tempName, $targetPath);
        }

        $this->moved = true;
    }

    /**
     * Get Filesize.
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->filesize;
    }

    /**
     * Get Upload-Error Code.
     *
     * @return int
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Get Client File name.
     *
     * @return string
     */
    public function getClientFilename(): string
    {
        return $this->filename;
    }

    /**
     * Get Client Media Type.
     *
     * @return string
     */
    public function getClientMediaType(): string
    {
        return $this->filetype;
    }

}
