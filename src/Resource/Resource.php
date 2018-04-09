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
use Zoe\Component\Acl\Exception\InvalidPermissionException;
use Zoe\Component\Acl\Exception\PermissionNotFoundException;

/**
 * Native implementation of ResourceInterface
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class Resource implements ResourceInterface
{
    
    /**
     * Permissions value to later grant
     * 
     * @var int
     */
    private $grant = 0;
    
    /**
     * Permissions value to later deny
     * 
     * @var int
     */
    private $deny = 0;
    
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
    protected $permissions;
    
    /**
     * Initialize a resource
     * 
     * @param string $name
     *   Resource name
     * @param int $behaviour
     *   Resource behaviour
     */
    public function __construct(string $name, int $behaviour)
    {
        $this->name = $name;
        $this->behaviour = $behaviour;
        $this->permissions[self::ALL_PERMISSIONS] = 0;
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
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::grant()
     */
    public function grant($permissions): ResourceInterface
    {
        $this->set($permissions, "grant");

        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::deny()
     */
    public function deny($permissions): ResourceInterface
    {
        $this->set($permissions, "deny");
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::to()
     */
    public function to(AclUserInterface $user): void
    {
        $permission = $user->getPermission();
        
        $permission |= $this->grant;
        $permission &= ~($this->deny);
        
        $user->setPermission($permission);
        
        $this->grant = 0;
        $this->deny = 0;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getPermission()
     */
    public function getPermission(string $permission): int
    {
        $this->checkPermission($permission);
        
        return $this->permissions[$permission];
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getPermissions()
     */
    public function getPermissions(array $permissions): int
    {
        $value = 1;
        foreach ($permissions as $permission) {
            $value |= $this->getPermission($permission);
        }
        
        return $value;   
    }
    
    /**
     * Add a permission
     * 
     * @param string $permission
     *   Permission name. Must respect pattern [a-z_] or it will throw an exception
     * 
     * @return self
     *   Fluent
     *   
     * @throws InvalidPermissionException
     *   When a permission contains an invalid character or is reserved or is already registered
     * @throws \LogicException
     *   When max permissions count is reached or a reserved permission is not initialized
     */
    public function add(string $permission): self
    {
        $current = \count($this->permissions);

        if(
            ($invalid       = (0 === \preg_match("#^[a-z_]+$#", $permission)))      ||
            ($reserved      = \in_array($permission, self::RESERVED_PERMISSIONS))   ||
            ($exists        = isset($this->permissions[$permission]))               ||
            ($overflow = ($current - \count(self::RESERVED_PERMISSIONS) >= self::MAX_PERMISSIONS))
        ) {
            $message = ($invalid || $reserved || $exists) 
            ? (($invalid) 
                ? "This permission '{$permission}' does not respest pattern [a-z_]" 
                : (($reserved) 
                    ? "This permission '{$permission}' cannot be added as its name is reserved" 
                    : "This permission '{$permission}' cannot be added as it is already registered into resource '{$this->name}'"))
            : "Cannot add more permissions into resource '{$this->name}'. Max permission allowed setted to " . self::MAX_PERMISSIONS;
                    
            $exception = (isset($overflow)) ? new \LogicException($message) : new InvalidPermissionException($message);
            
            throw $exception;
        }
        
        try {
            $this->permissions[$permission] = ($setted = 1 << $current - \count(self::RESERVED_PERMISSIONS));
            $this->permissions[self::ALL_PERMISSIONS] += $setted;            
        } catch (\ArithmeticError $e) {
            throw new \LogicException("Cannot set this permission '{$permission}' bit value. Did you forget to initialize a reserved permission ?");
        }
        
        return $this;
    }
    
    /**
     * Register a permission value into a defined property depending on the given type.
     * Type allowed string and array
     * 
     * @param string|array $permissions
     *   Permission(s) to set
     * @param string $property
     *   Property which permission value is injected
     * 
     * @throws \TypeError
     *   When given permission is neither an array on a string
     */
    private function set($permissions, string $property): void
    {
        switch ($type = \gettype($permissions)) {
            case "string":
                $this->{$property} = $this->getPermission($permissions);
                break;
            case "array":
                $this->{$property} = $this->getPermissions($permissions);
                break;
            default:
                throw new \TypeError("Permissions MUST be either an array or a string. {$type} given");
        }   
    }
    
    /**
     * Validate a permission.
     * Throw exception if not valid
     * 
     * @param string $permission
     *   Permission to check
     * 
     * @throws PermissionNotFoundException
     *   When given permission is invalid
     */
    private function checkPermission(string $permission): void
    {
        if(!isset($this->permissions[$permission]))
            throw new PermissionNotFoundException("This permission '{$permission}' is not registered into resource '{$this->name}'");
    }

}
