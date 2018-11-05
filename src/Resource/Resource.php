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

use Ness\Component\Acl\User\AclUser;
use Ness\Component\Acl\Exception\PermissionNotFoundException;
use Ness\Component\Acl\Exception\InvalidArgumentException;

/**
 * Native basic implementation of ResourceInterface
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class Resource implements ResourceInterface, \Serializable
{
    
    /**
     * Resource name
     * 
     * @var string
     */
    protected $name;
    
    /**
     * Resource behaviour
     * 
     * @var int
     */
    protected $behaviour;
    
    /**
     * Permissions setted
     * 
     * @var int[]
     */
    protected $permissions = [];
    
    /**
     * Actions to perform over the next call to to()
     * 
     * @var \Closure|null
     */
    private $actions = null;
    
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
    protected const ERROR_PERMISSION_ALREADY_REGISTERED = 1;
    
    /**
     * Exception code when max permission allowed is reached
     *
     * @var int
     */
    protected const ERROR_MAX_PERMISSION_REACHED = 2;

    /**
     * Initialize a resource
     * 
     * @param string $name
     *   Resource name
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
     * @see \Ness\Component\Acl\Resource\ResourceInterface::grantRoot()
     */
    public function grantRoot(): ResourceInterface
    {
        $this->actions[] = function(int $current): int {
            return \array_sum($this->permissions);
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
    public function to(AclUser $user): void
    {
        if(null === $this->actions)
            return;
        
        if($user->isLocked($this)) {
            unset($this->actions);
            return;
        }
            
        $current = $user->getPermission($this) ?? ( ($this->behaviour === self::WHITELIST) ? 0 : \array_sum($this->permissions) );

        foreach ($this->actions as $action)
            $current = $action->call($this, $current);

        $user->setPermission($this, $current);
        
        $this->actions = null;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\ResourceInterface::getPermissions()
     */
    public function getPermissions(): array
    {
        return \array_keys($this->permissions);
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
     * @see \Serializable::serialize()
     */
    public function serialize(): string
    {
        $this->actions = null;
        return \serialize($this->toSerialize());
    }
    
    /**
     * {@inheritDoc}
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized): void
    {
        list($this->name, $this->behaviour, $this->permissions, $this->actions) = \unserialize($serialized);
    }
    
    /**
     * Add a permission for the resource
     * 
     * @param string $permission
     *   Permission name
     *   
     * @return self
     *   Fluent
     *   
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
            
        $this->permissions[$permission] = ($current === 0) ? 1 : 1 << $current;
        
        return $this;
    }
    
    /**
     * Get serializable properties
     * 
     * @return array
     *   Serializable properties
     */
    protected function toSerialize(): array
    {
        return [
            $this->name,
            $this->behaviour,
            $this->permissions,
            $this->actions
        ];
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
    protected function checkPermission($permissions): int
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
