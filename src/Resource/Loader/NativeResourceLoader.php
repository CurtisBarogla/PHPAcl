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
use Zoe\Component\Acl\Resource\ResourceCollectionInterface;

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
        return $this->get($resource, ResourceInterface::class);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\Loader\ResourceLoaderInterface::loadCollection()
     */
    public function loadCollection(string $collection): ResourceCollectionInterface
    {
        return $this->get($collection, ResourceCollectionInterface::class);
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
     * Check if a resource/collection is loadable
     * 
     * @param string $resource
     *   Resource or collection name
     * 
     * @throws ResourceNotFoundException
     *   If resource/collection cannot be loaded
     */
    private function check(string $resource): void
    {
        if( ($notRegistered = (!isset($this->files[$resource]))) || !\is_file($this->files[$resource]) ) {
            $message = ($notRegistered)
            ? "This resource/collection '{$resource}' cannot be loaded over '" . __CLASS__ . "' resource load as it is not registered into given files"
            : "This resource/collection '{$resource}' cannot be loaded over '" . __CLASS__ . "' as given file : '{$this->files[$resource]}' does not exist";
            
            throw new ResourceNotFoundException($message);
        }
    }
    
    /**
     * Check if the resource/collection name correspond to a valid type and if loaded resource/collection name correspond to the filename given
     * 
     * @param string $resource
     *   Resource/Collection name which loaded resource/collection must correspond
     * @param ResourceInterface|ResourceCollectionInterface $loaded
     *   Resource/Collection loaded
     * @param string $type
     *   Type restricted
     * 
     * @throws ResourceNotFoundException
     *   If a resource/collection does not correspond to the valid type or given filename does not correspond to loaded resource/collection
     */
    private function checkLoaded(string $resource, $loaded, string $type): void
    {
        if(!$loaded instanceof $type) {
            throw new ResourceNotFoundException("This file '{$this->files[$resource]}' MUST resource an instance of '{$type}'");
        }
        
        $name = ($loaded instanceof ResourceInterface) ? $loaded->getName() : $loaded->getIdentifier();
        if($name !== $resource) {
            throw new ResourceNotFoundException("Resource/Collection name '{$resource}' from file '{$this->files[$resource]}' from loaded resource/collection '{$name}' does not correspond");
        }
    }
    
    /**
     * Process verifications and load a resource/collection
     * 
     * @param string $resource
     *   Resource/collection
     * @param string $type
     *   Type restricted
     *   
     * @return ResourceInterface|ResourceCollectionInterface
     *   Resource/Collection verified and loaded
     */
    private function get(string $resource, string $type)
    {
        $this->check($resource);
        
        $loaded = self::includeFile($this->files[$resource]);
        
        $this->checkLoaded($resource, $loaded, $type);
        
        return $loaded;
    }
    
    /**
     * Include a file to prevent override of local variable and access to this
     * 
     * @param string $_file
     *   File name
     * 
     * @return ResourceInterface|ResourceCollectionInterface
     *   Resource/ResourceCollection representation
     */
    private static function includeFile(string $_file)
    {
        return include $_file;
    }
    
}
