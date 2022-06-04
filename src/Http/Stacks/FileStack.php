<?php declare(strict_types=1);

namespace Citrus\Http\Stacks;

use Citrus\Concerns\Stack;
use Citrus\Http\File;

class FileStack extends Stack 
{

    /**
     * @inheritDoc
     */
    protected bool $readonly = true;

    /**
     * Create a new FileStack.
     *
     * @param array $files
     */
    public function __construct(array $files)
    {
        $stack = [];

        foreach ($files AS $input => $data) {
            $temp = [];

            if (is_array($data['name'])) {
                for ($i = 0, $l = count($data['name']); $i < $l; $i++) {
                    $temp[strval($i)] = new File([
                        "name" => $data['name'][$i],
                        "type" => $data['type'][$i],
                        "tmp_name" => $data['tmp_name'][$i],
                        "error" => $data['error'][$i],
                        "size" => $data['size'][$i]
                    ]);
                }
            } else {
                $temp = new File($data);
            }

            $stack[$input] = $temp;
        }

        $this->stack = $stack;
        $this->source = $files;
    }

}
