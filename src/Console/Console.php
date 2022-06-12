<?php declare(strict_types=1);

namespace Citrus\Console;

use Citrus\Console\Commands\AboutCommand;
use Citrus\Console\Commands\HelpCommand;
use Citrus\Contracts\Command;
use Citrus\Exceptions\CitrusException;

/**
 * The Citrus Console class provides a simple interface to extend Citrus CLI 
 * tool with which you can manage your Citrus application completely using the 
 * command-line-interface of your OS through the root ./citrus file. Similar 
 * to Laravel's Artisan those registered commands can be called as follows:
 * 
 *      php citrus [namespace]:[method]
 * 
 * The [namespace] is a simple taxonomy, which just separates the different 
 * [method]'s. Calling the same command without the colon and [method] part 
 * will also list all available methods of the respective namespace, which has 
 * been registered using the static registerCommand() method below.
 * 
 * Parameters
 * ----------
 * The Citrus CLI tool does also provide the possibility of passing additional 
 * paramters to the single methods, either using the long-term syntax prefixed 
 * with two --dashed or the short-term syntax with a single dash. The supported 
 * parameters must be passed within the static describe() method of the 
 * corresponding command class, visit the docs for more information about this.
 * 
 *      php citrus [namespace]:[method] --param1 -p2
 * 
 * Citrus supports three types for Parameters: 'boolean' (without any additional 
 * value), 'string' (with a value) and 'array', which allows using the same 
 * parameter multiple times to pass multiple strings. Strings can be passed 
 * using an equal sign or a simple space after the parameter name, regardless of 
 * wheter the long or short-term parameter version is used. Double Quoting is 
 * is also supported and, when dashes or other special characters are used
 * within the string, also required. All of those are valid:
 * 
 *      php citrus [namespace]:[command] --param1 value1
 *      php citrus [namespace]:[command] --param1="value1"
 *      php citrus [namespace]:[command] -p1 "value1" -p1 "value2"
 * 
 * File Parameters
 * ---------------
 * String-typed parameters do also support passing a file instead of any 
 * content by starting the custom value with an @ sign. However, neither Citrus 
 * nor Console validates or parses the file, it will simple resolve relative 
 * paths and add a 'file://' on the beginning of the respective parameter. The 
 * rest MUST be done by your command method. 
 * 
 *      php citrus [namespace]:[command] --param @relative/path/file.json
 *      php citrus [namespace]:[command] --param "@/absolute/path/to a file.json"
 * 
 * Parameter Modifications
 * -----------------------
 * Parameters do also support so-called modification values, which are used to 
 * further configure a parameter with a simple syntax. Mod-Values are written 
 * directly after the parameter, prefixed with a colon:
 * 
 *      php citrus [namespace]:[command] --param:mod 'My Value'
 * 
 * Such mod values are limited to alpha and underline characters ([a-z_]) and 
 * will be passed case-sensitive to your command method within the second 
 * 'config' array. Like Parameters, those mod values must be available within 
 * the description array as noted in the documentation.
 * 
 * Working with the Console
 * ------------------------
 * Just a quick note on the end: Please never try to `print()` your content 
 * yourself. Citrus gives you a neat Writer class, with which you can write,
 * colourize and format your ouput way better... and more.
 */
class Console
{

    /**
     * Registered Commands
     *
     * @var array
     */
    static protected $commands = [
        'about:app'         => AboutCommand::class,
        'about:hello'       => AboutCommand::class,
        'about:composer'    => AboutCommand::class,
        'about:version'     => AboutCommand::class,
        'help'              => HelpCommand::class,
        'help:list'         => HelpCommand::class,
        'help:index'        => HelpCommand::class,
        'help:usage'        => HelpCommand::class,
        'help:describe'     => HelpCommand::class,
        'help:unknown'      => HelpCommand::class
    ];

    /**
     * Register a new command
     *
     * @param string $command The main action, which should be assigned to your 
     *               command class.
     * @param string $class The desired command class to be used.
     * @return void
     * @throws CitrusException The passed command action '%s' has already been registered.
     * @throws CitrusException The passed namespace for the command '%s' cannot be used.
     * @throws CitrusException The passed command class '%s' does not extend the Command interface.
     */
    static public function registerCommand(string $command, string $class): void
    {
        if (array_key_exists($command, self::$commands)) {
            throw new CitrusException("The passed command '$command' has already been registered.");
        }

        if (strpos($command, ':') === false) {
            throw new CitrusException("The passed command '$command' does not define a namspace.");
        }

        if (str_starts_with($command, 'citrus:')) {
            throw new CitrusException("The passed namespace for the command '$command' cannot be used.");
        }

        $interfaces = class_implements($class);

        if (!in_array(Command::class, $interfaces)) {
            throw new CitrusException("The passed command class '$class' does not extend the Command interface.");
        }

        self::$commands[$command] = $class;
    }

    /**
     * Get all registered Commands.
     *
     * @return array
     */
    static public function getAllCommands(): array
    {
        return self::$commands;
    }


    /**
     * The priginal CLI arguments array.
     *
     * @var array
     */
    protected $originalArgs = [];
    
    /**
     * The parsed and cleaned arguments array.
     *
     * @var array
     */
    protected $args = [];

    /**
     * The original script, fetched from the arguments array.
     *
     * @var string
     */
    protected $script = '';
    
    /**
     * The resulting command, fetched from the arguments array.
     *
     * @var string
     */
    protected $command = '';

    /**
     * Current parsed command parameters.
     *
     * @var array
     */
    protected $params = [];

    /**
     * Current parsed command configuration.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Create a new Console instnace.
     */
    public function __construct()
    {
        
    }

    /**
     * Get original arguments array
     *
     * @return array
     */
    public function getOriginalArgs(): array
    {
        return $this->originalArgs;
    }

    /**
     * Get parsed arguments array
     *
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Get current script value from arguments array.
     *
     * @return string
     */
    public function getScript(): string
    {
        return $this->script;
    }

    /**
     * Get current command value from arguments array.
     *
     * @return void
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Get current parsed command parameters.
     *
     * @return void
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Get current parsed command configuration.
     *
     * @return void
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Parse arguments regarding the command description (if any)
     *
     * @param array $details The command details or an empty array.
     * @param array $args The command arguments.
     * @param array &$params A reference for the parameter array. 
     * @param array &$config A reference for the configuration array.
     * @return array Containing all errors, empty if everything is cool.
     */
    protected function parseArguments(array $details, array $args, array &$params, array &$config): array
    {
        $supported = [];
        $required = [];
        $aliases = [];
        $hasDefault = false;

        // Parse command arguments description
        if (isset($details['args'])) {
            foreach ($details['args'] AS $key => $data) {
                $supported[$key] = [
                    'type'      => $data['type'] ?? 'unknown',
                    'unmeet'    => [],
                    'mods'      => array_keys($data['mods'] ?? [])
                ];
                if ($data['required'] ?? false) {
                    $required[] = $key;
                }
                if ($data['unmeet'] ?? false) {
                    $supported[$key]['unmeet'] = is_array($data['unmeet'])? $data['unmeet']: explode(',', $data['unmeet']);
                }
                if (isset($data['short'])) {
                    if ($data['short'] === '') {
                        $hasDefault = $key;
                    } else {
                        $aliases[$data['short']] = $key;
                    }
                }
            }
        }

        // Loop Arguments
        $errors = [];
        while (current($args) !== false) {
            $arg = current($args);
            $mod = null;    // Argument Modification
            $val = null;    // Argument Value
            $des = [];      // Argument Description

            // No Argument
            if (strpos($arg, '-') === false) {
                if ($hasDefault && empty($params)) {
                    $params[$hasDefault] = $arg;
                    if (in_array($hasDefault, $required)) {
                        unset($required[array_search($hasDefault, $required)]);
                    }
                } else {
                    $errors[] = [null, 'no-key', $arg];
                }
                next($args);
                continue;
            }
            $arg = ltrim($arg, '-');

            // Extract Value when using an equal sign
            if (($index = strpos($arg, '=')) !== false) {
                $val = substr($arg, $index+1);
                $arg = substr($arg, 0, $index);
            }

            // Extract Modification
            if (($index = strpos($arg, ':')) !== false) {
                $mod = substr($arg, $index+1);
                $arg = substr($arg, 0, $index) . substr($arg, $index+strlen($mod)+1);
            }
            
            // Resolve real key
            $key = isset($aliases[$arg])? $aliases[$arg]: $arg;
            $des = isset($supported[$key])? $supported[$key]: [];

            // Validate argument typing
            if (($des['type'] ?? 'unknown') === 'boolean') {
                if (is_null($val)) {
                    $val = next($args);
                    if ($val !== false && strpos($val, '-') === false) {
                        $errors[] = [$key, 'boolean-type', $val];
                    }
                    if ($val !== false) {
                        prev($args);
                    }
                } else {
                    $errors[] = [$key, 'boolean-type', $val];
                }
                $params[$key] = true;
            } else if (($des['type'] ?? 'unknown') === 'string' || ($des['type'] ?? 'unknown') === 'array') {
                if (is_null($val)) {
                    $val = next($args);
                    if ($val === false || strpos($val, '-') === 0) {
                        $errors[] = [$key, $des['type'] . '-type', $key, $val];
                        $val = null;
                        if ($val !== false) {
                            prev($args);
                        }
                    }
                }
                if ($des['type'] === 'array') {
                    if (array_key_exists($key, $params)) {
                        $params[$key][] = $val;
                    } else {
                        $params[$key] = [$val];
                    }
                } else {
                    $params[$key] = $val;
                }
            } else {
                $val = next($args);
                if ($val !== false && strpos($val, '-') === 0) {
                    $val = null;
                    if ($val !== false) {
                        prev($args);
                    }
                }
                $params[$key] = $val ?? true;
            }

            // Set Mod
            if ($mod && (!isset($des['mods']) || in_array($mod, $des['mods'] ?? []))) {
                if (($des['type'] ?? 'unknown') === 'array') {
                    if (!array_key_exists($key, $config)) {
                        $config[$key] = [];
                    }
                    $config[$key][count($params[$key])-1] = $mod;
                } else {
                    $config[$key] = $mod;
                }
            }

            // Remove Requirements
            if (in_array($key, $required)) {
                unset($required[array_search($key, $required)]);
            }

            // Continue Array
            next($args);
        }

        // Check Requirements
        if (!empty($required)) {
            foreach ($required AS $key) {
                $errors[] = [$key, 'required'];
            }
        }

        // Check Unmeets
        $paramKeys = array_keys($params);
        $opposite = [];
        foreach ($paramKeys AS $key) {
            if (!isset($supported[$key]) || empty($supported[$key]['unmeet'])) {
                continue;
            }
            $compare = $supported[$key]['unmeet'];
            $unmeet = array_intersect($compare, $paramKeys);

            if (!empty($unmeet) && !in_array($key, $opposite)) {
                $errors[] = [$key, 'unmeet', $key, ...$unmeet];
                $opposite = array_merge($opposite, $unmeet);
            }
        }

        // Return Errors
        return $errors;
    }

    /**
     * Print Execution Errors
     *
     * @return void
     */
    public function printErrors(array $errors)
    {
        $writer = new Writer();

        // Write Header
        $writer->line();
        $writer->line($writer->red('Error occurred while executing command:'));
        $writer->line("    php " . implode(' ', $this->originalArgs));

        // Write Errors
        $count = 1;
        foreach ($errors AS $error) {
            $key = array_shift($error);
            $type = array_shift($error);
            $args = $error;

            $writer->line();
            switch ($type) {
                case 'no-key':
                    $writer->line(
                        $writer->red($count . '. Invalid Syntax on token ') . 
                        $writer->white(implode(', ', $args))
                    );
                    $writer->line('    You may forgot to use double quotes?');
                    break;
                case 'boolean-type':
                    $writer->line(
                        $writer->red($count . '. Received value on boolean parameter: ') . 
                        $writer->white($key)
                    );
                    $writer->line('    Passed Value: ' . implode(', ', $args));
                    break;
                case 'string-type':
                case 'array-type':
                    $writer->line(
                        $writer->red($count . '. Received no value for parameter: ') . 
                        $writer->white($key)
                    );
                    $writer->line('    This parameter requires a value');
                    break;
                case 'unmeet':
                    $writer->line(
                        $writer->red($count . '. Received ' . count($args) . ' opposing parameters: ')
                    );
                    $writer->line('    Use only one of those: ' . implode(', ', $args));
                    break;
                case 'required':
                    $writer->line(
                        $writer->red($count . '. Required parameter is missing: ') .
                        $writer->white($key)
                    );
                    $writer->line('    Please check the help text for more details');
                    break;
                default:
                $writer->line(
                        $writer->red($count . '. Unknown error for parameter: ') .
                        $writer->white($key ?? 'unknown parameter')
                    );
                    $writer->line('    Please check the help text for more details');
                    break;
            }

            $count++;
        }
    }

    /**
     * Execute Command Request
     *
     * @return void
     */
    public function execute(?array $args = null)
    {
        global $argv; 

        // Get Arguments Array
        if (is_null($args)) {
            $args = $argv;
        }
        $this->originalArgs = $args;
        $this->script = array_shift($args);

        // No Arguments or No Namespace -> Call Help
        if (empty($args)) {
            $args = ['help:index'];
        } else if(count($args) === 1 && strpos($args[0], ':') === false) {
            $args = ['help:list', '--namespace', $args[0]];
        }

        // Get Command & Method
        $command = array_shift($args);

        if (!array_key_exists($command, self::$commands)) {
            $args = ['--command', $command, ...$args];
            $command = 'help:unknown';
        }

        $method = substr($command, strpos($command, ':')+1);

        // Check Command Method
        $class = self::$commands[$command];

        if (!method_exists($class, $method)) {
            $args = ['--namespace', $command, '--method', $method, ...$args];
            $command = 'help:unknown';
            $class = self::$commands[$command];
        }

        // Check for special --help -h parameters
        if (in_array('--help', $args) || in_array('-h', $args)) {
            $args = ['--command', $command, '--method', $method, ...$args];
            $command = 'help:describe';
            $method = 'describe';
            $class = self::$commands[$command];
        }

        // Get Command Description
        $desc = $class::describe()[$command] ?? [];

        // Parse Parameters
        $params = [];
        $config = [];
        $errors = $this->parseArguments($desc, $args, $params, $config);

        // Set Current Data
        $this->command = $command;
        $this->args = $args;
        $this->params = $params;
        $this->config = $config;

        // Check Errors
        if (!empty($errors)) {
            $this->printErrors($errors);
            die();
        }

        // Call Command
        $writer = new Writer;
        if ($desc['header'] ?? true) {
            $writer->line("");
            $writer->line($writer->dim($writer->yellow('Execute command:')));
            $writer->line($writer->dim("    php " . implode(' ', $this->originalArgs)));
            $writer->line("");
        }

        $realMethod = $desc['method'] ?? $method;
        (new $class($this, $writer))->$realMethod($params, $config);
        
        if ($desc['footer'] ?? true) {
            $time = round(microtime(true) - citrus()->getStartTime(), 4);
            $writer->line("");

            if (citrus()->getContext() !== 'production') {
                $writer->line("");
                $writer->line("");
                $writer->line($writer->dim($writer->yellow('Command executed in ' . $time . ' seconds')));
            }
        }
        
    }

}
