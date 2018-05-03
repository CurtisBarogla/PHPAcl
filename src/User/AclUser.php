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

use Zoe\Component\User\UserInterface;

/**
 * Native implementation of AclUserInterface.
 * Simple wrapper over an UserInterface
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
final class AclUser implements AclUserInterface
{
    
    /**
     * User wrapped
     * 
     * @var UserInterface
     */
    private $user;
    
    /**
     * Current permission
     * 
     * @var int
     */
    private $permissions = 0;
    
    /**
     * Initialize an acl user
     * 
     * @param UserInterface $user
     *   Basic user wrapped
     */
    public function __construct(UserInterface $user)
    {
        $this->user = $user;
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
     * @see \Zoe\Component\Acl\User\AclUserInterface::getPermission()
     */
    public function getPermission(): int
    {
        return $this->permissions;
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\User\AclUserInterface::setPermission()
     */
    public function setPermission(int $permission): void
    {
        $this->permissions = $permission;
    }

}
