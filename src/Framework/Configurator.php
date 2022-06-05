<?php declare(strict_types=1);

namespace Citrus\Framework;

use Citrus\Exceptions\CitrusException;
use Citrus\Structures\Dictionary;
use Citrus\Utilities\StringUtility;

class Configurator
{

    /**
     * The current Configurator instance.
     *
     * @var Configurator|null
     */
    static protected ?Configurator $instance = null;

    /**
     * Get current Application instance
     *
     * @return Configurator
     * @throws CitrusException The Configurator has not been initialized yet.
     */
    static public function getInstance(): Configurator
    {
        if (!self::$instance) {
            throw new CitrusException('The Configurator has not been initialized yet.');
        }
        return self::$instance;
    }


    /**
     * Primary Environment Key.
     *
     * @var string|null
     */
    private ?string $environmentKey = null;

    /**
     * Current Environment Status
     *
     * @var string
     */
    private string $environmentStatus = 'unlocked';

    /**
     * Available Environment Data Sets (first-in-last-out)
     * 
     * @var array
     */
    protected array $environments = [];

    /**
     * Available Configuration Dictionary
     * 
     * @var Dictionary
     */
    protected Dictionary $configs;

    /**
     * Create a new Configurator instance.
     * 
     * @throws CitrusException The Configurator cannot be initialized twice.
     */
    public function __construct()
    {
        if (self::$instance) {
            throw new CitrusException('The Configurator cannot be initialized twice.');
        }
        self::$instance = $this;

        $this->configs = new Dictionary;
    }

    /**
     * Set the primary envionment key.
     *
     * @param string $envKey The desired environment key to set.
     * @return void
     * @throws CitrusException The environment has already been locked, you cannot further use this method.
     */
    public function setEnvironmentKey(string $envKey): void
    {
        if ($this->environmentStatus === 'locked') {
            throw new CitrusException('The environment has already been locked, you cannot further use this method.');
        }
        $this->environmentKey = strtoupper($envKey);
    }

    /**
     * Lock environment and prevent further changes.
     *
     * @return void
     */
    public function lockEnvironment(): void
    {
        $this->environmentStatus = 'locked';

        // The First environment arrays are the last to search in
        $this->environments = array_reverse($this->environments);
    }

    /**
     * Set environment data using an array.
     *
     * @param array $data The plain environment array to set.
     * @return void
     * @throws CitrusException The environment has already been locked, you cannot further use this method.
     */
    public function setEnvironmentData(array &$data)
    {
        if ($this->environmentStatus === 'locked') {
            throw new CitrusException('The environment has already been locked, you cannot further use this method.');
        }
        $this->environments[] = &$data;
    }

    /**
     * Load environment data using a file/path.
     *
     * @param string $path The direct filepath to the desired environment file.
     * @param boolean $loadSubs True to subsequently laod environment-specific, 
     *                files, if available (ex. `.env.development`).
     * @return void
     * @throws CitrusException The environment has already been locked, you cannot further use this method.
     * @throws CitrusException The passed filepath '%s' does not exist or is not a file.
     */
    public function loadEnvironmentFile(string $path, bool $loadSubs = false)
    {
        if ($this->environmentStatus === 'locked') {
            throw new CitrusException('The environment has already been locked, you cannot further use this method.');
        }

        if (!file_exists($path) || !is_file($path)) {
            throw new CitrusException("The passed filepath '$path' does not exist or is not a file.");
        }

        // Parse Environment
        [$result, $evaluate] = $this->parseEnvironment(file_get_contents($path));

        // Parse subsequently Environments 
        if ($loadSubs && !empty($env_key) &&  isset($result[$env_key])) {
            $key = strtolower($result[$env_key]);
    
            $path = dirname($path) . DIRECTORY_SEPARATOR . '.env.' . $key;
            if (file_exists($path)) {
                [$temp1, $temp2] = $this->parseEnv(file_get_contents($path));
                unset($temp1[$env_key]);

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
    }

    /**
     * Get environment data.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getEnvironmentData(string $key, mixed $default = null): mixed
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
     * Get current environment.
     *
     * @param string $default
     * @return string
     */
    public function getEnvironment(string $default = 'production'): string
    {
        if (empty($this->environmentKey)) {
            return $default;
        }
        return $this->getEnvironmentData($this->environmentKey, $default);
    }

    /**
     * Parse Environment File Content
     *
     * @param string $content
     * @return array
     */
    protected function parseEnvironment(string $content): array
    {
        if (empty($content)) {
            return [];
        }
        $content = StringUtility::normalizeEol($content);
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
     * Set Configuration using an alias and the raw config data.
     *
     * @param string $alias
     * @param array $config
     * @return void
     */
    public function setConfiguration(string $alias, array $config)
    {

    }

    /**
     * Set multiple alias => config Configurations.
     *
     * @param array $configs
     * @return void
     */
    public function setConfigurations(array $configs)
    {
        array_walk($configs, fn($val, $key) => $this->setConfiguration($key, $val));
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

    }

}
