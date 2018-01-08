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

namespace Zoe\Component\Acl\User;

use Zoe\Component\Acl\Mask\Mask;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\User\UserInterface;
use Zoe\Component\Acl\Exception\PermissionNotFoundException;
use Zoe\Component\Acl\Exception\EntityValueNotFoundException;

/**
 * Native implementation of AclUser
 * Just basically a wrapper around a UserInterface
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
final class AclUser implements AclUserInterface
{
    
    /**
     * Permission mask for a resource
     * 
     * @var Mask
     */
    private $permissions;
    
    /**
     * Wrapped user
     * 
     * @var UserInterface
     */
    private $user;
    
    /**
     * Initialize acl user
     * 
     * @param Mask $permission
     *   Permission mask for resource
     * @param UserInterface $user
     *   User wrapped
     */
    public function __construct(Mask $permission, UserInterface $user)
    {
        $this->user = $user;
        $this->permissions = $permission;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\User\UserInterface::getName()
     */
    public function getName(): string
    {
        return $this->user->getName();
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\User\UserInterface::isRoot()
     */
    public function isRoot(): bool
    {
        return $this->user->isRoot();
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\User\UserInterface::addAttribute()
     */
    public function addAttribute(string $attribute, $value): void
    {
        $this->user->addAttribute($attribute, $value);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\User\UserInterface::getAttributes()
     */
    public function getAttributes(): ?iterable
    {
        return $this->user->getAttributes();
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\User\UserInterface::getAttribute()
     */
    public function getAttribute(string $attribute)
    {
        return $this->user->getAttribute($attribute);
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\User\UserInterface::hasAttribute()
     */
    public function hasAttribute(string $attribute): bool
    {
        return $this->user->hasAttribute($attribute);
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\User\UserInterface::getRoles()
     */
    public function getRoles(): ?iterable
    {
        return $this->user->getRoles();
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\User\UserInterface::hasRole()
     */
    public function hasRole(string $role): bool
    {
        return $this->user->hasRole($role);
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\User\AclUserInterface::grant()
     */
    public function grant(ResourceInterface $resource, array $permissions): void
    {
        $this->set($resource, $permissions, "add");
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\User\AclUserInterface::deny()
     */
    public function deny(ResourceInterface $resource, array $permissions): void
    {
        $this->set($resource, $permissions, "sub");
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\User\AclUserInterface::getPermission()
     */
    public function getPermission(): Mask
    {
        return $this->permissions;
    }
    
    /**
     * Move permissions mask for binding
     */
    public function __clone()
    {
        $this->permissions = clone $this->permissions;
    }
    
    /**
     * Determine permissions to set into the permission mask
     * 
     * @param ResourceInterface $resource
     *   Resource processed
     * @param array $permissions
     *   Permissions applied
     * @param string $action
     *   Action done on the mask (add or sub)
     * 
     * @throws PermissionNotFoundException
     *   When a permission cannot be resolved as a raw permission nor an entity value
     */
    private function set(ResourceInterface $resource, array $permissions, string $action): void
    {        
        foreach ($permissions as $permission) {
            try {
                $this->permissions->{$action}($resource->getPermission($permission));
            } catch (PermissionNotFoundException $e) {
                $entities = $resource->getEntities();
                if(null === $entities)
                    throw new PermissionNotFoundException($this->getExceptionMessageForInvalidPermission($permission, $action, $resource, true));

                while ($entity = \array_shift($entities)) {
                    try {
                        $toSet = $entity->get($permission);
                        $this->permissions->{$action}($resource->getPermissions($toSet)->total());
                        
                        break;
                    } catch (EntityValueNotFoundException $e) {
                        if(empty($entities))
                            throw new PermissionNotFoundException($this->getExceptionMessageForInvalidPermission($permission, $action, $resource, false));
                        
                        continue;
                    } catch (PermissionNotFoundException $e) {
                        throw new PermissionNotFoundException(\sprintf("This permission '%s' for value '%s' into entity '%s' setted into '%s' resource is not valid",
                            $e->getInvalidPermission(),
                            $permission,
                            $entity->getName(),
                            $resource->getName()));
                    }
                }
            }
        }
    }
    
    /**
     * Determine exception message
     * 
     * @param string $permission
     *   Invalid permission
     * @param string $action
     *   Action done on permission mask
     * @param ResourceInterface $resource
     *   Resource processed
     * @param bool $raw
     *   If permission if from the resource itself and not from an entity associated to it
     * 
     * @return string
     *   Exception message
     */
    private function getExceptionMessageForInvalidPermission(string $permission, string $action, ResourceInterface $resource, bool $raw): string
    {
        // ugly... i know
        $args = [$permission, ($action === "add") ? "granted" : "denied", $resource->getName()];
        if($raw) {
            return \sprintf("This permission '%s' cannot be '%s' as it is not defined as a raw permission into resource '%s'",
                ...$args);
        } else {
            return \sprintf("This permission '%s' cannot be '%s' as it is not defined as a raw permission nor an entity value into resource '%s'",
                ...$args);
        }
    }
    
}
