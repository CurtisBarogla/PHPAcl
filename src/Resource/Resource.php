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
use Zoe\Component\Acl\Exception\EntryNotFoundException;

/**
 * Native basic implementation of ResourceInteface
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class Resource implements ResourceInterface
{
    
    /**
     * Bit to grant
     * 
     * @var int
     */
    private $allow = 0;
    
    /**
     * Bit to deny
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
     * Permission name
     * 
     * @var string
     */
    protected $permissions;
    
    /**
     * ACE
     * 
     * @var string[]
     */
    protected $entries;
    
    /**
     * Initialize a new resource
     * 
     * @param string $name
     *   Resource name
     * @param array|null $permissions
     *   Default permissions
     */
    public function __construct(string $name, int $behaviour, ?array $permissions = null)
    {
        $this->name = $name;
        $this->behaviour = $behaviour;
        $this->permissions[self::ALL] = 0;
        
        if(null !== $permissions)
            foreach ($permissions as $permission)
                $this->add($permission);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::allow()
     */
    public function allow(array $permissions): ResourceInterface
    {
        $this->allow |= (isset($permissions[1])) ? $this->getPermissions($permissions) : $this->getPermission($permissions[0]);
        
        return $this;
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::deny()
     */
    public function deny(array $permissions): ResourceInterface
    {
        $this->deny |= (isset($permissions[1])) ? $this->getPermissions($permissions) : $this->getPermission($permissions[0]);
        
        return $this;
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::to()
     */
    public function to(AclUserInterface $user): void
    {
        $current = $user->getPermission();
        
        $current |= $this->allow;
        $current &= ~($this->deny);
        
        $user->setPermission($current);
        
        $this->allow = 0;
        $this->deny = 0;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getPermission()
     */
    public function getPermission($permission): int
    {
        $this->checkPermission($permission);
        
        return $this->permissions[$permission];
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getPermissions()
     */
    public function getPermissions(?array $permissions = null): int
    {
        if(null === $permissions)
            return $this->permissions[self::ALL];
        
        $value = 0;
        foreach ($permissions as $permission) {
            $value |= $this->getPermission($permission);
        }
        
        return $value;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getEntry()
     */
    public function getEntry(string $entry): array
    {
        return [];  
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
     * @return self
     *   Fluent
     * 
     * @throws \InvalidArgumentException
     *   When given permission cannot be added
     */
    public function add(string $permission): self
    {
        if( 
            ($reserved = \in_array($permission, self::PERMISSIONS_RESERVED))
            || ($invalid = 0 === \preg_match("#^[a-z_]+$#", $permission))
            || ($overflow = (($count = \count($this->permissions)) === self::MAX_PERMISSIONS))
            || isset($this->permissions[$permission])
        ) {
            
            $message = ($reserved) 
                ? "Cannot add this permission '{$permission}' on resource '{$this->name}'. It is reserved'" 
                    : (($invalid) ? "This permission '{$permission}' does not respect allowed pattern [a-z_]" 
                        : (($overflow) ? "Cannot add more permission on resource '{$this->name}'" 
                            : "This permission '{$permission}' is already registered into resource '{$this->name}'" ));  
            throw new InvalidPermissionException($message);
        }
        
        $this->permissions[$permission] = $given = (empty($this->permissions)) ? 1 : 1 << $count - 1;
        $this->permissions[self::ALL] += $given;
        
        return $this;
    }
    
    /**
     * Check if a permission is registered.
     * Throws immediately exception if not
     * 
     * @param string $permission
     *   Permission to check
     * 
     * @throws PermissionNotFoundException
     *   When permission is not registered
     */
    private function checkPermission(string $permission): void
    {
        if(!isset($this->permissions[$permission]))
            throw new PermissionNotFoundException("This permission '{$permission}' is not registered into resource '{$this->name}'", $permission);
    }

}
