<?php declare(strict_types=1);

namespace Citrus\Console\Commands;

use Citrus\Console\Console;
use Citrus\Console\Writer;
use Citrus\Contracts\CommandContract;

class AboutCommand implements CommandContract
{

    /**
     * Describe the command including all available methods and arguments.
     *
     * @return array
     */
    static public function describe(): array
    {
        return [
            'about:app'         => [
                'label' => 'Prints some details about your Citrus app',
                'args'  => []
            ],
            'about:composer'    => [
                'label' => 'Prints the main composer.json file content',
                'args'  => [
                    'dependencies'      => [
                        'type'      => 'boolean',
                        'short'     => 'dep',
                        'label'     => 'Limits the output to the dependencies'
                    ],
                    'dev-dependencies'  => [
                        'type'      => 'boolean',
                        'short'     => 'ddep',
                        'label'     => 'Limits the output to the devDependencies'
                    ]
                ]
            ],
            'about:hello'       => [
                'label' => 'Prints Hello World or something similar',
                'args'  => [
                    'custom'    => [
                        'type'  => 'array',
                        'short' => 'c',
                        'label' => 'Pass a custom text to be printed',
                        'mods'  => [
                            'black'     => "Print this text part in black",
                            'red'       => "Print this text part in red",
                            'green'     => "Print this text part in green",
                            'yellow'    => "Print this text part in yellow",
                            'blue'      => "Print this text part in blue",
                            'magenta'   => "Print this text part in magenta",
                            'cyan'      => "Print this text part in cyan",
                            'white'     => "Print this text part in white",
                            'crimson'   => "Print this text part in crimson"
                        ]
                    ],
                    'uppercase' => [
                        'type'      => 'boolean',
                        'short'     => 'u',
                        'unmeet'    => 'lowercase',
                        'label'     => 'Uppercase the passed text'
                    ],
                    'lowercase' => [
                        'type'      => 'boolean',
                        'short'     => 'l',
                        'unmeet'    => 'uppercase',
                        'label'     => 'Lowercase the passed text'
                    ]
                ]
            ],
            'about:version'     => [
                'label' => 'Prints the current version of your Citrus app',
                'args'  => []
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
     * Command about:app
     *
     * @return void
     */
    public function app(array $params = [], array $config = [])
    {

    }

    /**
     * Command about:hello
     *
     * @return void
     */
    public function hello(array $params = [], array $config = [])
    {
        $content = 'Hello World';

        // Format Text
        $format = function($string) use ($params) {
            if ($params['uppercase'] ?? null) {
                return strtoupper($string);
            }
            if ($params['lowercase'] ?? null) {
                return strtolower($string);
            }
            return $string;
        };
        $content = $format($content);

        // Set Custom Text
        if (isset($params['custom'])) {
            $result = [];

            $colours = $config['custom'] ?? [];
            for ($i = 0, $l = count($params['custom']); $i < $l; $i++) {

                if (isset($colours[$i])) {
                    $result[] = $this->writer->{$colours[$i]}($format($params['custom'][$i]));
                } else {
                    $result[] = $format($params['custom'][$i]);
                }
            }

            $content = implode(' ', $result);
        }

        // Write Text
        $this->writer->write($content);
    }

    /**
     * Command about:composer
     *
     * @return void
     */
    public function composer(array $params = [], array $config = [])
    {
        $this->writer->command(
            'cd ' . citrus()->rootPath() . ' && composer info'
        );
    }

    /**
     * Command about:version
     *
     * @return void
     */
    public function version(array $params = [], array $config = [])
    {

    }

}
