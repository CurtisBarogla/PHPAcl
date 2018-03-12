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

namespace Zoe\Component\Acl\Loader;

use Psr\SimpleCache\CacheInterface;
use Zoe\Component\Acl\Loader\Cache\CacheFormatInterface;
use Zoe\Component\Acl\Resource\ResourceInterface;

/**
 * Wrapper around a resource loader to get a resource from a cache
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheWrapperResourceLoader implements ResourceLoaderInterface
{
    
    /**
     * Loader to wrap
     * 
     * @var ResourceLoaderInterface
     */
    private $loader;
    
    /**
     * Psr-16 Cache implementation
     * 
     * @var CacheInterface
     */
    private $cache;
    
    /**
     * Format strategy used
     * 
     * @var CacheFormatInterface
     */
    private $format;
    
    /**
     * Prefix setted for each cache key
     * 
     * @var string
     */
    public const CACHE_LOADER_PREFIX = "ACL_CACHE_RESOURCE_";
    
    /**
     * Initialize loader
     * 
     * @param ResourceLoaderInterface $loader
     *   Loader implementation to wrap
     * @param CacheInterface $cache
     *   PSR-16 Cache implementation
     * @param CacheFormatInterface $format
     *   Cache format used
     */
    public function __construct(ResourceLoaderInterface $loader, CacheInterface $cache, CacheFormatInterface $format)
    {
        $this->loader = $loader;
        $this->cache = $cache;
        $this->format = $format;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Loader\ResourceLoaderInterface::load()
     */
    public function load(string $resource): ResourceInterface
    {
        if(null === $cache = $this->cache->get(self::CACHE_LOADER_PREFIX.$resource)) {
            $loaded = $this->loader->load($resource);
            $this->cache->set(self::CACHE_LOADER_PREFIX.$resource, $this->format->processSetting($loaded));
            
            return $loaded;
        } else {
            return ($cache instanceof ResourceInterface) ? $cache : $this->format->processGetting($cache);
        }
    }
    
    /**
     * Get setted cache implementation
     * 
     * @return CacheInterface
     *   PSR-16 Cache
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

}
