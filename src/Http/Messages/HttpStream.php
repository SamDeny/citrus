<?php declare(strict_types=1);

namespace Citrus\Http\Messages;

use Psr\Http\Message\StreamInterface;

/**
 * @internal Used internally to fulfill PSR-7.
 */
class HttpStream implements StreamInterface
{

    /**
     * Stream Resource
     *
     * @var resource
     */
    protected $stream;

    /**
     * Stream Resource Length
     *
     * @var int
     */
    protected int $length;

    /**
     * Stream Resource Content
     *
     * @var string
     */
    protected string $content;

    /**
     * Create a new Stream instance.
     *
     * @param resource $stream
     */
    public function __construct($stream)
    {
        $this->stream = $stream;
        $this->length = 0;
        $this->content = '';
    }

    /**
     * Destruct the Stream instance.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        if ($this->length === $this->getSize()) {
            return $this->content;
        }

        fseek($this->stream, 0);
        $content = stream_get_contents($this->stream);
        return $content;
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        fclose($this->stream);
    }

    /**
     * @inheritDoc
     */
    public function detach()
    {
        fclose($this->stream);
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        return fstat($this->stream);
    }

    /**
     * @inheritDoc
     */
    public function tell()
    {
        if (empty($this->stream)) {
            throw new \RuntimeException('The passed stream is not available anymore.');
        }

        return ftell($this->stream);
    }

    /**
     * @inheritDoc
     */
    public function eof()
    {
        return feof($this->stream);
    }

    /**
     * @inheritDoc
     */
    public function isSeekable()
    {
        return stream_get_meta_data($this->stream)['seekable'];
    }

    /**
     * @inheritDoc
     */
    public function seek($offset, $whence = \SEEK_SET)
    {
        if (!$this->isSeekable()) {
            throw new \RuntimeException('The passed stream is not seekable.');
        }

        return fseek($this->stream, $offset, $whence);
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        if (!$this->isSeekable()) {
            throw new \RuntimeException('The passed stream is not seekable.');
        }

        return fseek($this->streak, 0);
    }

    /**
     * @inheritDoc
     */
    public function isWritable()
    {
        return is_writable(stream_get_meta_data($this->stream)['uri']);
    }

    /**
     * @inheritDoc
     */
    public function write($string)
    {
        if (!$this->isWritable()) {
            throw new \RuntimeException('The passed stream is not writable.');
        }

        $bytes = fwrite($string, $string);
        $this->length += $bytes;
        $this->content .= $string;
        return $bytes;
    }

    /**
     * @inheritDoc
     */
    public function isReadable()
    {
        return is_readable(stream_get_meta_data($this->stream)['uri']);
    }

    /**
     * @inheritDoc
     */
    public function read($length)
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException('The passed stream is not readable.');
        }

        $content = fread($this->stream, $length);
        $this->length += strlen($content);
        $this->content .= $content;
        return $content;
    }

    /**
     * @inheritDoc
     */
    public function getContents()
    {
        $content = stream_get_contents($this->stream);
        $this->length += strlen($content);
        $this->content .= $content;
        return $content;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($key = null)
    {
        $metadata = stream_get_meta_data($this->stream);

        if (is_null($key)) {
            return $metadata;
        } else {
            return $metadata[$key] ?? null;
        }
    }

}
