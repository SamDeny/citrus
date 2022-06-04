<?php declare(strict_types=1);

namespace Citrus\Framework;

use Citrus\Exceptions\CitrusException;
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
     * All available Environment Arrays.
     *
     * @var array
     */
    protected array $environments = [];

    /**
     * The local Environment alias.
     *
     * @var ?string
     */
    protected ?string $localEnvironment = null;

    /**
     * Configuration Key => Values.
     *
     * @var array
     */
    protected array $configuration = [];

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
    }

    /**
     * Register new Environment array.
     *
     * @param array $env
     * @param string $alias
     * @return void
     * @throws CitrusException The apssed environment alias '%s' does already exist.
     */
    public function registerEnv(array &$env, string $alias)
    {
        if (array_key_exists($alias, $this->environments)) {
            throw new CitrusException("The passed environment alias '$alias' does already exist.");
        }
        $this->environments[$alias] = &$env;
    }

    /**
     * Load Environment File[s]
     *
     * @param string $path
     * @param string $env_key
     * @param boolean $load_subenv
     * @return void
     */
    public function loadEnv(string $path, string $env_key = '', bool $load_subenv = true, string $alias = '_LOCAL'): void
    {
        if (!file_exists($path) || !is_file($path)) {
            return;
        }

        // Load Environment Data
        [$result, $evaluate] = $this->parseEnv(file_get_contents($path));

        // Load Environment Data based on env_key
        $env_key = strtoupper($env_key);
        if ($load_subenv && !empty($env_key) &&  isset($result[$env_key])) {
            $key = strtolower($result[$env_key]);
    
            $path = dirname($path) . DIRECTORY_SEPARATOR . '.env.' . $key;
            if (file_exists($path)) {
                [$temp1, $temp2] = $this->parseEnv(file_get_contents($path));
                unset($temp1[$env_key]);

                $result = array_merge($result, $temp1);
                $evaluate = array_merge($evaluate, $temp2);
            }
        }

        // Lazy Evaluation
        foreach ($evaluate AS $key => $val) {
            if ($result[$key] !== chr(0)) {
                continue;   // Key has been overwritten by another .env file 
            }
            $result[$key] = $result[$val] ?? null;
        }

        // Set Environment Data
        $this->localEnvironment = $alias;
        $this->registerEnv($result, $alias);
    }

    /**
     * Get Environment Value
     *
     * @param string $key
     * @param string $default
     * @return mixed
     */
    public function getEnv(string $key, mixed $default = null): mixed
    {
        $local = trim(strtoupper($key));

        foreach ($this->environments AS $alias => $envs) {
            if (array_key_exists($alias === $this->localEnvironment? $local: $key, $envs)) {
                return $envs[$alias === $this->localEnvironment? $local: $key];
            }
        }

        return $default;
    }

    /**
     * Load Configuration File[s]
     *
     * @param string $path
     * @param string|null $alias
     * @param string|null $format The configuration file format. Supported: 'php', 'json', 'ini'.
     * @return void
     */
    public function loadConfig(string $path, ?string $alias = null, ?string $format = null): void
    {
        if (!file_exists($path)) {
            return;
        }
        if(($path = realpath($path)) === false) {
            return;
        }

        if (!is_file($path)) {
            $handle = opendir($path);
            while (($file = readdir($handle)) !== false) {
                if (!is_file($path . DIRECTORY_SEPARATOR . $file)) {
                    continue;
                }
                if ($file[0] === '.') {
                    continue;
                }

                $alias = substr($file, strpos($file, '.'));
                $format = pathinfo($path . DIRECTORY_SEPARATOR . $file, PATHINFO_EXTENSION);
                if (!in_array($format, ['php', 'json', 'ini'])) {
                    continue;
                }
                $this->loadConfig($path . DIRECTORY_SEPARATOR . $file, $alias, $format);
            }
            closedir($handle);
            return;
        }

        if (is_file($path)) {
            $filename = basename($path);
            if ($filename[0] === '.' || strpos($filename, '.') === false) {
                return;
            }

            // Fill Alias
            if (is_null($alias)) {
                $alias = substr($filename, strpos($filename, '.'));
            }

            // Check Alias
            $alias = strtolower($alias);
            if (array_key_exists($alias, $this->configuration)) {
                return;
            }

            // Fill & Check Format
            if (is_null($format)) {
                $format = pathinfo($path, PATHINFO_EXTENSION);
            }
            if (!in_array($format, ['php', 'json', 'ini'])) {
                return;
            }

            // Read & Parse Content
            if ($format === 'php') {
                $content = include $path;
            } else if ($format === 'json') {
                $content = json_decode(file_get_contents($path), true);
                $this->parseConfig($config, 'json');
            } else if ($format === 'ini') {
                $content = parse_ini_file($path, true);
                $this->parseConfig($config, 'init');
            }
            
            // Check Content
            if (!is_array($content)) {
                return;
            }

            // Set Configuration
            $this->configuration[$alias] = $content;
        }
    }

    /**
     * Get Configuration Value
     *
     * @param string $path
     * @return void
     */
    public function getConfig(string $path, mixed $default = null): mixed
    {
        var_dump($path);
        var_dump($this->configuration);
        return $default;
    }

    /**
     * Parse Environment File Content
     *
     * @param string $content
     * @return array
     */
    protected function parseEnv(string $content): array
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
     * Parse Configuration Data
     *
     * @param array $content
     * @return array
     */
    protected function parseConfig(array $content): ?array
    {
        return [];
    }

}
