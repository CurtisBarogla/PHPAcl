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

namespace Ness\Component\Acl\Resource\Loader\Entry;

use Ness\Component\Acl\Resource\EntryInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Traits\FileLoaderTrait;
use Ness\Component\Acl\Exception\EntryNotFoundException;
use Ness\Component\Acl\Resource\Entry;

/**
 * Load a set of entries from a set of files.
 * Files can be either a single file or a directory containing entries.
 * This implementation supports inheritance. Use "{entry}" as surrounding pattern to declare a parent entry
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class PhpFileEntryLoader implements EntryLoaderInterface
{
    
    use FileLoaderTrait;
    
    /**
     * File setting loadables entries
     * 
     * @var string[]
     */
    private $files;
    
    /**
     * Builded stat of the loader
     * 
     * @var bool
     */
    private $builded = false;
    
    /**
     * Initialize entries loader
     * 
     * @param array $files
     *   All files setting entries. Can be either a single file or a directory
     *   
     * @throws \LogicException
     *   When a file is neither a valid directory or file
     */
    public function __construct(array $files)
    {
        $this->files = $files;
        
        $this->checkFiles();
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface::load()
     */
    public function load(ResourceInterface $resource, string $entry, ?string $processor = null): EntryInterface
    {
        if(!$this->builded) {
            $this->buildFiles();
            
            $this->builded = true;
        }
        
        $file = $this->getFilePatternName($resource, $processor);

        if(!isset($this->files[$file]))
            throw $this->getException($entry, $resource);
        
        $file = $this->files[$file];
            
        $entries = $this->inject($file)();

        if(!\is_array($entries))
            throw new \LogicException("This file '{$file}' MUST return an array representing all entries loadables for resource '{$resource->getName()}'");
        
        foreach ($entries as $index => $current) {
            if($current instanceof EntryInterface && $current->getName() === $entry)
                return $current;
            if(\is_array($current) && $index === $entry) {
                $instance = new Entry($index);
                foreach ($current as $permission) {
                    if($this->isInheritable($permission)) {
                        foreach ($this->load($resource, $this->normalizeInheritable($permission), $processor)->getPermissions() as $permission)
                            $instance->addPermission($permission);                            
                    } else
                        $instance->addPermission($permission);                
                }
                
                return $instance;
            }
        }
        
        throw $this->getException($entry, $resource);
    }
    
    /**
     * Get the file name pattern for loading an entry from a resource and a processor
     * Override it if needed
     * 
     * @param ResourceInterface $resource
     *   Resource 
     * @param string|null $processor
     *   Processor name. Can be null
     * 
     * @return string
     *   File name pattern
     */
    protected function getFilePatternName(ResourceInterface $resource, ?string $processor = null): string
    {
        return (null === $processor) ? "{$resource->getName()}_ENTRIES" : "{$resource->getName()}_{$processor}_ENTRIES";
    }
    
    /**
     * Get an initialied EntryNotFoundException
     * 
     * @param string $entry
     *   Entry not found
     * @param ResourceInterface $resource
     *   Resource which the entry is required
     * 
     * @return EntryNotFoundException
     *   Exception initialized
     */
    private function getException(string $entry, ResourceInterface $resource): EntryNotFoundException
    {
        $exception = new EntryNotFoundException("This entry '{$entry}' cannot be found for resource '{$resource->getName()}'");
        $exception->setEntry($entry);
        
        return $exception;
    }
    
    /**
     * Inject a file restricting access to this
     * 
     * @param string $file
     *   File name
     * 
     * @return \Closure
     *   Closure to call
     */
    private function inject(string $file): \Closure
    {
        return \Closure::bind(function() use ($file) {
            return include $file;
        }, null);
    }
    
    /**
     * Check if the entry has an inheritable permission.
     * 
     * @param string $entry
     *   Permission referring to an entry
     * 
     * @return bool
     *   True if the permission refer to an entry. False otherwise
     */
    private function isInheritable(string $permission): bool
    {
        return $permission[0] === '{' && false !== \mb_strpos($permission, '}', -1); 
    }
    
    /**
     * Get rid of the inheritance surrounding characters for loading the entry
     * 
     * @param string $entry
     *   Entry to normalize
     * 
     * @return string
     *   Normalized entry
     */
    private function normalizeInheritable(string $entry): string
    {
        return \mb_strcut($entry, 1, -1);
    }

}
