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

use Ness\Component\Acl\User\AclUserInterface;
use Ness\Component\Acl\Exception\PermissionNotFoundException;
use Ness\Component\Acl\Exception\InvalidArgumentException;
use Ness\Component\Acl\Resource\Loader\ResourceLoaderInterface;

/**
 * Native basic implementation of ResourceInterface.
 * This implementation supports inheritance via ExtendableResourceInterface
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class Resource implements ExtendableResourceInterface, \Serializable
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
     * Permissions setted
     * 
     * @var int[]
     */
    private $permissions = [];
    
    /**
     * Actions to perform over the next call to to()
     * 
     * @var \Closure|null
     */
    private $actions = null;
    
    /**
     * Parent resource name
     * 
     * @var string|null
     */
    private $parent = null;
    
    /**
     * Max permissions allowed
     * 
     * @var int
     */
    public const MAX_PERMISSIONS = 31;
    
    /**
     * Exception code when permission is alredy registered
     * 
     * @var int
     */
    private const ERROR_PERMISSION_ALREADY_REGISTERED = 1;
    
    /**
     * Exception code when permission name is invalid
     *
     * @var int
     */
    private const ERROR_PERMISSION_INVALID = 2;
    
    /**
     * Exception code when max permission allowed is reached
     *
     * @var int
     */
    private const ERROR_MAX_PERMISSION_REACHED = 3;

    /**
     * Initialize a resource
     * 
     * @param string $name
     *   Resource name. Must be compliant with rules of the acl to be called
     * @param int $behaviour
     *   Resource behaviour. One of the const defined into the interface. By default will be setted to whitelist
     *   
     * @throws InvalidArgumentException
     *   When behaviour is invalid
     */
    public function __construct(string $name, int $behaviour = ResourceInterface::WHITELIST)
    {
        if($behaviour !== ResourceInterface::BLACKLIST && $behaviour !== ResourceInterface::WHITELIST)
            throw new InvalidArgumentException("Resource behaviour given for resource '{$name}' is invalid. Use one defined into the interface");
        
        $this->name = $name;
        $this->behaviour = $behaviour;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\ResourceInterface::getName()
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\ResourceInterface::getBehaviour()
     */
    public function getBehaviour(): int
    {
        return $this->behaviour;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\ResourceInterface::grant()
     */
    public function grant($permissions): ResourceInterface
    {
        $this->actions[] = function(int $current) use ($permissions): int {
            return $current | $this->checkPermission($permissions);  
        };
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\ResourceInterface::deny()
     */
    public function deny($permissions): ResourceInterface
    {
        $this->actions[] = function(int $current) use ($permissions): int {
            return $current & ~($this->checkPermission($permissions));   
        };
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\ResourceInterface::to()
     */
    public function to(AclUserInterface $user): void
    {
        if(null === $this->actions)
            return;
        
        if($user->isLocked($this)) {
            unset($this->actions);
            return;
        }
            
        $current = $user->getPermission();
        
        foreach ($this->actions as $action)
            $current = $action->call($this, $current);
        
        $user->setPermission($current);
        
        $this->actions = null;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\ResourceInterface::getPermission()
     */
    public function getPermission($permission): int
    {
        return $this->checkPermission($permission);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\ExtendableResourceInterface::extends()
     */
    public function toExtend(ResourceInterface $resource): ResourceInterface
    {
        if($resource->getName() === $this->name)
            throw new \LogicException("Resource '{$this->name}' cannot have the same parent's one name");
            
        $this->behaviour = $resource->getBehaviour();
        
        $toReinject = \array_keys($this->permissions);
        $this->permissions = [];
        
        foreach (\array_merge(\array_keys($resource->permissions), $toReinject) as $permission) {
            try {
                $this->addPermission($permission);                
            } catch (\LogicException $e) {
                if($e->getCode() !== self::ERROR_PERMISSION_ALREADY_REGISTERED)
                    throw $e;
                continue;
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
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        $this->actions = null;
        return \serialize([
            $this->name,
            $this->behaviour,
            $this->permissions,
            $this->actions,
            $this->parent   
        ]);
    }
    
    /**
     * {@inheritDoc}
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list($this->name, $this->behaviour, $this->permissions, $this->actions, $this->parent) = \unserialize($serialized);
    }
    
    /**
     * Add a permission for the resource
     * 
     * @param string $permission
     *   Permission name
     * @return self
     *   Fluent
     * @throws \LogicException
     *   When permission is already registered or max permissions count is reached
     */
    public function addPermission(string $permission): self
    {
        $current = \count($this->permissions);
        
        if(isset($this->permissions[$permission]))
            throw new \LogicException("This permission '{$permission}' is already registered for resource '{$this->name}'", self::ERROR_PERMISSION_ALREADY_REGISTERED);
        if(null !== $this->permissions && $current > self::MAX_PERMISSIONS)
            throw new \LogicException("Cannot add more permission for resource '{$this->name}'", self::ERROR_MAX_PERMISSION_REACHED);
        if(1 !== \preg_match("#^[a-z_]+$#", $permission))
            throw new \LogicException("This permission name '{$permission}' for resource '{$this->name}' is invalid. MUST contains only [a-z_] characters", self::ERROR_PERMISSION_INVALID);
            
        $this->permissions[$permission] = ($current === 0) ? 1 : 1 << $current;
        
        return $this;
    }
    
    /**
     * Generate a tree of all resources setted as parent for a given one
     * 
     * @param ExtendableResourceInterface $resource
     *   Resource to get the parents
     * @param ResourceLoaderInterface $loader
     *   Resource loader
     * 
     * @return ResourceInterface[]|null
     *   An array of resources or null if no parent are assigned to this resource
     */
    public static function generateParentTree(ExtendableResourceInterface $resource, ResourceLoaderInterface $loader): ?array
    {
        if(null === $resource->getParent())
            return null;
        
        $tree[] = $current = $loader->load($resource->getParent());
        while (null !== $current->getParent()) {
            $tree[] = $current = $loader->load($current->getParent());
            if(!$current instanceof ExtendableResourceInterface)
                break;
        }
        
        return $tree;
    }
    
    /**
     * Check type and existence of a permission and return its value if possible
     * 
     * @param mixed $permissions
     *   Permission
     * 
     * @return int
     *   Value of given permission
     * 
     * @throws \TypeError
     *   When invalid type
     * @throws PermissionNotFoundException
     *   When not setted
     */
    private function checkPermission($permissions): int
    {
        $exists = function(string $permission): int {
            if(!isset($this->permissions[$permission])) {
                $exception = new PermissionNotFoundException("This permission '{$permission}' into resource '{$this->name}' is not setted");
                $exception->setPermission($permission);
                
                throw $exception;
            }
            
            return $this->permissions[$permission];
        };

        switch (\gettype($permissions)) {
            case "string":
                return $exists($permissions);
            case "array":
                $total = 0;
                foreach ($permissions as $permission)
                    $total |= $exists($permission);
                    
                return $total;
            default:
                throw new \TypeError(\sprintf("Permission MUST be a string or an array. '%s' given",
                    (\is_object($permissions) ? \get_class($permissions) : \gettype($permissions))));
        }
    }
    
}
