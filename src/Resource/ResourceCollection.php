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

use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Exception\ResourceNotFoundException;
use Zoe\Component\Acl\Exception\InvalidPermissionException;

/**
 * Native implementation of ResourceCollection
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ResourceCollection implements ResourceCollectionInterface, ProcessableResourceInterface
{
    
    /**
     * Initialized when shouldBeProcessed is called and resource should be processed
     * 
     * @var array|null
     */
    private $toProcess = null;
    
    /**
     * Collection name
     * 
     * @var string
     */
    protected $name;
    
    /**
     * Resource registered
     * 
     * @var ResourceInterface[]
     */
    protected $resources;
    
    /**
     * Initialize a new resource collection
     * 
     * @param string $name
     *   Resource collection name
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
        foreach ($this->resources as $name => $resource) 
            yield $name => $resource;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceCollectionInterface::getName()
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceCollectionInterface::getResource()
     */
    public function getResource(string $resource): ResourceInterface
    {
        if(!isset($this->resources[$resource]))
            throw new ResourceNotFoundException("This resource '{$resource}' is not registered into resource collection '{$this->name}'");
        
        return $this->resources[$resource];
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ProcessableResourceInterface::shouldBeProcessed()
     */
    public function shouldBeProcessed(AclUserInterface $user): bool
    {
        $result = false;
        foreach ($this->resources as $name => $resource) {
            if($resource instanceof ProcessableResourceInterface) {
                if($resource->shouldBeProcessed($user)) {
                    $this->toProcess[] = $name;
                    $result = true;
                }
            }
        }
        
        return $result;
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ProcessableResourceInterface::process()
     */
    public function process(array $processors, AclUserInterface $user): void
    {
        if(null === $this->toProcess)
            throw new \LogicException("No resource are processable actually. Did you make a call to shouldBeProcessed ?");
        
        foreach ($this->toProcess as $resource) {
            // resources given are obligatory instance of ProcessableResourceInterface
            $this->resources[$resource]->process($processors, $user);
        }
    }
    
    /**
     * Add a resource to the collection
     * 
     * @param ResourceInterface $resource
     *   Resource to add
     */
    public function addResource(ResourceInterface $resource): void
    {
        $this->resources[$resource->getName()] = $resource;
    }
    
    /**
     * Initialize a new collection of resources. Shared permissions over registered resources are possible
     * If shared permissions are given, this factory needs an addPermission method into implementation of ResourceInterface or it will be skipped.
     * Overload this method if an another process is setted into your resource implementation for permission setting
     * 
     * @param string $name
     *   Collection name
     * @param ResourceInterface[] $resources
     *   Resource to add
     * @param array $sharedPermissions
     *   Shared permissions over all registered resources
     * 
     * @return ResourceCollectionInterface
     *   Collection initialized
     */
    public static function initializeCollection(string $name, array $resources, array $sharedPermissions = []): ResourceCollectionInterface
    {
        $collection = new self($name);
        
        if(!empty($reserved = \array_intersect($sharedPermissions, ResourceInterface::RESERVED_PERMISSIONS)))
            throw new InvalidPermissionException(\sprintf("Reserved permissions are given as shared. '%s'",
                \implode(", ", $reserved)));
        
        foreach ($resources as $resource) {
            if(!$resource instanceof ResourceInterface)
                throw new \InvalidArgumentException("Resource MUST be an instance of ResourceInterface for initializing collection : '{$name}'");
            try {
                foreach ($sharedPermissions as $permission) {
                    try {
                        $resource->addPermission($permission);                                            
                    } catch (InvalidPermissionException $e) {
                        if($e->getCode() === ResourceInterface::RESOURCE_ERROR_CODE_ALREADY_REGISTERED_PERMISSION)
                            continue;
                        throw $e;
                    }
                }
            } catch (\Error $e) {
                continue;
            } finally {
                $collection->addResource($resource);                
            }
        }
        
        return $collection;
    }
    
}
