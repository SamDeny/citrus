<?php declare(strict_types=1);

namespace Citrus\Console;

class Writer
{

    /**
     * Last printed message
     *
     * @var string|null
     */
    protected ?string $lastMessage = null;

    /**
     * Write Line
     *
     * @param string $message
     * @return void
     */
    public function write(string $message = '')
    {
        $this->lastMessage = $message;
        print($message);
    }

    /**
     * Write a single Line
     *
     * @param string $message
     * @return void
     */
    public function line(string $message = '')
    {
        $this->lastMessage = $message . "\n";
        print($message . "\n");
    }

    /**
     * Write multiple lines
     *
     * @param array $messages
     * @param int $indent
     * @return void
     */
    public function lines(array $messages, int $indent = 0)
    {
        foreach ($messages AS $message) {
            $this->line(str_repeat(' ', $indent) . $message);
        }
    }

    /**
     * Print a question and ask for input.
     *
     * @param string $question
     * @param bool $hideInput
     * @return string
     */
    public function input(string $question = '', bool $hideInput = false): string
    {
        if (!empty($question) && $question[strlen($question)-1] !== ' ') {
            $question .= ' ';
        }


        //@todo find a Windows / stty-less solution, which does not require 
        // using a different language (VBScript) or PowerShell or an executable 
        // file... or any kind of hacks.
        if ($hideInput) {
            if (!empty($question)) {
                print($question);
            }
            
            system('stty -echo');
            $answer = trim(fgets(STDIN));
            system('stty echo');

            return $answer;
        } else {
            return readline($question);
        }
    }

    /**
     * Execute console command and print output
     *
     * @param string $command
     * @param int $indent
     * @return void
     */
    public function command(string $command, int $indent = 0)
    {
        $content = []; 
        exec($command, $content);
        $this->lines($content, $indent);
    }

    /**
     * Write List
     *
     * @param array $list The list to be printed.
     * @param integer|null $length Adjust the length-space of the key item.
     * @param integer $indent The indentation between key and value text.
     * @param integer $startIndent The starting intendation before the key,
     * @param string $keyRules
     * @param string $valRules
     * @return void
     */
    public function list(
        array   $list, 
        ?int    $length = null, 
        int     $indent = 2, 
        int     $startIndent = 0, 
        string  $keyRules = '',  
        string  $valRules = ''
    ) {
        if (is_null($length)) {
            $length = array_reduce(array_keys($list), fn($carry, $item) => strlen($item) > $carry? strlen($item): $carry);
        }

        foreach ($list AS $key => $value) {
            $output  = str_repeat(' ', $startIndent);
            $output .= $this->apply(str_pad($key, $length, ' ', \STR_PAD_RIGHT), $keyRules);
            $output .= str_repeat(' ', $indent);
            $output .= $this->apply($value, $valRules);
            $this->line($output);
        }
    }

    /**
     * Write Table
     *
     * @param array $headers
     * @param array $rows
     * @return void
     */
    public function table(array $headers, array $rows, array $alignments = [])
    {
        $cells = [];
        $lines = [];
        $align = [
            'left'      => \STR_PAD_RIGHT,  // Spacing right -> text left
            'right'     => \STR_PAD_LEFT,   // Spacing left  -> text right
            'center'    => \STR_PAD_BOTH,
            'both'      => \STR_PAD_BOTH,
        ];

        // STRval
        $headers = array_map('strval', $headers);
        array_walk_recursive($rows, fn(&$val) => $val = strval($val));

        // Calculate Cell lengths
        foreach ($headers AS $i => $head) {
            $cells[$i] = strlen($head);
        }

        foreach ($rows AS $row) {
            foreach ($row AS $i => $cell) {
                if ($i > count($cells)-1) {
                    $cells[$i] = 0;
                }
                $cells[$i] = strlen($cell) > $cells[$i]? strlen($cell): $cells[$i];
            }
        }

        // Print Table Header
        $header = '';
        $separator = '';
        foreach ($cells AS $i => $length) {
            $header .= ($i == 0? '| ': '') . str_pad($headers[$i] ?? '', $length, ' ') . ' | ';
            $separator .= ($i == 0? '| ': '') . str_pad('', $length, '-') . ' | ';
        }
        $lines[] = $header;
        $lines[] = $separator;

        // Print Table Rows
        foreach ($rows AS $row) {
            $line = '';
            foreach ($cells AS $i => $length) {
                $alignment = $alignments[$i] ?? 'left';
                $line .= ($i == 0? '| ': '') . str_pad($row[$i] ?? '', $length, ' ', $align[$alignment]) . ' | ';
            }
            $lines[] = $line;
        }


        $this->lines($lines);
    }

    /**
     * Apply Rules
     *
     * @param string $message
     * @param string $rules
     * @return string
     */
    public function apply(string $message, string $rules = ""): string
    {
        return $rules . $message . "\x1b[0m";
    }

    /**
     * Reset all applied text styles
     *
     * @param string $message
     * @return string
     */
    public function reset(string $message)
    {
        return "\x1b[0m" . $message . "\x1b[0m";
    }

    /**
     * Change Text Style to bright
     *
     * @param string $message
     * @return string
     */
    public function bright(string $message): string
    {
        return "\x1b[1m" . $message . "\x1b[0m";
    }

    /**
     * Change Text Style to dim
     *
     * @param string $message
     * @return string
     */
    public function dim(string $message): string
    {
        return "\x1b[2m" . $message . "\x1b[0m";
    }

    /**
     * Change Text Style to underscore
     *
     * @param string $message
     * @return string
     */
    public function underscore(string $message): string
    {
        return "\x1b[4m" . $message . "\x1b[0m";
    }

    /**
     * Change Text Style to blink
     *
     * @param string $message
     * @return string
     */
    public function blink(string $message): string
    {
        return "\x1b[5m" . $message . "\x1b[0m";
    }

    /**
     * Change Text Style to reverse
     *
     * @param string $message
     * @return string
     */
    public function reverse(string $message): string
    {
        return "\x1b[7m" . $message . "\x1b[0m";
    }

    /**
     * Change Text Style to hidden
     *
     * @param string $message
     * @return string
     */
    public function hidden(string $message): string
    {
        return "\x1b[8m" . $message . "\x1b[0m";
    }

    /**
     * Change Foreground Colour to black
     *
     * @param string $message
     * @return string
     */
    public function black(string $message): string
    {
        return "\x1b[30m" . $message . "\x1b[0m";
    }

    /**
     * Change Foreground Colour to red
     *
     * @param string $message
     * @return string
     */
    public function red(string $message): string
    {
        return "\x1b[31m" . $message . "\x1b[0m";
    }

    /**
     * Change Foreground Colour to green
     *
     * @param string $message
     * @return string
     */
    public function green(string $message): string
    {
        return "\x1b[32m" . $message . "\x1b[0m";
    }

    /**
     * Change Foreground Colour to yellow
     *
     * @param string $message
     * @return string
     */
    public function yellow(string $message): string
    {
        return "\x1b[33m" . $message . "\x1b[0m";
    }

    /**
     * Change Foreground Colour to blue
     *
     * @param string $message
     * @return string
     */
    public function blue(string $message): string
    {
        return "\x1b[34m" . $message . "\x1b[0m";
    }

    /**
     * Change Foreground Colour to magenta
     *
     * @param string $message
     * @return string
     */
    public function magenta(string $message): string
    {
        return "\x1b[35m" . $message . "\x1b[0m";
    }

    /**
     * Change Foreground Colour to cyan
     *
     * @param string $message
     * @return string
     */
    public function cyan(string $message): string
    {
        return "\x1b[36m" . $message . "\x1b[0m";
    }

    /**
     * Change Foreground Colour to white
     *
     * @param string $message
     * @return string
     */
    public function white(string $message): string
    {
        return "\x1b[37m" . $message . "\x1b[0m";
    }

    /**
     * Change Foreground Colour to crimson
     *
     * @param string $message
     * @return string
     */
    public function crimson(string $message): string
    {
        return "\x1b[38m" . $message . "\x1b[0m";
    }

    /**
     * Change Background Color to black
     *
     * @param string $message
     * @return string
     */
    public function bgBlack(string $message): string
    {
        return "\x1b[40m" . $message . "\x1b[0m";
    }

    /**
     * Change Background Color to red
     *
     * @param string $message
     * @return string
     */
    public function bgRed(string $message): string
    {
        return "\x1b[41m" . $message . "\x1b[0m";
    }

    /**
     * Change Background Color to green
     *
     * @param string $message
     * @return string
     */
    public function bgGreen(string $message): string
    {
        return "\x1b[42m" . $message . "\x1b[0m";
    }

    /**
     * Change Background Color to yellow
     *
     * @param string $message
     * @return string
     */
    public function bgYellow(string $message): string
    {
        return "\x1b[43m" . $message . "\x1b[0m";
    }

    /**
     * Change Background Color to blue
     *
     * @param string $message
     * @return string
     */
    public function bgBlue(string $message): string
    {
        return "\x1b[44m" . $message . "\x1b[0m";
    }

    /**
     * Change Background Color to magenta
     *
     * @param string $message
     * @return string
     */
    public function bgMagenta(string $message): string
    {
        return "\x1b[45m" . $message . "\x1b[0m";
    }

    /**
     * Change Background Color to cyan
     *
     * @param string $message
     * @return string
     */
    public function bgCyan(string $message): string
    {
        return "\x1b[46m" . $message . "\x1b[0m";
    }

    /**
     * Change Background Color to white
     *
     * @param string $message
     * @return string
     */
    public function bgWhite(string $message): string
    {
        return "\x1b[47m" . $message . "\x1b[0m";
    }

    /**
     * Change Background Color to crimson
     *
     * @param string $message
     * @return string
     */
    public function bgCrimson(string $message): string
    {
        return "\x1b[48m" . $message . "\x1b[0m";
    }

}
