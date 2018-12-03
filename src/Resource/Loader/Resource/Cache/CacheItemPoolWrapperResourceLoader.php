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
use Psr\Cache\CacheItemPoolInterface;
use Cache\TagInterop\TaggableCacheItemInterface;

/**
 * Wrap a resource loader under a PSR-6 Cache implementation
 * Supports TagPool implementation
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItemPoolWrapperResourceLoader implements ResourceLoaderInterface
{
    
    /**
     * Resource loader wrapped
     * 
     * @var ResourceLoaderInterface
     */
    private $loader;
    
    /**
     * PSR-6 Cache implementation
     * 
     * @var CacheItemPoolInterface
     */
    private $pool;
    
    /**
     * Base to all cache keys
     * 
     * @var string
     */
    public const CACHE_KEY = "NESS_ACL_RESOURCE_";
    
    /**
     * Use to identify cached resources through a pool supporting tags
     *
     * @var string
     */
    public const CACHE_TAG = "ness_acl_resource";
    
    /**
     * Initialize loader
     * 
     * @param ResourceLoaderInterface $loader
     *   Resource loader wrapped
     * @param CacheItemPoolInterface $pool
     *   PSR-16 Cache pool
     */
    public function __construct(ResourceLoaderInterface $loader, CacheItemPoolInterface $pool)
    {
        $this->loader = $loader;
        $this->pool = $pool;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface::load()
     */
    public function load(string $resource): ResourceInterface
    {
        $key = self::CACHE_KEY. \sha1($resource);
        if( !($item = $this->pool->getItem($key))->isHit() ) {
            $resource = $this->loader->load($resource);
            $item->set($resource);
            
            if($item instanceof TaggableCacheItemInterface)
                $item->setTags([self::CACHE_TAG]);
            
            $this->pool->saveDeferred($item);
            
            return $resource;
        }
        
        return $item->get();
    }

}
