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
use Zoe\Component\Acl\JsonRestorableInterface;
use Zoe\Component\Acl\Resource\Resource;

/**
 * Use json format
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class JsonCacheFormatStrategy implements CacheFormatStrategyInterface
{
    
    /**
     * @throws \InvalidArgumentException
     *   When given resource does not implement JsonRestorableInterface
     * 
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Loader\Cache\CacheFormatStrategyInterface::processSetting()
     */
    public function processSetting(ResourceInterface $resource): string
    {
        if(!$resource instanceof JsonRestorableInterface)
            throw new \InvalidArgumentException(\sprintf("Resource '%s' must implement JsonRestorableInteface",
                $resource->getName()));
            
        return \json_encode($resource);
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Loader\Cache\CacheFormatStrategyInterface::processGetting()
     */
    public function processGetting(string $resource): ResourceInterface
    {
        return Resource::restoreFromJson($resource);
    }
    
}
