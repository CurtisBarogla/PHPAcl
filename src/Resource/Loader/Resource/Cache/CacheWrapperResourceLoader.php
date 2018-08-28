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

namespace Ness\Component\Acl\Resource\Loader\Resource\Cache;

use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Wrap a resource loader under a PSR-16 Cache implementation
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
     * PSR-16 Cache implementation
     * 
     * @var CacheInterface
     */
    private $cache;
    
    /**
     * Base to all cache keys
     * 
     * @var string
     */
    public const CACHE_KEY = "NESS_ACL_RESOURCE";
    
    /**
     * Initialize loader
     * 
     * @param ResourceLoaderInterface $loader
     *   Resource loader to wrap
     * @param CacheInterface $cache
     *   PSR-16 Cache implementation
     */
    public function __construct(ResourceLoaderInterface $loader, CacheInterface $cache)
    {
        $this->loader = $loader;
        $this->cache = $cache;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface::load()
     */
    public function load(string $resource): ResourceInterface
    {
        $key = self::CACHE_KEY."_{$resource}";
        if(null === $cached = $this->cache->get($key, null)) {
            $resource = $this->loader->load($resource);
            
            $this->cache->set($key, $resource);
            
            return $resource;
        }
        
        return $cached;
    }
    
}
