<?php
//StrictType
declare(strict_types = 1);

/*
 * Ness
 * Acl component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */

namespace Ness\Component\Acl\Traits;

/**
 * Interaction with the filesystem for loader
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
trait FileLoaderTrait
{
    
    /**
     * Check all files
     *
     * @throws \LogicException
     *   When a file or a directory does nos exist
     */
    private function checkFiles(): void
    {
        foreach ($this->files as $file ) {
            if(!\file_exists($file))
                throw new \LogicException("This file '{$file}' is neither a directory or a file");
        }
    }
    
    /**
     * Build files into local file property to be used on next call of load
     */
    private function buildFiles(): void
    {
        foreach ($this->files as $index => $file) {
            if(\is_dir($file)) {
                foreach (new \DirectoryIterator($file) as $file) {
                    if($file->isDot()) continue;
                    $this->files[$file->getBasename(".php")] = $file->getPathname();
                }
            } else {
                $this->files[(new \SplFileInfo($file))->getBasename(".php")] = $file;
            }
            
            unset($this->files[$index]);
        }
    }
    
}