<?php

namespace EvolutionCMS\Minify;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\{Cache, View};
use Less_Parser;
use MatthiasMullie\Minify\{CSS, JS};
use JShrink\Minifier;

class Minify
{
    private $filesystem;

    private $targetPath;
    private $hashesFile = MODX_BASE_PATH . 'assets/cache/hashes.pageCache.php';

    protected $autoComment = "/* Do not edit this file manually, it is an autocompilation result. Edit *.less files instead. */\n";

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->setTargetPath(config('minify.path', 'theme/compiled'));
    }

    public function setTargetPath($path)
    {
        $this->targetPath = trim($path, '/') . '/';

        if (!is_dir(MODX_BASE_PATH . $this->targetPath)) {
            $this->filesystem->makeDirectory(MODX_BASE_PATH . $this->targetPath, 0755, true);
        }
    }

    protected function isFilesChanged($files = [])
    {
        $isChanged = false;

        if (is_readable($this->hashesFile)) {
            $hashes = include $this->hashesFile;
        } else {
            $hashes = [];
            $isChanged = true;
        }

        foreach ($files as $file) {
            $filePath = MODX_BASE_PATH . $file;

            if (!is_readable($filePath)) {
                continue;
            }

            $time = filemtime($filePath);

            if (!isset($hashes[$file]) || $hashes[$file]['time'] != $time) {
                $isChanged = true;
                $hashes[$file] = [
                    'time' => $time,
                    'hash' => hash('md5', file_get_contents($filePath)),
                ];
                continue;
            }

            $hash = hash('md5', file_get_contents($filePath));

            if ($hashes[$file]['hash'] != $hash) {
                $isChanged = true;
                $hashes[$file]['hash'] = $hash;
            }
        }

        if ($isChanged) {
            file_put_contents($this->hashesFile, '<?php return ' . var_export($hashes, true) . ';');
        }

        return $isChanged;
    }

    public function compileLessFiles($files, $vars = [])
    {
        $result = [];
        $groups = $this->groupFilesByNamePart($files);

        foreach ($groups as $files) {
            $file     = array_shift($files);
            $filename = pathinfo($file, PATHINFO_FILENAME);
            $cssFile  = $filename . '.css';
            $mapFile  = $this->targetPath . $cssFile . '.map';

            $parser = new Less_Parser([
                'compress'  => false,
                'sourceMap' => true,
                'sourceMapWriteTo' => MODX_BASE_PATH . $mapFile,
                'sourceMapURL'     => MODX_BASE_URL . $mapFile,
            ]);

            $this->setVariablesFromFiles($parser, $vars);

            $parser->parseFile(MODX_BASE_PATH . $file, $this->targetPath);

            foreach ($files as $file) {
                $parser->parseString('@import "' . $file . '";');
            }

            file_put_contents(MODX_BASE_PATH . $this->targetPath . $cssFile, $this->autoComment . $parser->getCss());

            $result[] = $this->targetPath . $cssFile;
        }

        return $result;
    }

    public function compileSassFiles($files)
    {
        return [];
    }

    public function minifyStyles($files, $minify = true)
    {
        if ($minify) {
            $css = new CSS($files);
            $target = $this->targetPath . "minified.css";
            $css->minify($target);
            $files = [$target];
        }

        return View::make('minify::result', [
            'css' => $files,
        ])->toHtml();
    }

    public function minifyScripts($files, $minify = true)
    {
        if ($minify) {
            $js = new JS($files);
            $target = $this->targetPath . "minified.js";
            $js->minify($target);
            $files = [$target];
        }

        return View::make('minify::result', [
            'js' => $files,
        ])->toHtml();
    }

    protected function setVariablesFromFiles(Less_Parser $parser, $files)
    {
        foreach ($files as $file) {
            if (is_readable(MODX_BASE_PATH . $file)) {
                $json = json_decode(file_get_contents(MODX_BASE_PATH . $file), true);
                $parser->ModifyVars($json);
            }
        }
    }

    protected function expandFiles($raw)
    {
        $result = [];

        foreach ($raw as $mask) {
            if (strpos($mask, '*')) {
                foreach (glob($mask) as $file) {
                    $result[] = $file;
                }
            } else if (is_readable(MODX_BASE_PATH . $mask)) {
                $result[] = $mask;
            }
        }

        return $result;
    }

    protected function groupFilesByExtension($raw)
    {
        $result = [];

        foreach ($raw as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            if (!isset($result[$extension])) {
                $result[$extension] = [];
            }

            $result[$extension][] = $file;
        }

        $result = array_map(function($group) {
            return array_unique($group);
        }, $result);

        return $result;
    }

    protected function processFileGroups($groups)
    {
        $groups['css'] = $groups['css'] ?? [];

        if (!empty($groups['sass'])) {
            $result = $this->compileSassFiles($groups['sass'], $groups['json'] ?? []);
            $groups['css'] = array_merge($groups['css'], $result);
        }

        if (!empty($groups['less'])) {
            $result = $this->compileLessFiles($groups['less'], $groups['json'] ?? []);
            $groups['css'] = array_merge($groups['css'], $result);
        }

        $isLoggedIn = evo()->isLoggedIn('mgr');
        $output = '';

        if (!empty($groups['css'])) {
            $output .= $this->minifyStyles($groups['css'], !$isLoggedIn);
        }

        if (!empty($groups['js'])) {
            $output .= $this->minifyScripts($groups['js'], !$isLoggedIn);
        }

        return $output;
    }

    protected function groupFilesByNamePart($raw)
    {
        $parents = $children = [];

        foreach ($raw as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);

            if (preg_match('/^_(.+?)_/', $filename, $matches)) {
                if (!isset($children[$matches[1]])) {
                    $children[$matches[1]] = [];
                }

                $children[$matches[1]][] = $file;
            } else if (strpos($filename, '_') !==0) {
                $parents[$filename] = [$file];
            }
        }

        foreach (array_keys($parents) as $parent) {
            if (isset($children[$parent])) {
                $parents[$parent] = array_merge($parents[$parent], $children[$parent]);
            }
        }

        return array_values($parents);
    }

    public function process($files = [])
    {
        $hash = 'minified:' . md5(implode(':', $files)) . evo()->isLoggedIn('mgr');

        $files = $this->expandFiles($files);
        $groups = $this->groupFilesByExtension($files);

        if ($this->isFilesChanged($files)) {
            $output = $this->processFileGroups($groups);
            Cache::put($hash, $output);
            return $output;
        }

        return Cache::rememberForever($hash, function() use ($groups) {
            return $this->processFileGroups($groups);
        });
    }
}
