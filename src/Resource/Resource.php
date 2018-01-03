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

use Zoe\Component\Acl\JsonRestorableInterface;
use Zoe\Component\Acl\Exception\EntityNotFoundException;
use Zoe\Component\Acl\Exception\InvalidMaskException;
use Zoe\Component\Acl\Exception\InvalidResourceBehaviourException;
use Zoe\Component\Acl\Exception\PermissionNotFoundException;
use Zoe\Component\Acl\Mask\Mask;
use Zoe\Component\Acl\Mask\MaskCollection;
use Zoe\Component\Acl\User\AclUserInterface;

/**
 * Native resource implementation
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class Resource implements ResourceInterface, JsonRestorableInterface
{
    
    /**
     * Resource name
     * 
     * @var string
     */
    private $name;
    
    /**
     * Resource behaviour
     * 
     * @var int
     */
    private $behaviour;
    
    /**
     * Mask collection representation all permissions setted
     * 
     * @var MaskCollection
     */
    private $permissions;
    
    /**
     * Entities registered
     * 
     * @var EntityInterface[]|null
     */
    private $entities;
    
    /**
     * Process state of the resource
     * 
     * @var bool
     */
    private $isProcessed = false;
    
    /**
     * Initialize a new resource
     * 
     * @param string $name
     *   Resource name
     * @param int $behaviour
     *   Resource behaviour. One of the const defined into the interface (BLACKLIST or WHITELIST)
     *   
     * @throws InvalidResourceBehaviourException
     *   When behaviour is invalid
     */
    public function __construct(string $name, int $behaviour)
    {
        if(!\in_array($behaviour, [self::BLACKLIST, self::WHITELIST]))
            throw new InvalidResourceBehaviourException(\sprintf("This behaviour '%s' is invalid for resource '%s'. Use one defined into the interface",
                $behaviour,
                $name));
        
        $this->name = $name;
        $this->behaviour = $behaviour;
        $this->permissions = new MaskCollection("PERMISSIONS_{$this->name}");
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getName()
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getBehaviour()
     */
    public function getBehaviour(): int
    {
        return $this->behaviour;
    }
    
    /**
     * Add a permission to the resource
     * 
     * @param string $permission
     *   Permission name
     *   
     * @throws \LogicException
     *   When max permissions count is reached
     */
    public function addPermission(string $permission): void
    {
        $index = \count($this->permissions);
        
        if($index >= self::MAX_PERMISSIONS)
            throw new \LogicException(\sprintf("Resource cannot be have more than '%s' permissions",
                self::MAX_PERMISSIONS));
        
        $permission = new Mask($permission, 1);

        if($index === 0) {
            $this->permissions->add($permission);
            return;
        }

        $permission->lshift($index);
        $this->permissions->add($permission);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getPermission()
     */
    public function getPermission(string $permission): Mask
    {
        try {
            return $this->permissions->get($permission);
        } catch (InvalidMaskException $e) {
            $exception = new PermissionNotFoundException(\sprintf("This permission '%s' for resource '%s' is not defined",
                $permission,
                $this->name));
            $exception->setInvalidPermission($permission);
            
            throw $exception;
        }
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getPermissions()
     */
    public function getPermissions(?array $permissions = null): MaskCollection
    {
        if(null === $permissions)
            return $this->permissions;
        
        $collection = new MaskCollection("PERMISSIONS");
        foreach ($permissions as $permission)
            $collection->add($this->getPermission($permission));
        
        return $collection;
    }
    
    /**
     * Attached an entity to the resource
     * 
     * @param EntityInterface $entity
     *   Entity
     *   
     * @throws \InvalidArgumentException
     *   This implementation require a JsonRestorableInterface entity. Overwrite if needed
     */
    public function registerEntity(EntityInterface $entity): void
    {
        if(!$entity instanceof JsonRestorableInterface)
            throw new \InvalidArgumentException(\sprintf("This entity '%s' MUST implement JsonRestorableInterface for resource '%s'",
                $entity->getName(),
                $this->name));
        
        $this->entities[$entity->getName()] = $entity;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getEntity()
     */
    public function getEntity(string $entity): EntityInterface
    {
        if(!isset($this->entities[$entity]))
            throw new EntityNotFoundException(\sprintf("This entity is not registered for resource '%s'",
                $this->name));
            
        return $this->entities[$entity];
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getEntities()
     */
    public function getEntities(): ?array
    {
        return $this->entities;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::process()
     */
    public function process(array $processors, AclUserInterface $user): void
    {
        if(null === $this->entities) {
            $this->isProcessed = true;
            
            return;
        }
        
        foreach ($this->entities as $entity) {
            $processor = $entity->getProcessor();
            if(null === $processor || \count($entity) === 0)
                continue;
            
            if(!isset($processors[$processor]))
                throw new \RuntimeException(\sprintf("This processor '%s' for entity '%s' attached to '%s' resource is not registered",
                    $processor,
                    $entity->getName(),
                    $this->name));
                
            $processor = $processors[$processor];
            
            $processor->setEntity($entity);
            $processor->process($this, $user);
        }
        
        $this->isProcessed = true;
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::isProcessed()
     */
    public function isProcessed(): bool
    {
        return $this->isProcessed;
    }

    /**
     * {@inheritDoc}
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize(): array
    {
        return [
            "name"          =>  $this->name,
            "behaviour"     =>  $this->behaviour,
            "permissions"   =>  $this->permissions,
            "entities"      =>  $this->entities
        ];     
    }
    
    /**
     * Restore the resource
     * 
     * @see \Zoe\Component\Acl\JsonRestorableInterface::restoreFromJson()
     * 
     * @return ResourceInterface
     *   Resource restored
     */
    public static function restoreFromJson($json): ResourceInterface
    {
        if(!\is_array($json))
            $json = \json_decode($json, true);
        
        $resource = new Resource($json["name"], $json["behaviour"]);
        $resource->permissions = MaskCollection::restoreFromJson($json["permissions"]);
        $resource->entities = (null !== $entities = $json["entities"]) ?
            \array_map(function(array $entity): Entity {
                return Entity::restoreFromJson($entity); 
            }, $entities)
            : null;
        
        return $resource;
    }
    
}
