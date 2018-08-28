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
 * Files can be either a single file or a directory containing entries
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
            if(\is_array($current) && $index === $entry)
                return $this->parse($index, $current);
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
     * Parse an array representation of an entry
     * 
     * @param string $entry
     *   Entry name
     * @param array $permissions
     *   Permissions applied to this entry
     * 
     * @return EntryInterface
     *   Entry initialized
     */
    private function parse(string $entry, array $permissions): EntryInterface
    {
        $entry = new Entry($entry);
        foreach ($permissions as $permission)
            $entry->addPermission($permission);
        
        return $entry;
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

}
