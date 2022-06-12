<?php declare(strict_types=1);

namespace Citrus\Console\Commands;

use Citrus\Console\Console;
use Citrus\Console\Writer;
use Citrus\Contracts\CommandContract;
use Citrus\Utilities\ArrayUtility;

class HelpCommand implements CommandContract
{

    /**
     * Describe the command including all available methods and arguments.
     *
     * @return array
     */
    static public function describe(): array
    {
        return [
            'help'          => [
                'label'     => 'Prints this fancy command list',
                'args'      => [],
                'header'    => false,
            ],
            'help:usage'    => [
                'label'     => 'Prints a more detailed usage help text',
                'args'      => [],
                'header'    => false
            ],
            'help:describe' => [
                'label'     => 'Prints the help text about a specific command',
                'args'      => [
                    'command'   => [
                        'type'      => 'string',
                        'short'     => '',
                        'label'     => 'The desired command to describe',
                        'required'  => true
                    ]
                ],
                'header'    => false,
                'method'    => 'describeMethod'
            ],

            // Pseudo commands, does only exist for internal reason.
            'help:index'    => [
                'label'     => '',
                'args'      => [],
                'hidden'    => true,
                'header'    => false
            ],
            'help:list'     => [
                'label'     => '',
                'args'      => [],
                'hidden'    => true,
                'header'    => false
            ],
            'help:unknown'  => [
                'label'     => '',
                'args'      => [],
                'hidden'    => true,
                'header'    => false
            ]
        ];
    }

    /**
     * Console Instance
     *
     * @var Console
     */
    protected Console $console;

    /**
     * Writer Instance
     *
     * @var Writer
     */
    protected Writer $writer;

    /**
     * Create a new AboutCommand instance.
     *
     * @param Console $console
     * @param Writer $writer
     */
    public function __construct(Console $console, Writer $writer)
    {
        $this->console = $console;
        $this->writer = $writer;
    }

    /**
     * Default 'php citrus' output
     *
     * @param array $params
     * @param array $config
     * @return void
     */
    public function index(array $params = [], array $config = [])
    {
        if (empty($params)) {
            $this->writer->line();
            $this->writer->line($this->writer->yellow("      _ _                  "));
            $this->writer->line($this->writer->yellow("     (_) |                 "));
            $this->writer->line($this->writer->yellow("  ___ _| |_ _ __ _   _ ___ "));
            $this->writer->line($this->writer->yellow(" / __| | __| '__| | | / __|"));
            $this->writer->line($this->writer->yellow("| (__| | |_| |  | |_| \__ \\"));
            $this->writer->line($this->writer->yellow(" \___|_|\__|_|   \__,_|___/"));
            $this->writer->line();
            $this->writer->line($this->writer->dim($this->writer->yellow('Welcome to ')));
            $this->writer->line('        ' . $this->writer->yellow('C I T R U S   C L I'));
            $this->writer->line();
        }

        $this->writer->line();
        $this->writer->line($this->writer->yellow('Basic Usage'));
        $this->writer->line("    php {$this->console->getScript()} [namespace]:[method]");
        $this->writer->line();
        $this->writer->line($this->writer->yellow('List all commands'));
        $this->writer->line("    php {$this->console->getScript()} help");
        $this->writer->line();
        $this->writer->line($this->writer->yellow('View detailed help'));
        $this->writer->line("    php {$this->console->getScript()} help:usage");
    }

    /**
     * Default 'php citrus help' and 'php citrus [namespace]' output
     *
     * @param array $params
     * @param array $config
     * @return void
     */
    public function list(array $params = [], array $config = [])
    {
        if (isset($params['namespace']) && $params['namespace'] === 'help') {
            unset($params['namespace']);
        }
        $namespace = isset($params['namespace'])? $params['namespace']: '[namespace]';

        // Parse Commands List
        $commands = $this->console::getAllCommands();

        //@todo catch on Console class directly
        $namespaces = array_map(fn($val) => ($index = strpos($val, ':')) !== false? substr($val, 0, $index): $val, array_keys($commands));
        if (isset($params['namespace']) && !in_array($params['namespace'], $namespaces)) {
            return $this->unknown(['namespace' => $params['namespace']]);
        }

        // Write Header
        $this->writer->line();
        $this->writer->line($this->writer->yellow('Basic Usage'));
        $this->writer->line("    php {$this->console->getScript()} $namespace:[method]");
        if (!(!empty($params) && isset($params['namespace']))) {
            $this->writer->line();
            $this->writer->line($this->writer->yellow('Command Usage Description'));
            $this->writer->line(
                "    php {$this->console->getScript()} help:describe " . 
                $this->writer->dim("$namespace:[method]")
            );
            $this->writer->line(
                "    php {$this->console->getScript()} $namespace:[method] " . 
                $this->writer->dim('--help|-h')
            );
            $this->writer->line();
        }
        $this->writer->line();

        // Loop Commands
        $lists = [];
        $length = 0;
        foreach ($commands AS $action => $class) {
            $data = $class::describe()[$action];
            if ($data['hidden'] ?? false) {
                continue;
            }

            // Split namespace and key
            if (strpos($action, ':') !== false) {
                [$namespace, $method] = explode(':', $action, 2);
            } else {
                $namespace = $action;
                $method = '';
            }

            // Append
            if (!array_key_exists($namespace, $lists)) {
                $lists[$namespace] = [];
            }
            $lists[$namespace][$action] = $data['label'] ?? 'Unknown';

            // Maximum Key Length
            $length = strlen($action) > $length? strlen($action): $length;
        }
        ksort($lists);

        // Print Commands List
        if (!empty($params) && isset($params['namespace'])) {
            $lists = [
                $params['namespace'] => $lists[$params['namespace']] ?? []
            ];
            $this->writer->line($this->writer->yellow('Available Methods'));
        } else {
            $this->writer->line($this->writer->yellow('Available Commands'));
        }
        foreach ($lists AS $namespace => $list) {
            ksort($list);

            $this->writer->line();
            $this->writer->line($this->writer->yellow($namespace));
            $this->writer->list($list, $length, 4, 4, "", "\x1b[2m");
        }
    }

    /**
     * Default 'php citrus help:usage' output
     *
     * @param array $params
     * @param array $config
     * @return void
     */
    public function usage(array $params = [], array $config = [])
    {

        // Basic Usage
        $this->writer->line();
        $this->writer->line($this->writer->yellow('Basic Usage'));
        $this->writer->line("    php {$this->console->getScript()} [namespace]:[method]");
        $this->writer->line();
        $this->writer->line(
            'Replace the [namespace] and [method] part with the desired action'
        );
        $this->writer->line(
            '... some commands can also be called without passing a [method].'
        );
        $this->writer->line(
            'Run ' .
            $this->writer->yellow('php ' . $this->console->getScript() . ' help') .
            ' to see all available commands'
        );
        $this->writer->line();

        // Additional Parameters
        $this->writer->line();
        $this->writer->line($this->writer->yellow('Additional Parameters'));
        $this->writer->line(
            $this->writer->dim("    php {$this->console->getScript()} [namespace]:[method]") . 
            ' [customvalue]'
        );
        $this->writer->line();
        $this->writer->line(
            'Some commands support passing a value directly after the method part.'
        );
        $this->writer->line();
        $this->writer->line();

        $this->writer->line(
            $this->writer->dim("    php {$this->console->getScript()} [namespace]:[method]") . 
            ' --boolean'
        );
        $this->writer->line(
            $this->writer->dim("    php {$this->console->getScript()} [namespace]:[method]") . 
            ' -bool'
        );
        $this->writer->line();
        $this->writer->lines([
            'Otherwise you can pass additional parameters using the --dash syntax.',
            'Some parameters also supports a -short parameter name with a single -.'
        ]);
        $this->writer->line();
        $this->writer->line();

        $this->writer->line(
            $this->writer->dim("    php {$this->console->getScript()} [namespace]:[method]") . 
            ' --string Custom'
        );
        $this->writer->line(
            $this->writer->dim("    php {$this->console->getScript()} [namespace]:[method]") . 
            ' --string-spaces "My Custom Value"'
        );
        $this->writer->line();
        $this->writer->lines([
            'Depending on the action, parameters may also support a custom value.',
            'Use a space after the parameter name and use quotes when your custom',
            'value contains spaces or other special characters.'
        ]);
        $this->writer->line();
        $this->writer->line();

        $this->writer->line(
            $this->writer->dim("    php {$this->console->getScript()} [namespace]:[method]") . 
            ' --array value1 --array value2'
        );
        $this->writer->line();
        $this->writer->lines([
            'Some actions may also support passing multiple values with the same',
            'parameter name.'
        ]);
        $this->writer->line();
        $this->writer->line();

        // Parameter Modifications
        $this->writer->line($this->writer->yellow('Parameter Modifications'));

        $this->writer->line(
            $this->writer->dim("    php {$this->console->getScript()} [namespace]:[method]") . 
            $this->writer->dim(" --bool") . 
            ':mod'
        );
        $this->writer->line(
            $this->writer->dim("    php {$this->console->getScript()} [namespace]:[method]") . 
            $this->writer->dim(" --string") . 
            ':mod' .
            $this->writer->dim(' "Custom Value"')
        );
        $this->writer->line();
        $this->writer->lines([
            'When configured, action parameters can also contain an additional',
            'parameter modification. You can only use one single modification ',
            'as shown above.'
        ]);

        // Parameter Modifications
        $this->writer->line();
        $this->writer->line();
        $this->writer->line($this->writer->yellow('How do I know which action supports which features?'));
        $this->writer->line();
        $this->writer->line("That's easy, just run one of those commands on the desired action:");
        $this->writer->line();
        $this->writer->line(
            '    ' .
            $this->writer->dim("php {$this->console->getScript()}") .
            " [namespace]:[method] --help" 
        );
        $this->writer->line(
            '    ' .
            $this->writer->dim("php {$this->console->getScript()}") .
            " help:describe [namespace]:[method]"
        );
    }

    /**
     * Default 'php citrus help:describe' output
     *
     * @param array $params
     * @param array $config
     * @return void
     */
    public function describeMethod(array $params = [], array $config = [])
    {
        $commands = $this->console::getAllCommands();
        $command = $commands[$params['command']];
        $describe = $command::describe()[$params['command']];

        // Parse Arguments
        $parameterList = [];
        $hasDefault = null;
        $required = [];
        foreach (($describe['args'] ?? []) as $key => $arg) {
            $type = isset($arg['type'])? $arg['type']: 'unknown';
            $short = isset($arg['short'])? $arg['short']: null;
            $label = isset($arg['label'])? $arg['label']: '';
            $mods = isset($arg['mods'])? $arg['mods']: [];

            // Assign Required Keys
            if ($arg['required'] ?? false) {
                $required[] = $key;
            }

            // Assign Default Short Key
            if (($arg['short'] ?? '') === '') {
                $hasDefault = $key;
                $parameterList[] = [
                    'keys'  => ["[$key]"],
                    'type'  => $type,
                    'label' => $arg['label'],
                    'mods'  => []
                ];
                continue;
            }

            // Prepare Parameters List Data
            $keys = [
                "--{$key}" . (($type === 'string' || $type === 'array')? " [$key]": '')
            ];
            if (!empty($short)) {
                $keys[] = "-{$short}" . (($type === 'string' || $type === 'array')? " [$key]": '');
            }
            $parameterList[] = [
                'keys'  => $keys,
                'type'  => $type,
                'label' => $label,
                'mods'  => $mods
            ];
        }

        // Write Basic Usage
        $this->writer->line();
        $this->writer->line($this->writer->yellow('Basic Usage'));
        $this->writer->write('    php ' . $this->console->getScript());
        $this->writer->write(' ' . $params['command']);
        if ($hasDefault && in_array($hasDefault, $required)) {
            $this->writer->write(" [$hasDefault]");
        }
        $this->writer->line();

        // Write Descriptionv
        $this->writer->line();
        $this->writer->line($this->writer->dim($this->writer->yellow('Command Description')));
        $this->writer->line($this->writer->dim('    ' . $describe['label']));

        // Write Possible Parameters
        $this->writer->line();
        $this->writer->line($this->writer->yellow('Possible Parameters'));

        $maxLength = max(array_map('strlen', ArrayUtility::flat(array_column($parameterList, 'keys'))));

        $count = 0;
        foreach ($parameterList AS $param) {
            if ($count++ > 0) {
                $this->writer->line();
            }
            $this->writer->line(
                '    ' .
                str_pad($param['keys'][0], $maxLength, ' ', \STR_PAD_RIGHT) .
                '    ' .
                $param['label']
            );

            if (count($param['keys']) === 2 || $param['type'] === 'array') {
                $key = $param['keys'][1] ?? '';
                $this->writer->line(
                    '     ' .
                    str_pad($key, $maxLength-1, ' ', \STR_PAD_RIGHT) .
                    '    ' .
                    $this->writer->dim(($param['type'] === 'array'? 'Supports to be used multiple times': ''))
                );
            }

            if (!empty($param['mods'])) {
                $this->writer->line('    ' . $this->writer->green($this->writer->dim('Modifiers')));

                foreach ($param['mods'] AS $mod => $label) {
                    $this->writer->line(
                        '        :' .
                        str_pad($mod, $maxLength-5, ' ', \STR_PAD_RIGHT) .
                        '    ' .
                        $label
                    );
                }
            }
        }
    }

    /**
     * Internal - Output when namespace or method are unknown
     *
     * @param array $params
     * @param array $config
     * @return void
     */
    public function unknown(array $params = [], array $config = [])
    {
        $commands = $this->console::getAllCommands();

        // Writer Header
        $this->writer->line();
        $this->writer->line($this->writer->red('Error occurred while executing command:'));
        $this->writer->line("    php " . implode(' ', $this->console->getOriginalArgs()));

        // Write Error
        if (isset($params['command'])) {
            if (strpos($params['command'], ':') === false) {
                $this->writer->line();
                $this->writer->line(
                    $this->writer->red('The passed namespace does not exist')
                );
                $this->writer->line(
                    $this->writer->dim('    Run ') .
                    $this->console->getScript() . ' help' .
                    $this->writer->dim(' to see all available commands')
                );
            } else {
                $similar = $commands; 
                array_walk($similar, fn(&$val, $key) => $val = levenshtein($key, $params['command']));
                $similar = array_filter($similar, fn($val) => $val <= 5);
                asort($similar, \SORT_NUMERIC);

                $this->writer->line();
                if (empty($similar)) {
                    $this->writer->line($this->writer->red('The command does not exist...'));
                    $this->writer->line(
                        $this->writer->dim('    Run ') .
                        $this->console->getScript() . ' help' .
                        $this->writer->dim(' to see all available commands')
                    );
                } else {
                    $this->writer->line(
                        $this->writer->red('The command does not exist... Did you mean:')
                    );
                    $this->writer->lines(array_keys($similar), 4);
                }
            }
        } else if (isset($params['namespace'])) {
            $this->writer->line();
            $this->writer->line(
                $this->writer->red('The passed namespace does not exist')
            );
            $this->writer->line(
                $this->writer->dim('    Run ') .
                $this->console->getScript() . ' help' .
                $this->writer->dim(' to see all available commands')
            );
        }

    }

}
