<?php
//StrictType
declare(strict_types = 1);

/*
 * Zoe
 * Acl component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */

namespace Zoe\Component\Acl\Resource\Loader;

use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\Exception\ResourceNotFoundException;

/**
 * Load resource from a set of PHP files returning a resource instance.
 * Resource name is based on file name
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NativeResourceLoader implements ResourceLoaderInterface
{
    
    /**
     * Path to resource files, indexed by resource name
     * 
     * @var string[]
     */
    private $files;
    
    /**
     * Initialize loader
     * 
     * @param array $files
     *   Paths to resource files
     */
    public function __construct(array $files)
    {
        foreach ($files as $file)
            $this->files[$this->extractResourceFileName($file)] = $file;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\Loader\ResourceLoaderInterface::load()
     */
    public function load(string $resource): ResourceInterface
    {
        if( ($notRegistered = (!isset($this->files[$resource]))) || !\is_file($this->files[$resource]) ) {
            $message = ($notRegistered) 
                ? "This resource '{$resource}' cannot be loaded over '" . __CLASS__ . "' resource load as it is not registered into given files" 
                : "This resource '{$resource}' cannot be loaded over '" . __CLASS__ . "' as given file : '{$this->files[$resource]}' does not exist";
            
            throw new ResourceNotFoundException($message);
        }
        
        try {
            $loaded = self::includeFile($this->files[$resource]);            
        } catch (\TypeError $e) {
            throw new ResourceNotFoundException("This file '{$this->files[$resource]}' MUST resource an instance of ResourceInterface");
        }
        
        if($loaded->getName() !== $resource) {
            throw new ResourceNotFoundException("Resource name '{$resource}' from file '{$this->files[$resource]}' from loaded resource '{$loaded->getName()}' does not correspond");
        }
        
        return $loaded;
    }

    /**
     * Extract a resource name from a php file
     * 
     * @param string $file
     *   File name
     * 
     * @return string
     *   Resource name
     */
    private function extractResourceFileName(string $file): string
    {
        return \substr($file, \strrpos($file, "/") + 1, - 4);
    }
    
    /**
     * Include a file to prevent override of local variable and access to this
     * 
     * @param string $file
     *   File name
     * 
     * @return ResourceInterface
     *   Resource representation
     */
    private static function includeFile(string $file): ResourceInterface
    {
        return include $file;
    }
}
