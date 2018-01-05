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
 * Use serialize and unserialize
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NativeSerializeCacheFormatStrategy implements CacheFormatStrategyInterface
{
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Loader\Cache\CacheFormatStrategyInterface::processSetting()
     */
    public function processSetting(ResourceInterface $resource): string
    {
        return \serialize($resource);
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Loader\Cache\CacheFormatStrategyInterface::processGetting()
     */
    public function processGetting(string $resource): ResourceInterface
    {
        return \unserialize($resource);
    }
    
}
