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

namespace Zoe\Component\Acl\Resource;

use Zoe\Component\Acl\Exception\ResourceNotFoundException;
use Zoe\Component\Acl\Exception\InvalidPermissionException;

/**
 * Collection of resources
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ResourceCollection implements ResourceCollectionInterface
{
    
    /**
     * Resource registered
     * 
     * @var ResourceInterface[]
     */
    private $resources;
    
    /**
     * Collection identifier
     * 
     * @var string
     */
    private $name;
    
    /**
     * Initialize a resource collection
     * 
     * @param string $name
     *   Collection identifier
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    /**
     * {@inheritDoc}
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): \Generator
    {
        foreach ($this->resources as $name => $resource) {
            yield $name => $resource;
        }
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceCollectionInterface::getIdentifier()
     */
    public function getIdentifier(): string
    {
        return $this->name;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceCollectionInterface::get()
     */
    public function get(string $resource): ResourceInterface
    {
        if(!isset($this->resources[$resource]))
            throw new ResourceNotFoundException("This resource '{$resource}' is not registered into resource collection '{$this->name}'");
        
        return $this->resources[$resource];
    }
    
    /**
     * Add a resource to the collection
     * 
     * @param ResourceInterface $resource
     *   Resource to add
     * 
     * @return self
     *   Fluent
     */
    public function add(ResourceInterface $resource): self
    {
        $this->resources[$resource->getName()] = $resource;
        
        return $this;
    }
    
    /**
     * Initialize a new resource collection with shared permissions among all registered resources.
     * 
     * @param string $name
     *   Collection name
     * @param Resource[] $resources
     *   Resources to register
     * @param string[] $sharedPermissions
     *   Permissions shared among all resources
     *   
     * @return ResourceCollectionInterface
     *   Resource collection with shared permissions
     *   
     * @throws \InvalidArgumentException
     *   When a given resource is not an instance of Resource::class
     */
    public static function initializeCollection(string $name, array $resources, array $sharedPermissions = []): ResourceCollectionInterface
    {
        $collection = new ResourceCollection($name);
        
        // This implementation accepts only native ResourceInterface implementation.
        // To use yours, override this method, or use your own implementation of ResourceCollectionInterface
        foreach ($resources as $resource) {
            if(!$resource instanceof Resource)
                throw new \InvalidArgumentException("Resource collection " . self::class . " only accept instances of " . Resource::class);
            foreach ($sharedPermissions as $sharedPermission) {
                try {
                    $resource->addPermission($sharedPermission);                                        
                } catch (InvalidPermissionException $e) {
                    continue;
                }
            }
            $collection->add($resource);
        }
        
        return $collection;
    }
    
}
