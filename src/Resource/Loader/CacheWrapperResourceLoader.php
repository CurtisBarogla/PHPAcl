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

use Psr\SimpleCache\CacheInterface;
use Zoe\Component\Acl\Resource\ResourceCollectionInterface;
use Zoe\Component\Acl\Resource\ResourceInterface;

/**
 * Wrap a ResourceLoader into this one for a cache hit first before generation
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheWrapperResourceLoader implements ResourceLoaderInterface
{
    
    /**
     * Resource loader wrapped
     * 
     * @var ResourceLoaderInterface
     */
    private $loader;
    
    /**
     * Psr-16 cache implementation
     * 
     * @var CacheInterface
     */
    private $cache;
    
    /**
     * Base cache key to set or get a cached resource
     * 
     * @var string
     */
    public const ACL_CACHE_WRAPPER_RESOURCE_KEY = "ACL_CACHE_RESOURCE";
    
    /**
     * Initialize resource loader
     * 
     * @param ResourceLoaderInterface $loader
     *   Resource loader to wrap
     * @param CacheInterface $cache
     *   Psr-16 cache implementation
     */
    public function __construct(ResourceLoaderInterface $loader, CacheInterface $cache)
    {
        // deny wrapping this one
        if($loader instanceof CacheWrapperResourceLoader)
            throw new \LogicException("Cannot wrap a CacheWrappedResourceLoader into an another one");
        
        $this->loader = $loader;
        $this->cache = $cache;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\Loader\ResourceLoaderInterface::load()
     */
    public function load(string $resource): ResourceInterface
    {
        return $this->get($resource, false);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\Loader\ResourceLoaderInterface::loadCollection()
     */
    public function loadCollection(string $collection): ResourceCollectionInterface
    {
        return $this->get($collection, true);
    }
    
    /**
     * Invalidate a cached resource
     * 
     * @param string $resource
     *   Resource cached to invalidate
     * 
     * @return bool
     *   True if the cached value has been correctly invalidate. False otherwise
     */
    public function invalidate(string $resource): bool
    {
        return $this->cache->delete(self::ACL_CACHE_WRAPPER_RESOURCE_KEY."_{$resource}");    
    }
    
    /**
     * Get PSR-16 cache implementation
     * 
     * @return CacheInterface
     *   PSR-16 cache implementation
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * Load a resource/collection from a cached one.
     * Set it into setted cache implementation
     * 
     * @param string $resource
     *   Resource/Collection name
     * @param bool $collection
     *   If resource to load is a collection of resources
     * 
     * @return ResourceInterface|ResourceCollectionInterface
     *   Resource or collection
     */
    private function get(string $resource, bool $collection)
    {
        $key = self::ACL_CACHE_WRAPPER_RESOURCE_KEY."_{$resource}";
        if( ($loaded = $this->cache->get($key, null)) === null ) {
            $action = ($collection) ? "loadCollection" : "load";
            $loaded = $this->loader->{$action}($resource);
            
            $this->cache->set($key, $loaded);
            
            return $loaded;
        } else {
            return $loaded;
        }
    }
    
}