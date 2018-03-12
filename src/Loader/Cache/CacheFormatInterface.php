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

namespace Zoe\Component\Acl\Loader\Cache;

use Zoe\Component\Acl\Resource\ResourceInterface;

/**
 * Resource format storable by a cache mechanism (mostly a string)
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface CacheFormatInterface
{
    
    /**
     * Processed when the resource is setted into the cache.
     * Return a normalized string value of the cached resource
     * 
     * @param ResourceInterface $resource
     *   Resource to cache
     * 
     * @return string
     *   Normalize string format of the resource
     */
    public function processSetting(ResourceInterface $resource): string;
    
    /**
     * Processed when the resource is getted from the cache.
     * Resource a restored version of a resource from its normalize version
     * 
     * @param string $resource
     *   Normalized resource
     * 
     * @return ResourceInterface
     *   Resource restored from its normalized version
     */
    public function processGetting(string $resource): ResourceInterface;
    
}
