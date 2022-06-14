<?php declare(strict_types=1);

namespace Citrus\Tarnished;

use Citrus\Exceptions\RuntimeException;
use Ds\Map;
use Ds\Set;

class TarnishedCollection
{

    // Disable cache invalidation completely
    const MODE_DISABLED = 0x00;

    // Lazy Mode checks every 24 hours for changes
    const MODE_LAZY = 0x01;
    
    // Sleepy Mode checks every 8 hours for changes
    const MODE_SLEEPY = 0x02;

    // Weak mode checks the target source, once per access / request
    const MODE_WEAK = 0x03;

    // Strong mode checks all sources, once per access / request
    const MODE_STRONG = 0x04;

    // Paranoid mode checks all sources, each time
    const MODE_PARANOID = 0x05;


    /**
     * Tarnished FilePath
     *
     * @var string
     */
    private string $filepath;

    /**
     * Tarnished Cache Invalidation Mode
     *
     * @var int
     */
    private int $mode;

    /**
     * Tarnished File Header Line
     *
     * @var string|null
     */
    private ?string $header = null;

    /**
     * Tarnished Data Cache
     *
     * @var mixed
     */
    private mixed $cache = null;

    /**
     * Tarnished Data Storage
     *
     * @var Map
     */
    private Map $storage;

    /**
     * Tarnished Data Sources
     *
     * @var Map
     */
    private Map $sources;

    /**
     * Tarnished Data Sources Map
     *
     * @var Map
     */
    private Map $sourcesMap;

    /**
     * State if changes happened.
     *
     * @var bool
     */
    private bool $changes = false;

    /**
     * Create a new TarnishedCollection
     *
     * @param string $filepath
     * @param string $mode
     */
    public function __construct(string $filepath, int $mode = TarnishedCollection::MODE_WEAK)
    {
        $this->filepath = $filepath;
        $this->mode = $mode;

        $this->storage = new Map();

        if (file_exists($filepath)) {
            $this->load($filepath);
        } else {
            $this->sources = new Map();
            $this->sourcesMap = new Map();
        }
    }

    /**
     * Check if changes happened
     *
     * @return boolean
     */
    public function hasChanged(): bool
    {
        return $this->changes;
    }

    /**
     * Writes the Collection data to the filepath.
     *
     * @return void
     */
    public function write()
    {
        $results = '[';
        foreach ($this->cache AS $key => $data) {
            if (!is_string($data)) {
                $data = bin2hex(serialize($data));
            } 
            $results .= '\''. $key .'\'=>\''. $data .'\',';
        }
        $results .= '\''. chr(0) .'\'=>unserialize(\''. serialize($this->sources->toArray()) .'\'),';
        $results .= '\''. chr(1) .'\'=>unserialize(\''. serialize($this->sourcesMap->toArray()) .'\')';
        $results .= '];';

        $content  = '<!--t:'. $this->mode .'-->' . "\n";
        $content .= '<?php return '. $results .' ?>';
        file_put_contents($this->filepath, $content);
    }

    /**
     * Load a Tarnished collection file.
     *
     * @param string $filepath
     * @return void
     */
    private function load(string $filepath)
    {
        $line = fgets($handle = fopen($filepath, 'r'));
        fclose($handle);

        // Validate Header
        if (!str_starts_with($line, '<!--t:')) {
            throw new RuntimeException('The passed tarnished cache file is corrupt.');
        }

        // Mode has changed
        if (intval($line[6]) !== $this->mode) {
            $this->sources = new Map();
            $this->sourcesMap = new Map();
            unlink($filepath);
            return;
        }

        //@todo Move Tarnished comment after <?php tag, ex: `<?php /**t:[mode]:[timestamp]:[secure]**/`
        ob_start();

        // Set Data
        $this->header = substr(trim($line), 6, -3);
        $this->cache = require_once $filepath;
        $this->sources = new Map($this->cache[chr(0)]);
        $this->sourcesMap = new Map($this->cache[chr(1)]);

        //@todo Move Tarnished comment after <?php tag, ex: `<?php /**t:[mode]:[timestamp]:[secure]**/`
        ob_end_clean();
    }

    /**
     * Check if one or more source files are expired
     *
     * @param string $source
     * @return bool
     */
    private function isExpired(Map $sources, bool $force = false): bool
    {
        $expired = $sources->filter(function(string $file, array $ts) {
            if ($ts[0] === 0 && file_exists($file)) {
                return true;
            } else if ($ts[0] > 0 && $ts[0] !== filemtime($file)) {
                return true;
            } else {
                return false;
            }
        });
        return $expired->count() > 0;
    }

    /**
     * Validated the passed cache key and returns the value or null.
     *
     * @param string $key
     * @return mixed
     */
    public function validate(string $key): mixed
    {
        if (!isset($this->cache[$key])) {
            return null;
        }
        $sources = $this->cache[chr(0)][$key] ?? [];

        // Cache Invalidation has been disabled or no sources to check
        if ($this->mode === 0x00 || empty($sources)) {
            $this->storage[$key] = fn() => unserialize(hex2bin($this->cache[$key]));
            return $this->storage[$key]();
        }

        // If collection is not paranoid and validation already happened, return.
        if ($this->mode !== 0x05 && isset($this->storage[$key])) {
            return $this->storage[$key]();
        }

        // Source Files
        $sourceFiles = $this->sourcesMap->intersect(new Map(array_flip($sources)));

        // Validate Expiration
        if ($this->mode === 0x01) {
            $sourceFiles = $sourceFiles->filter(fn($_, $val) => time() - $val > 24 * 60 * 60);
            if ($this->isExpired($sourceFiles)) {
                return null;
            }
        } else if ($this->mode === 0x02) {
            $sourceFiles = $sourceFiles->filter(fn($_, $val) => time() - $val > 8 * 60 * 60);
            if ($this->isExpired($sourceFiles)) {
                return null;
            }
        } else if ($this->mode === 0x03) {
            if ($this->isExpired($sourceFiles)) {
                return null;
            }
        } else if ($this->mode === 0x04) {
            if ($this->isExpired($this->sourcesMap)) {
                return null;
            }
        } else if ($this->mode === 0x05) {
            if ($this->isExpired($this->sourcesMap, true)) {
                return null;
            }
        }

        // Store & Return Data
        $this->storage[$key] = fn() => unserialize(hex2bin($this->cache[$key]));
        return $this->storage[$key]();
    }

    /**
     * Receive or Create a Cache item.
     *
     * @param string $key The unique cache item id.
     * @param mixed $collector The collector function to create the cache item.
     * @param array $arguments Additional arguments to be passed to the 
     *              collector function.
     * @return mixed
     */
    public function receive(string $key, mixed $collector, array $arguments = []): mixed
    {
        if (($value = $this->validate($key)) !== null) {
            return $value;
        } else {
            return $this->collect($key, $collector, $arguments);
        }
    }

    /**
     * Collect, Cache and Return the data received from the collector function 
     * or collected via the passed Tarnisher.
     *
     * @param string $key
     * @param mixed $collector
     * @param array $arguments
     * @return mixed
     */
    public function collect(string $key, mixed $collector, array $arguments = []): mixed
    {
        $tarnisher = new Tarnisher($key);
        $resolved = call_user_func_array($collector, [$tarnisher, ...$arguments]);

        if (!$tarnisher->nullable && $tarnisher->data !== null) {
            $filenames = $tarnisher->sources->toArray();

            $sourcesMap = array_combine(
                $filenames,
                array_map(function ($file) {
                    if (file_exists($file)) {
                        return [filemtime($file), time()];
                    } else {
                        return [0, time()];
                    }
                }, $filenames)
            );

            $this->cache[$key] = $tarnisher->data;
            $this->storage[$key] = $tarnisher->data;
            $this->sources[$key] = $filenames;
            $this->sourcesMap = $this->sourcesMap->union(new Map($sourcesMap));
        }

        $this->changes = true;
        return $resolved;
    }

}
