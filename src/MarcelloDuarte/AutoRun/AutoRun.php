<?php

namespace MarcelloDuarte\AutoRun;

class AutoRun
{
    static public $lastTime;

    public static function main($argc, array $argv = array())
    {
        chdir(".");
        $lastTime = time();
        $dir      = new \DirectoryIterator(".");
        $autorun  = new AutoRun($lastTime, $dir);

        if ($argc <= 1) {
            self::usage();
            exit(1);
        }

        $argv = array_slice($argv, 1);
        $command  = implode(' ', $argv);

        while(1) {
            $autorun->run($command);
        }
    }

    public static function usage()
    {
        echo "
AutoRun 1.0.0 by Marcello Duarte.

Make sure a autorun bash with your command is in the executable PATH
and it has the followig line:

php AutoRun.php <command>

Usage: autorun
            ";
    }

    public function __construct($lastTime, \DirectoryIterator $testDir)
    {
        self::$lastTime = $lastTime;
        $this->testDir = $testDir;
    }

    public function run($command)
    {
        foreach ($this->testDir as $file) {
            if ($file->isDot()) continue;

            if ($file->isDir()) {
                $this->recursivelyRun($file, $command);
                continue;
            }

            if (preg_match('/\.php$/', $file)) {
                $this->runCommandIfThisFileWasModified($file, $command);
            }
        }
    }

    /**
     * For some reason I can't use the same Directory Iterator object. I needed
     * a brand new Directory Iterator due to some internals of how the SPL class works
     */
    private function recursivelyRun(\DirectoryIterator $dir, $command)
    {
        $this->cloneDirectoryIteratorAndCreateNewAutoRun($dir)
            ->run($command);
    }

    /**
     * Cloning with the clone statement wasn't enough. I really need a new object.
     * I create one with from the previous directory's path. 
     */
    private function cloneDirectoryIteratorAndCreateNewAutoRun(\DirectoryIterator $dir)
    {
        $clone = new \DirectoryIterator($dir->getPathName());
        return new AutoRun(self::$lastTime, $clone);
    }

    private function runCommandIfThisFileWasModified(\SplFileInfo $file, $command)
    {
        if ($this->wasModifiedSinceLastRun($file)) {
            $this->clearTerminalAndRun($command);
            $this->updateLastModifiedTime($file);
        }
    }

    private function wasModifiedSinceLastRun(\SplFileInfo $file)
    {
        return filemtime($file->getRealpath()) > self::$lastTime;
    }

    private function clearTerminalAndRun($command)
    {
        system('clear');
        system($command, $result);
        $this->notifyResult($result);
    }

    private function notifyResult($result)
    {
        $hasError = (bool) $result;
        if ($hasError) {
            system('growlnotify -m "Fail" -t "AutoRun" --image ' . IMAGE_RED_PATH);
        } else {
            system('growlnotify -m "Pass" -t "AutoRun" --image ' . IMAGE_GREEN_PATH);
        }
    }

    private function updateLastModifiedTime(\SplFileInfo $modifiedFile)
    {
        self::$lastTime = $modifiedFile->getMTime();
    }
}
