<?php declare(strict_types=1);

namespace Citrus\Console;

use Citrus\Exceptions\CitrusException;

class Form
{

    /**
     * Form Questions
     *
     * @var array
     */
    protected array $questions = [];

    /**
     * Current Form Position
     *
     * @var string
     */
    protected string $current;

    /**
     * Current Form Data
     *
     * @var array
     */
    protected array $formdata;

    /**
     * Console Writer instance
     *
     * @var Writer
     */
    protected Writer $writer;

    /**
     * Add a new Form Content element
     *
     * @param string $id
     * @param array $content
     * @return bool
     */
    protected function add(string $id, array $content): bool
    {
        if (array_key_exists($id, $this->questions)) {
            //@todo logger
            throw new CitrusException("The passed question id '$id' does already exist.");
            return false;
        } else {
            $this->questions[$id] = $content;
            return true;
        }
    }

    /**
     * Undocumented function
     *
     * @param string $id
     * @param array $content
     * @return bool
     */
    public function select(string $id, array $content): bool
    {
        $content['type'] = __FUNCTION__;
        return $this->add($id, $content);
    }

    /**
     * Undocumented function
     *
     * @param string $id
     * @param array $content
     * @return bool
     */
    public function input(string $id, array $content): bool
    {
        $content['type'] = __FUNCTION__;
        return $this->add($id, $content);
    }

    /**
     * Undocumented function
     *
     * @param string $id
     * @param array $content
     * @return bool
     */
    public function password(string $id, array $content): bool
    {
        $content['type'] = __FUNCTION__;
        return $this->add($id, $content);
    }

    /**
     * Perform Form
     *
     * @return ?array
     */
    public function perform(Writer $writer): ?array
    {
        reset($this->questions);
        $this->current = key($this->questions);
        $this->formdata = [];
        $this->writer = $writer;

        if ($this->handle()) {
            return $this->formdata;
        }
    }

    /**
     * Handle current Form Content
     *
     * @return bool
     */
    protected function handle(): bool
    {
        $content = $this->questions[$this->current];
        $type = $content['type'] ?? 'input';

        // Write Label
        if (isset($content['label'])) {
            if (is_string($content['label'])) {
                $this->writer->line($this->writer->yellow($content['label']));
            } else if (is_array($content['label'])) {
                $this->writer->lines($content['label']);
            } else if (is_callable($content['label'])) {
                call_user_func($content['label'], $this->writer, $this->formdata);
            }
            if (!isset($content['help']) && $type !== 'select') {
                $this->writer->line();
            }
        }

        // Write Help Text
        if (isset($content['help'])) {
            if (is_string($content['help'])) {
                $this->writer->line($this->writer->dim($content['help']));
            } else if (is_array($content['help'])) {
                $this->writer->lines($content['help']);
            } else if (is_callable($content['help'])) {
                call_user_func($content['help'], $this->writer, $this->formdata);
            }
            if ($type !== 'select') {
                $this->writer->line();
            }
        }

        // Write Options
        if ($type === 'select') {
            $disabled = $content['disabled'] ?? [];
            foreach ($content['options'] AS $num => $option) {
                if (in_array($num, $disabled)) {
                    $this->writer->line($this->writer->dim('  [x] ' . $option));
                } else {
                    $this->writer->line("  [$num] $option");
                }
            }
            $this->writer->line();
        }

        // Receive Value
        $question = $this->writer->green('Select');
        if (isset($content['default'])) {
            $question .= $this->writer->green($this->writer->dim(' (default: '. $content['default'] .')'));
        }
        $question .= $this->writer->green(': ');

        $data = trim($this->writer->input($question, $type === 'password'));
        $input = $data;
        $data = empty($data)? ($content['default'] ?? false): $data;

        // Validate
        if ($type === 'select') {
            if (!(array_key_exists(intval($data), $content['options']) && !in_array(intval($data), $content['disabled'] ?? []))) {
                $data = false;
            }
        } else if (isset($content['validate'])) {
            $data = call_user_func($content['validate'], $data, $this->formdata, $this->writer);
        }

        // OnInvalid
        if ($data === false) {
            if (isset($content['onInvalid'])) {
                $action = call_user_func($content['onInvalid'], $this->writer, $input);
            } else {
                $this->writer->line();
                $this->writer->line($this->writer->red('The passed value was invalid'));
                $action = 'repeat';
            }
        } else {
            $this->formdata[$this->current] = $data;

            $this->writer->line();
            if (isset($content['onValid'])) {
                $action = call_user_func($content['onValid'], $this->writer, $data);
            } else {
                //@todo
            }
        }

        // Handle Action
        if ($action === 'repeat') {
            return $this->handle();
        } else if (strpos($action, 'goto:') === 0) {
            $question = substr($action, 5);
            if (!array_key_exists($question, $this->questions)) {
                throw new CitrusException("The passed question '$question' does not exist.");
            }
            $this->current = $question;
            return $this->handle();
        } else if ($action === 'finish') {
            return true;
        }
    }

}
