<?php
// find_unused_files.php
require_once dirname(__DIR__) . '/config/config.php';

class UnusedFileFinder
{
    private $baseDir;
    private $usedFiles = [];

    public function __construct()
    {
        $this->baseDir = ROOT_DIR;
    }

    public function scanFiles()
    {
        echo "Scanning for used files...\n\n";

        // Σάρωση των αρχείων για dependencies
        $this->scanDirectory($this->baseDir . '/src');
        $this->scanDirectory($this->baseDir . '/public');

        // Εύρεση αχρησιμοποίητων αρχείων
        $this->findUnusedFiles();
    }

    private function scanDirectory($dir)
    {
        $files = glob($dir . '/*');

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->scanDirectory($file);
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $this->scanFile($file);
            }
        }
    }

    private function scanFile($file)
    {
        $content = file_get_contents($file);

        // Βρες όλα τα includes, requires, class και interface references
        preg_match_all('/(?:include|require)(?:_once)?\s+[\'"]([^\'"]+)[\'"]/', $content, $includes);
        preg_match_all('/use\s+([\\\\a-zA-Z0-9_]+)/', $content, $uses);
        preg_match_all('/new\s+([a-zA-Z0-9_\\\\]+)/', $content, $news);

        foreach ($includes[1] as $include) {
            $this->usedFiles[] = realpath(dirname($file) . '/' . $include);
        }

        foreach ($uses[1] as $use) {
            $classFile = $this->classToFile($use);
            if ($classFile) {
                $this->usedFiles[] = $classFile;
            }
        }

        foreach ($news[1] as $new) {
            $classFile = $this->classToFile($new);
            if ($classFile) {
                $this->usedFiles[] = $classFile;
            }
        }
    }

    private function classToFile($class)
    {
        $class = ltrim($class, '\\');
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        // Ελέγχουμε για Drivejob namespace
        if (strpos($path, 'Drivejob') === 0) {
            $path = 'src' . DIRECTORY_SEPARATOR . substr($path, 8);
        }

        $file = $this->baseDir . DIRECTORY_SEPARATOR . $path . '.php';

        return file_exists($file) ? realpath($file) : false;
    }

    private function findUnusedFiles()
    {
        echo "Finding unused files...\n\n";

        $allFiles = [];
        $this->getAllPhpFiles($this->baseDir . '/src', $allFiles);

        $unusedFiles = array_diff($allFiles, $this->usedFiles);

        echo "Potentially unused files:\n";
        foreach ($unusedFiles as $file) {
            echo "- " . str_replace($this->baseDir, '', $file) . "\n";
        }
    }

    private function getAllPhpFiles($dir, &$files)
    {
        $items = glob($dir . '/*');

        foreach ($items as $item) {
            if (is_dir($item)) {
                $this->getAllPhpFiles($item, $files);
            } elseif (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
                $files[] = realpath($item);
            }
        }
    }
}

$finder = new UnusedFileFinder();
$finder->scanFiles();
