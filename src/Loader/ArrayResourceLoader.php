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

use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\Exception\ResourceNotFoundException;
use Zoe\Component\Acl\Resource\Resource;
use Zoe\Component\Acl\Resource\Entity;

/**
 * Load resource from an array representing it
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ArrayResourceLoader implements ResourceLoaderInterface
{
    
    /**
     * Array representing all resources loadable by this loaded
     * 
     * @var array
     */
    private $resources;
    
    /**
     * Initialize loader
     * 
     * @param array $resources
     *   Array representation of all resources loadables by this loaded
     */
    public function __construct(array $resources)
    {
        $this->resources = $resources;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Loader\ResourceLoaderInterface::load()
     */
    public function load(string $resource): ResourceInterface
    {
        if(!isset($this->resources[$resource]))
            throw new ResourceNotFoundException(\sprintf("This resource '%s' cannot be loaded",
                $resource));
            
        $info = $this->resources[$resource];
        $behaviour = ($info["behaviour"] === "blacklist") ? ResourceInterface::BLACKLIST : ResourceInterface::WHITELIST;
        $resource = new Resource($resource, $behaviour);
        
        if(isset($info["permissions"])) {
            foreach ($info["permissions"] as $permission)
                $resource->addPermission($permission);
        }
        
        if(isset($info["entities"])) {
            foreach ($info["entities"] as $name => $entityInfo) {
                $entity = new Entity($name, $entityInfo["processor"] ?? null);
                if(isset($entityInfo["values"])) {
                    foreach ($entityInfo["values"] as $value => $permissions) {
                        $entity->add($value, $permissions);
                    }
                }
                $resource->registerEntity($entity);
            }
        }
        
        return $resource;
    }

}
