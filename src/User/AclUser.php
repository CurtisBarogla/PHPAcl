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

namespace Ness\Component\Acl\User;

use Ness\Component\User\UserInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Exception\PermissionNotFoundException;
use Ness\Component\User\Exception\UserAttributeNotFoundException;

/**
 * Simple implementation of AclUser
 * Wrap a basic user
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
final class AclUser implements AclUserInterface
{
    
    /**
     * Wrapped user
     * 
     * @var UserInterface
     */
    private $user;
    
    /**
     * Current permission
     * 
     * @var int
     */
    private $permission = 0;
    
    /**
     * Permission queued for the call to on
     * 
     * @var array|string|null
     */
    private $permissions;
    
    /**
     * Initialize acl user
     * 
     * @param UserInterface $user
     *   User to wrap
     */
    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\User\UserInterface::getName()
     */
    public function getName(): string
    {
        return $this->user->getName();
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\User\UserInterface::addAttribute()
     */
    public function addAttribute(string $attribute, $value): UserInterface
    {
        return $this->user->addAttribute($attribute, $value);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\User\UserInterface::getAttribute()
     */
    public function getAttribute(string $attribute)
    {
        return $this->user->getAttribute($attribute);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\User\UserInterface::getAttributes()
     */
    public function getAttributes(): ?iterable
    {
        return $this->user->getAttributes();
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\User\UserInterface::deleteAttribute()
     */
    public function deleteAttribute(string $attribute): void
    {
        $this->user->deleteAttribute($attribute);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\User\UserInterface::getRoles()
     */
    public function getRoles(): ?iterable
    {
        return $this->user->getRoles();
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\User\UserInterface::hasRole()
     */
    public function hasRole(string $role): bool
    {
        return $this->user->hasRole($role);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\User\AclUserInterface::getPermission()
     */
    public function getPermission(): int
    {
        return $this->permission;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\User\AclUserInterface::setPermission()
     */
    public function setPermission(int $permission): void
    {
        $this->permission = $permission;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\User\AclUserInterface::grant()
     */
    public function grant($permissions): AclUserInterface
    {
        $this->queue($permissions, "grant");
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\User\AclUserInterface::deny()
     */
    public function deny($permissions): AclUserInterface
    {
        $this->queue($permissions, "deny");
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\User\AclUserInterface::on()
     */
    public function on(ResourceInterface $resource): void
    {
        if(null === $this->permissions)
            return;
        
        if($this->isLocked($resource)) {
            $this->permissions = null;
            return;
        }
            
        try {
            foreach ($this->permissions as $permissions) {
                foreach ($permissions as $type => $permission) {
                    ($type === "grant") ? $resource->grant($permission) : $resource->deny($permission);
                }
            }

            $resource->to($this);                    
        } catch (PermissionNotFoundException $e) {
            throw new PermissionNotFoundException("This permission '{$e->getPermission()}' cannot be attributed to user '{$this->getName()}' as it is not defined into resource '{$resource->getName()}'");
        } finally {
            $this->permissions = null;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\User\AclUserInterface::lock()
     */
    public function lock(ResourceInterface $resource): void
    {
        $this->on($resource);
        
        try {            
            $attribute = $this->getAttribute(self::ACL_ATTRIBUTE_IDENTIFIER);
            $name = $resource->getName();
            if(isset($attribute[$name]))
                unset($attribute[$name]);
            
            $attribute["<{$name}>"] = $this->permission;
            $this->addAttribute(self::ACL_ATTRIBUTE_IDENTIFIER, $attribute);
        } catch (UserAttributeNotFoundException $e) {
            $this->addAttribute(self::ACL_ATTRIBUTE_IDENTIFIER, ["<{$resource->getName()}>" => $this->permission]);            
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\User\AclUserInterface::isLocked()
     */
    public function isLocked(ResourceInterface $resource): bool
    {
        try {
            return isset($this->getAttribute(self::ACL_ATTRIBUTE_IDENTIFIER)["<{$resource->getName()}>"]);
        } catch (UserAttributeNotFoundException $e) {
            return false;
        }
    }
    
    /**
     * Queue given permission for the next call to on
     * 
     * @param string|array $permissions
     *   Permission to queue
     * @param string $type
     *   Deny or grant
     * 
     * @throws \TypeError
     *   When given permission is neither an array or a string
     */
    private function queue($permissions, string $type): void
    {
        if(!\is_string($permissions) && !\is_array($permissions))
            throw new \TypeError(\sprintf("Permissions MUST be an array or a string. '%s' given",
                (\is_object($permissions) ? \get_class($permissions) : \gettype($permissions))));
            
        $this->permissions[][$type] = $permissions;
    }

}
