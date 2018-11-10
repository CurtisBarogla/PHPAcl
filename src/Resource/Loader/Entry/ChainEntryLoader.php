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
use Ness\Component\Acl\Exception\EntryNotFoundException;

/**
 * Try to load an entry from a set of EntryLoaderInterface
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ChainEntryLoader implements EntryLoaderInterface
{
    
    /**
     * Loaders registered
     * 
     * @var EntryLoaderInterface
     */
    private $loaders;
    
    /**
     * Initialize loader
     * 
     * @param EntryLoaderInterface $defaultLoader
     *   Default entry loader
     */
    public function __construct(EntryLoaderInterface $defaultLoader)
    {
        $this->loaders[] = $defaultLoader;
    }
    
    /**
     * Register an entry loader
     * 
     * @param EntryLoaderInterface $loader
     *   Entry loader
     */
    public function addLoader(EntryLoaderInterface $loader): void
    {
        $this->loaders[] = $loader;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface::load()
     */
    public function load(ResourceInterface $resource, string $entry, ?string $processor = null): EntryInterface
    {
        foreach ($this->loaders as $loader) {
            try {
                return $loader->load($resource, $entry, $processor);
            } catch (EntryNotFoundException $e) {
                continue;
            }
        }
        
        throw new EntryNotFoundException($entry, "This entry '{$entry}' cannot be found for resource '{$resource->getName()}' via all registered loaders");
    }
    
}
