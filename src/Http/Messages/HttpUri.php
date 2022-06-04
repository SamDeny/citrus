<?php declare(strict_types=1);

namespace Citrus\Http\Messages;

use Psr\Http\Message\UriInterface;

/**
 * @internal Used internally to fulfill PSR-7.
 */
class HttpUri implements UriInterface
{

    /**
     * Default supported ports
     */
    const DEFAULT_PORTS = [
        'http'      => 80,
        'https'     => 443,
        'ftp'       => 21,
        'ftps'      => 22,      // FTP-over-SSH, thus using SSH port
        'sftp'      => 115,
        'ssh'       => 22,
        'ws'        => 80,      // WebSockets are using the HTTP protocol
        'wss'       => 443,     // WebSockets are using the HTTP protocol
    ];
    
    /**
     * Uri Scheme
     *
     * @var string
     */
    protected string $scheme;
    
    /**
     * Uri Username Part
     *
     * @var string
     */
    protected string $username;
    
    /**
     * Uri Password Part
     *
     * @var string
     */
    protected string $password;
    
    /**
     * Uri Hostname
     *
     * @var string
     */
    protected string $host;
    
    /**
     * Uri Port Number
     *
     * @var ?int
     */
    protected ?int $port;
    
    /**
     * Uri Path
     *
     * @var string
     */
    protected string $path;
    
    /**
     * Uri Query
     *
     * @var string
     */
    protected string $query;
    
    /**
     * Uri Fragment
     *
     * @var string
     */
    protected string $fragment;

    /**
     * Array Map of properties to be cloned.
     *
     * @var array
     */
    protected array $cloneSet = [];

    /**
     * Create a new Uri instance.
     *
     * @param string $scheme
     * @param string $userinfo
     * @param string $host
     * @param ?int $port
     * @param string $path
     * @param string $query
     * @param string $fragment
     */
    public function __construct(string $scheme, string $userinfo, string $host, ?int $port, string $path, string $query, string $fragment = '')
    {
        $this->scheme = $scheme;
        $this->username = '';
        $this->password = '';
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->query = $query;
        $this->fragment = $fragment;
        
        if (!empty($userinfo)) {
            if (($index = strpos(':', $userinfo)) !== false) {
                $this->username = substr($userinfo, 0, $index);
                $this->password = substr($userinfo, $index+1);
            } else {
                $this->username = $userinfo;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        $result = '';

        if (!empty($this->scheme)) {
            $result .= $this->scheme . ':';
        }
        if (($auth = $this->getAuthority()) !== '') {
            $result .= '//' . $auth;
        }
        if (!empty($auth) && empty($this->path)) {
            $result .= '/';
        } else if (!empty($this->path)) {
            $result .= empty($auth)? ('/' . ltrim($this->path, '/')): $this->path;
        }
        if (!empty($this->query)) {
            $result .= '?' . $this->query;
        }
        if (!empty($this->fragment)) {
            $result .= '#' . $this->fragment;
        }

        return $result;
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
    protected function clone(array $values): UriInterface
    {
        $this->cloneSet = $values;

        $clone = clone $this;

        $this->cloneSet = [];
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @inheritDoc
     */
    public function withScheme($scheme)
    {
        $scheme = strtolower($scheme);

        if ($scheme[strlen($scheme)-1] === ':') {
            $scheme = substr($scheme, 0, -1);
        }

        if (!ctype_alpha($scheme) || empty($scheme)) {
            throw new \InvalidArgumentException('The passed scheme is invalid or empty.');
        }

        if (!array_key_exists($scheme, self::DEFAULT_PORTS)) {
            throw new \InvalidArgumentException('The passed scheme is not supported. Supported schemes: ' . implode(', ', array_keys(self::DEFAULT_PORTS)));
        }

        return $this->clone(['scheme' => $scheme]);
    }

    /**
     * @inheritDoc
     */
    public function getAuthority()
    {
        $result = $this->getUserInfo();

        if (!empty($result)) {
            $result .= '@';
        }

        $result .= $this->getHost();

        if (!$this->isDefaultPort()) {
            $result .= ':' . $this->getPort();
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getUserInfo()
    {
        $result = $this->username;

        if (!empty($result) && !empty($this->password)) {
            $result .= ':' . $this->password;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function withUserInfo($username, $password = null)
    {
        return $this->clone(['username' => $username, 'password' => $password]);
    }

    /**
     * @inheritDoc
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @inheritDoc
     */
    public function withHost($host)
    {
        return $this->clone(['host' => $host]);
    }

    /**
     * Check if the provided port is the default port for the assigned scheme.
     *
     * @return boolean
     */
    public function isDefaultPort(): bool
    {
        if (empty($this->scheme)) {
            return false;
        }

        return self::DEFAULT_PORTS[$this->scheme] === $this->port;
    }

    /**
     * @inheritDoc
     */
    public function getPort()
    {
        if (empty($this->port)) {
            return null;
        }

        return $this->isDefaultPort()? null: $this->port;
    }

    /**
     * @inheritDoc
     */
    public function withPort($port)
    {
        $port = intval($port);

        if ($port < 0 || $port > 65535) {
            throw new \InvalidArgumentException('The passed Port is invalid.');
        }

        return $this->clone(['port' => $port]);
    }

    /**
     * @inheritDoc
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function withPath($path)
    {
        return $this->clone(['path' => $path]);
    }

    /**
     * @inheritDoc
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @inheritDoc
     */
    public function withQuery($query)
    {
        return $this->clone(['query' => $query]);
    }

    /**
     * @inheritDoc
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @inheritDoc
     */
    public function withFragment($fragment)
    {
        return $this->clone(['fragment' => $fragment]);
    }

}
