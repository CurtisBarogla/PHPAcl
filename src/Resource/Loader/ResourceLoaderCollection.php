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

namespace Ness\Component\Acl\Resource\Loader;

use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Exception\ResourceNotFoundException;

/**
 * Load resource from multiple ResourceLoader
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ResourceLoaderCollection implements ResourceLoaderInterface
{
    
    /**
     * Registered loaders
     * 
     * @var ResourceLoaderInterface[]
     */
    private $loaders;
    
    /**
     * Initialize resource loader
     * 
     * @param ResourceLoaderInterface $defaultLoader
     *   Default resource loader
     */
    public function __construct(ResourceLoaderInterface $defaultLoader)
    {
        $this->loaders[] = $defaultLoader;
    }
    
    /**
     * Add a loader to the collection
     * 
     * @param ResourceLoaderInterface $loader
     *   Resource loader
     */
    public function addLoader(ResourceLoaderInterface $loader): void
    {
        $this->loaders[] = $loader;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Loader\ResourceLoaderInterface::load()
     */
    public function load(string $resource): ResourceInterface
    {
        foreach ($this->loaders as $loader) {
            try {
                return $loader->load($resource); 
            } catch (ResourceNotFoundException $e) {
                continue;
            }
        }
        
        throw new ResourceNotFoundException("This resource '{$resource}' has been not found into all resource loaders registered into this collection");
    }
    
}
