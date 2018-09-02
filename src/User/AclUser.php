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
     * Permission queued for the call to on
     * 
     * @var array|string|null
     */
    private $permissionsQueue;
    
    /**
     * Resource locked
     * 
     * @var bool[]
     */
    private $locked;
    
    /**
     * Already fetched permissions
     * 
     * @var int[]
     */
    private $fetched;
    
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
     * @see \Ness\Component\User\UserInterface::getAttributes()
     */
    public function getAttributes(): ?iterable
    {
        return $this->user->getAttributes();
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
    public function getPermission(ResourceInterface $resource): ?int
    {
        $name = $resource->getName();
        if(isset($this->fetched[$name]))
            return $this->fetched[$name];
        
        if(null === $permissions = $this->getAttribute(self::ACL_ATTRIBUTE_IDENTIFIER))
            return null;
        
        return $this->fetched[$name] = ($permissions["<{$name}>"] ?? $permissions[$name] ?? null);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\User\AclUserInterface::setPermission()
     */
    public function setPermission(ResourceInterface $resource, int $permission): void
    {
        if(null === $permissions = $this->getAttribute(self::ACL_ATTRIBUTE_IDENTIFIER)) {
            $this->addAttribute(self::ACL_ATTRIBUTE_IDENTIFIER, [$resource->getName() => $permission]);
            return;
        }
        
        $name = $resource->getName();
        $permissions[$name] = $permission;
        $this->addAttribute(self::ACL_ATTRIBUTE_IDENTIFIER, $permissions);
        unset($this->fetched[$name]);
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
     * @see \Ness\Component\Acl\User\AclUserInterface::grantRoot()
     */
    public function grantRoot(): AclUserInterface
    {
        $this->queue("root", "root");
        
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
        if(null === $this->permissionsQueue) {
            unset($this->fetched[$resource->getName()]);
            return;
        }
        
        if($this->isLocked($resource)) {
            $this->permissionsQueue = null;
            return;
        }
            
        try {
            foreach ($this->permissionsQueue as $permissions) {
                foreach ($permissions as $type => $permission) {
                    if("root" === $type)
                        $resource->grantRoot();
                    else 
                        ($type === "grant") ? $resource->grant($permission) : $resource->deny($permission);
                }
            }

            $resource->to($this);
            unset($this->fetched[$resource->getName()]);
        } catch (PermissionNotFoundException $e) {
            throw new PermissionNotFoundException("This permission '{$e->getPermission()}' cannot be attributed to user '{$this->getName()}' as it is not defined into resource '{$resource->getName()}'");
        } finally {
            $this->permissionsQueue = null;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\User\AclUserInterface::lock()
     */
    public function lock(ResourceInterface $resource): void
    {
        if($this->isLocked($resource))
            return;
        
        $this->on($resource);
        
        if(null !== $permissions = $this->getAttribute(self::ACL_ATTRIBUTE_IDENTIFIER)) {
            $name = $resource->getName();
            if(isset($permissions[$name]))
                unset($permissions[$name]);
            
            $permissions["<{$name}>"] = $this->getPermission($resource) ?? 0;
            $this->addAttribute(self::ACL_ATTRIBUTE_IDENTIFIER, $permissions);
            $this->locked[$name] = true;
            
            return;
        }
        
        $this->locked[$resource->getName()] = true;
        $this->addAttribute(self::ACL_ATTRIBUTE_IDENTIFIER, ["<{$resource->getName()}>" => $this->getPermission($resource) ?? 0]);            
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\User\AclUserInterface::isLocked()
     */
    public function isLocked(ResourceInterface $resource): bool
    {
        $name = $resource->getName();
        return $this->locked[$name] 
                    ?? $this->locked[$name] = ( (null !== $permissions = $this->getAttribute(self::ACL_ATTRIBUTE_IDENTIFIER)) ? isset($permissions["<{$name}>"]) : false );
    }
    
    /**
     * Get reference to the wrapped user
     * 
     * @return UserInterface
     *   Wrapped user
     */
    public function getUser(): UserInterface
    {
        return $this->user;
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
            
        $this->permissionsQueue[][$type] = $permissions;
    }

}
