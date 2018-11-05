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
use Ness\Component\Acl\Normalizer\LockPatternNormalizerInterface;

/**
 * Extending basic user to make it able to communicate with acl components
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class AclUser implements UserInterface
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
     * Resource lock pattern normalizer
     * 
     * @var LockPatternNormalizerInterface
     */
    private $normalizer;
    
    /**
     * Identifier to access permission setted into the user's attributes
     *
     * @var string
     */
    public const ACL_ATTRIBUTE_IDENTIFIER = "NESS_ACL_RESOURCE";
    
    /**
     * Initialize acl user
     * 
     * @param UserInterface $user
     *   User to wrap
     * @param LockPatternNormalizerInterface $normalizer
     *   Lock pattern resource name normalizer
     */
    public function __construct(UserInterface $user, LockPatternNormalizerInterface $normalizer)
    {
        $this->user = $user;
        $this->normalizer = $normalizer;
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
     * Get permission accorded to the user for a resource
     * 
     * @param ResourceInterface $resource
     *   Resource to get the permission
     * 
     * @return int|null
     *   Value representing the permission. Returns null if no permission found for this resource
     */
    public function getPermission(ResourceInterface $resource): ?int
    {
        $name = $resource->getName();
        if(isset($this->fetched[$name]))
            return $this->fetched[$name];
        
        if(null === $permissions = $this->getAttribute(self::ACL_ATTRIBUTE_IDENTIFIER))
            return null;
        
        return $this->fetched[$name] = ($permissions[$this->normalizer->apply($name)] ?? $permissions[$name] ?? null);
    }
    
    /**
     * Set permission accorded to a resource
     * 
     * @param ResourceInterface $resource
     *   Resource to accord to permission
     * @param int $permission
     *   Value representing the permission
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
     * Grant permission over the resource setted into the next call of to
     * 
     * @param string|array $permissions
     *   Permission to grant
     *   
     * @return self
     *   Fluent
     *   
     * @throws \TypeError
     *   When permission is neither a string or an array
     */
    public function grant($permissions): self
    {
        $this->queue($permissions, "grant");
        
        return $this;
    }
    
    /**
     * Grant root permission over the resource setted into the next call of to
     *
     * @return self
     *   Fluent
     */
    public function grantRoot(): self
    {
        $this->queue("root", "root");
        
        return $this;
    }
    
    /**
     * Deny permission over the resource setted into the next call of to
     *
     * @param string|array $permissions
     *   Permission to deny
     *
     * @return self
     *   Fluent
     *   
     * @throws \TypeError
     *   When permission is neither a string or an array
     */
    public function deny($permissions): self
    {
        $this->queue($permissions, "deny");
        
        return $this;
    }
    
    /**
     * Finalize all grant an deny actions over a resource
     * 
     * @param ResourceInterface $resource
     *   Resource processed
     *   
     * @throws PermissionNotFoundException
     *   When a permission has been not setted into the resource
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
     * Lock permission on the resource.
     * If previous permission setting has been made and not attributed via a on() call, lock MUST attribute it before locking the user
     * 
     * @param ResourceInterface $resource
     *   Resource to lock
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
            
            $permissions[$this->normalizer->apply($name)] = $this->getPermission($resource) ?? 0;
            $this->addAttribute(self::ACL_ATTRIBUTE_IDENTIFIER, $permissions);
            $this->locked[$name] = true;
            
            return;
        }
        
        $this->locked[$resource->getName()] = true;
        $this->addAttribute(self::ACL_ATTRIBUTE_IDENTIFIER, [$this->normalizer->apply($resource->getName()) => $this->getPermission($resource) ?? 0]);            
    }
    
    /**
     * Check permissions for this user has been locked.
     * If locked, no modification are possible on the user on the resource
     * 
     * @param ResourceInterface $resource
     *   Resource to check
     * 
     * @return bool
     *   True if the permission cannot be modified. False otherwise
     */
    public function isLocked(ResourceInterface $resource): bool
    {
        $name = $resource->getName();
        return $this->locked[$name] 
                    ?? $this->locked[$name] = ( (null !== $permissions = $this->getAttribute(self::ACL_ATTRIBUTE_IDENTIFIER)) ? isset($permissions[$this->normalizer->apply($name)]) : false );
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
