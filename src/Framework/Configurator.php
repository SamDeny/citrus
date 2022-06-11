<?php declare(strict_types=1);

namespace Citrus\Framework;

use Citrus\Exceptions\CitrusException;
use Citrus\Utilities\Dictionary;
use Citrus\Utilities\Str;

/**
 * Application Configurator
 * The application configurator is used by the main Citrus Application instance 
 * itself to manage the available environment data and set configurations. You 
 * can simple access this class by using the `env()` or `config()` methods or 
 * some of the methods provided by the Application class.
 */
class Configurator
{

    /**
     * Current Configurator instance.
     *
     * @var self|null
     */
    static protected ?self $instance = null;

    /**
     * Get current Configurator instance.
     *
     * @return self
     * @throws CitrusException The Configurator has not been initialized yet.
     */
    static public function getInstance(): self
    {
        if (!self::$instance) {
            throw new CitrusException('The Configurator has not been initialized yet.');
        }
        return self::$instance;
    }


    /**
     * Main Context Key.
     *
     * @var string
     */
    private string $contextKey = 'CITRUS_ENV';

    /**
     * Current Environment Status.
     *
     * @var string
     */
    private string $environmentStatus = 'unlocked';

    /**
     * Available Environment Data Sets (Priority: FILO).
     * 
     * @var array
     */
    protected array $environments = [];

    /**
     * Available Application Configurations.
     * 
     * @var Dictionary
     */
    protected Dictionary $configurations;

    /**
     * Create a new Configurator instance.
     * 
     * @throws CitrusException The Configurator cannot be initialized twice.
     */
    public function __construct()
    {
        if (isset(self::$instance)) {
            throw new CitrusException('The Configurator cannot be initialized twice.');
        }
        self::$instance = $this;

        // Create Dictionary for Configurations
        $this->configurations = new Dictionary();
    }

    /**
     * Set the main environment context key.
     *
     * @param string $contextKey The desired environment context key to set.
     * @return self
     * @throws CitrusException The environment has already been locked, you cannot further use this method.
     */
    public function setContextKey(string $contextKey): self
    {
        if ($this->environmentStatus === 'locked') {
            throw new CitrusException('The environment has already been locked, you cannot further use this method.');
        }

        $this->contextKey = strtoupper($contextKey);
        return $this;
    }

    /**
     * Get main environment context key
     * 
     * @return string
     */
    public function getContextKey(): string
    {
        return $this->contextKey;
    }

    /**
     * Lock environment and prevent further changes (This has no affects to the 
     * configuration parts / methods of this class).
     *
     * @return self
     */
    public function lockEnvironment(): self
    {
        $this->environmentStatus = 'locked';
        return $this;
    }

    /**
     * Set environment data array.
     *
     * @param array $data The plain environment array to set.
     * @return self
     * @throws CitrusException The environment has already been locked, you cannot further use this method.
     */
    public function setEnvironmentData(array &$data): self
    {
        if ($this->environmentStatus === 'locked') {
            throw new CitrusException('The environment has already been locked, you cannot further use this method.');
        }

        // Prepend the data due to the First-in Last-Out principle.
        array_unshift($this->environments, $data);
        return $this;
    }

    /**
     * Load environment file/path.
     *
     * @param string $path The direct filepath to the desired environment file.
     * @param boolean $loadSubs True to subsequently load context-specific, 
     *                files, if available. Supports `.env.[CONTEXT]` and 
     *                `.[CONTEXT].env` in this order.
     * @return self
     * @throws CitrusException The environment has already been locked, you cannot further use this method.
     * @throws CitrusException The passed filepath '%s' does not exist or is not a file.
     */
    public function loadEnvironmentFile(string $path, bool $loadSubs = false): self
    {
        if ($this->environmentStatus === 'locked') {
            throw new CitrusException('The environment has already been locked, you cannot further use this method.');
        }

        if (!file_exists($path) || !is_file($path)) {
            throw new CitrusException("The passed filepath '$path' does not exist or is not a file.");
        }

        // Parse Environment
        [$result, $evaluate] = $this->parseEnvironment(file_get_contents($path));
        $contextKey = $this->contextKey;

        // Parse subsequently Environments 
        if ($loadSubs && !empty($contextKey) && isset($result[$contextKey])) {
            $context = strtolower($result[$contextKey]);
    
            $subEnv = dirname($path) . DIRECTORY_SEPARATOR . ".env.{$context}";
            if (file_exists($subEnv)) {
                $subEnv = dirname($path) . DIRECTORY_SEPARATOR . ".{$context}.env";
            }

            if (file_exists($subEnv)) {
                [$temp1, $temp2] = $this->parseEnvironment(file_get_contents($subEnv));
                unset($temp1[$contextKey]);

                $result = array_merge($result, $temp1);
                $evaluate = array_merge($evaluate, $temp2);
            }
        }

        // Lacy Evaluation
        foreach ($evaluate AS $key => $val) {
            if ($result[$key] !== chr(0)) {
                continue;   // Key has been overwritten by another .env file 
            }
            $result[$key] = $result[$val] ?? null;
        }

        // Set Environment Data
        $this->setEnvironmentData($result);
        return $this;
    }

    /**
     * Parse Environment file content.
     *
     * @param string $content
     * @return array
     */
    protected function parseEnvironment(string $content): array
    {
        if (empty($content)) {
            return [];
        }
        $content = Str::normalizeEol($content);
        $lines = explode("\n", $content);

        // Walk through the passed content
        $result = [];
        $evaluate = [];
        do {
            $line = current($lines);
            $temp = strtolower($line);
            if (trim($temp) === '') {
                continue;
            }
            
            // Explode Key and Value
            if (strpos($temp, 'export ') === 0) {
                if (strpos($temp, '=') === false) {
                    continue;   // Skip plain export [VARIABLE]
                }
                [$key, $value] = explode(' ', substr($line, 7), 2);
            } else if (strpos($temp, 'setenv ') === 0) {
                [$key, $value] = explode(' ', substr($line, 7), 2);
            } else if (strpos($temp, 'set ') === 0) {
                [$key, $value] = explode('=', substr($line, 4), 2);
            } else {
                if (strpos($temp, '=') === false) {
                    continue;   // Skip invalid values.
                }
                [$key, $value] = explode('=', $line, 2);
            }
            $key = trim(strtoupper($key));
            $value = trim($value);

            // Skip empty Values
            if ($value === '') {
                continue;
            }

            // Evaluate Value
            if (($value[0] === '"' || $value[0] === "'") && $value[0] === $value[strlen($value)-1]) {
                $value = substr($value, 1, -1);
            }

            $temp = strtolower($value);
            if (in_array($temp, ['true', 'on', 'yes'])) {
                $value = true;
            } else if (in_array($temp, ['false', 'off', 'no'])) {
                $value = false;
            } else if (in_array($temp, ['null', 'none', 'nil'])) {
                $value = null;
            } else if (is_numeric($temp)) {
                $value = strpos($temp, '.') === false? intval($temp): floatval($temp);
            }

            // Evaluation
            if (strpos($temp, '${') === 0 && $temp[strlen($temp)-1] === '}') {
                $evaluate[$key] = substr($temp, 2, -1);
                $value = chr(0);
            }

            // Append Value
            $result[$key] = $value;
        } while(next($lines) !== false);

        // Return Data
        return [$result, $evaluate];
    }

    /**
     * Get environment data.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getEnvironment(string $key, mixed $default = null): mixed
    {
        $local = trim(strtoupper($key));

        foreach ($this->environments AS $environment) {
            if (array_key_exists($key, $environment)) {
                return $environment[$key];
            }
            if (array_key_exists($local, $environment)) {
                return $environment[$local];
            }
        }

        return $default;
    }

    /**
     * Set Configuration using an alias and the raw config data.
     *
     * @param string $alias
     * @param array $config
     * @return void
     */
    public function setConfiguration(string $alias, array $config)
    {
        $this->configurations[$alias] = $config;
    }

    /**
     * Get Configuration using the key path.
     *
     * @param string $key
     * @param mixed $default
     * @return void
     */
    public function getConfiguration(string $key, mixed $default = null)
    {
        if (isset($this->configurations[$key])) {
            return $this->configurations[$key];
        } else {
            return $default;
        }
    }

}
