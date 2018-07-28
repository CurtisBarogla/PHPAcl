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

namespace Ness\Component\Acl\Resource;

use Ness\Component\Acl\Exception\InvalidArgumentException;
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface;

/**
 * Extendable resource
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ExtendableResource extends Resource implements ExtendableResourceInterface
{
    
    /**
     * Parent resource
     * 
     * @var string|null
     */
    private $parent;
    
    /**
     * Initialize an extendable resource
     *
     * @param string $name
     *   Resource name
     * @param int $behaviour
     *   Resource behaviour. One of the const defined into the interface. By default will be setted to whitelist
     * @param ResourceInterface|null $parent
     *   Parent resource or null for later assignation
     *
     * @throws InvalidArgumentException
     *   When behaviour is invalid
     */
    public function __construct(string $name, int $behaviour = ResourceInterface::WHITELIST, ?ResourceInterface $parent = null)
    {
        parent::__construct($name, $behaviour);
        if(null !== $parent)
            $this->extendsFrom($parent);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\ExtendableResourceInterface::extends()
     */
    public function extendsFrom(ResourceInterface $resource): ExtendableResourceInterface
    {
        if(null !== $this->parent)
            throw new \LogicException("Resource '{$this->name}' cannot have more than one parent");
        
        if($resource->getName() === $this->name)
            throw new \LogicException("Resource '{$this->name}' cannot have the same parent's one name");    
            
        $this->behaviour = $resource->getBehaviour();
        
        $toReinject = \array_keys($this->permissions);
        $this->permissions = [];
        
        foreach (\array_merge($resource->getPermissions(), $toReinject) as $permission) {
            try {
                $this->addPermission($permission);
            } catch (\LogicException $e) {
                if($e->getCode() === self::ERROR_PERMISSION_ALREADY_REGISTERED)                                   
                    continue; 
                throw $e;
            }
        }
        
        $this->parent = $resource->getName();
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\ExtendableResourceInterface::getParent()
     */
    public function getParent(): ?string
    {
        return $this->parent;
    }
    
    /**
     * {@inheritDoc}
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized): void
    {
        list($this->name, $this->behaviour, $this->permissions, $this->actions, $this->parent) = \unserialize($serialized);
    }
    
    /**
     * Generate all parents from this resource
     *
     * @param ExtendableResourceInterface $resource
     *   Resource to get the parents
     * @param ResourceLoaderInterface $loader
     *   Resource loader
     *
     * @return ResourceInterface[]
     *   An iterator of resources
     */
    public static function generateParents(ExtendableResourceInterface $resource, ResourceLoaderInterface $loader): \Generator
    {
        if(null === $resource->getParent())
            yield null;
        else {
            while (null !== $parent = $resource->getParent()) {
                $resource = $loader->load($parent);
                yield $resource;
                
                if(!$resource instanceof ExtendableResourceInterface)
                    break;
            }
        }
    }
    
    /**
     * Convert a basic resource to an extendable one conserving all datas from it
     * 
     * @param ResourceInterface $resource
     *   Resource to convert
     * 
     * @return ExtendableResourceInterface
     *   Extendable resource
     */
    public static function buildFromBasicResource(ResourceInterface $resource): ExtendableResourceInterface
    {
        if($resource instanceof ExtendableResourceInterface)
            return $resource;
        
        $extendable = new self($resource->getName(), $resource->getBehaviour());
        foreach ($resource->getPermissions() as $permission)
            $extendable->addPermission($permission);
        
        return $extendable;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Resource::toSerialize()
     */
    protected function toSerialize(): array
    {
        return \array_merge(parent::toSerialize(), [$this->parent]);
    }
    
}
