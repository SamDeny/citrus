<?php declare(strict_types=1);

namespace Citrus\Http\Messages;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal Used internally to fulfill PSR-7.
 */
abstract class HttpMessage implements MessageInterface
{

    /**
     * Message Protocol Version
     *
     * @var string
     */
    protected string $protocol;

    /**
     * All uncleaned Headers, values are presented as arrays.
     *
     * @var array
     */
    protected array $headers;

    /**
     * Maps uppercased header keys with original header keys.
     *
     * @var array
     */
    protected array $headerKeyMap;

    /**
     * Message Stream Interface
     *
     * @var StreamInterface|null
     */
    protected ?StreamInterface $body;

    /**
     * Array Map of properties to be cloned.
     *
     * @var array
     */
    protected array $cloneSet = [];

    /**
     * Create a new Message instance.
     * 
     * @param string $protocol
     * @param array $headers
     * @param string|StreamInterface $body
     */
    public function __construct(string $protocol, array $headers, StreamInterface $body)
    {
        $this->protocol = $protocol;
        $this->headers = array_map(fn ($val) => is_string($val)? [$val]: $val, $headers);
        $this->headerKeyMap = array_combine(
            array_keys(array_change_key_case($headers)),
            array_keys($headers)
        );
        $this->body = $body;
    }

    /**
     * Clone Object
     *
     * @return void
     */
    public function __clone()
    {
        foreach ($this->cloneSet AS $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * Clone current instance with changed values.
     *
     * @param array $values
     * @return static
     */
    protected function clone(array $values): static
    {
        $this->cloneSet = $values;

        $clone = clone $this;

        $this->cloneSet = [];
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($protocol)
    {
        return $this->clone(['protocol' => $protocol]);
    }

    /**
     * @inheritDoc
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name)
    {
        return array_key_exists(strtoupper($name), $this->headerKeyMap);
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name)
    {
        $name = $this->headerKeyMap[strtoupper($name)] ?? '';
        return !empty($name)? $this->headers[$name] ?? []: [];
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name)
    {
        $name = $this->headerKeyMap[strtoupper($name)] ?? '';
        return !empty($name)? implode(',', $this->headers[$name]) ?? []: '';
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value)
    {
        $headers = array_merge($this->headers, [$name => $value]);
        $headerKeyMap = array_merge($this->headerKeyMap, [strtoupper($name) => $name]);

        return $this->clone(['headers' => $headers, 'headerKeyMap' => $headerKeyMap]);
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value)
    {
        $headerKey = $this->headerKeyMap[strtoupper($name)] ?? '';

        // Add Header
        $headers = $this->headers;
        if (!empty($headerKey)) {
            $headers[$headerKey][] = $value;
        } else {
            $headers[$headerKey] = [$value];
        }
        $headerKeyMap = array_merge($this->headerKeyMap, [strtoupper($name) => $name]);

        return $this->clone(['headers' => $headers, 'headerKeyMap' => $headerKeyMap]);
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name)
    {
        $headerKey = $this->headerKeyMap[strtoupper($name)] ?? '';
        $headers = $this->headers;
        $headerKeyMap = $this->headerKeyMap;

        if (!empty($headerKey)) {
            unset($headers[$headerKey]);
            unset($headerKeyMap[strtoupper($name)]);
        }

        return $this->clone(['headers' => $headers, 'headerKeyMap' => $headerKeyMap]);
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body)
    {
        return $this->clone(['body' => $body]);
    }

}
