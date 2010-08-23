<?php

class AutoRun {
    static public $lastTime;
    
    public static function main($argc, array $argv = array()) {
        chdir(".");
        $lastTime = time();
        $dir      = new DirectoryIterator(".");
        $autorun  = new AutoRun($lastTime, $dir);
        
        if ($argc === 0) {
            self::usage();
            exit(1);
        }
        
        $argv = array_slice($argv, 1);
        $command  = implode(' ', $argv);

        while(1) {
            $autorun->run($command);
        }
    }
    
    public static function usage() {
        echo "
AutoRun 1.0.0 by Marcello Duarte.

Usage: autorun <command>
";
    }
    
    public function __construct($lastTime, DirectoryIterator $testDir) {
        self::$lastTime = $lastTime;
        $this->testDir = $testDir;
    }
    
    public function run($command) {
        foreach ($this->testDir as $file){
            if ($file->isDot()) continue;

            if ($file->isDir()) {
                $this->recursivelyRun($file, $command);
                continue;
            }
            
            $this->runCommandIfThisFileWasModified($file, $command);
        }
    }
    
    /**
     * For some reason I can't use the same Directory Iterator object. I needed
     * a brand new Directory Iterator due to some internals of how the SPL class works
     */
    private function recursivelyRun(DirectoryIterator $dir, $command) {
        $this->cloneDirectoryIteratorAndCreateNewAutoRun($dir)
             ->run($command);
    }
    
    /**
     * Cloning with the clone statement wasn't enough. I really need a new object.
     * I create one with from the previous directory's path. 
     */
    private function cloneDirectoryIteratorAndCreateNewAutoRun(DirectoryIterator $dir) {
        $clone = new DirectoryIterator($dir->getPathName());
        return new AutoRun(self::$lastTime, $clone);
    }
    
    private function runCommandIfThisFileWasModified(SplFileInfo $file, $command) {
        if ($this->wasModifiedSincelastRun($file)) {
            $this->clearTerminalAndRun($command);
            $this->resetTime($file);
        }
    }
    
    private function wasModifiedSincelastRun(SplFileInfo $file) {
        return $file->getMTime() > self::$lastTime;
    }
    
    private function clearTerminalAndRun($command) {
        system('clear');
        system($command);
    }
    
    private function resetTime(SplFileInfo $file) {
        self::$lastTime = $file->getMTime();
    }
}

AutoRun::main(count($argv), $argv);
