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

namespace Zoe\Component\Acl;

use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\User\UserInterface;

/**
 * Native implementation of AclInteraction
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class AclInteraction implements AclInteractionInterface
{
    
    /**
     * Acl user
     * 
     * @var AclUserInterface
     */
    private $user;
    
    /**
     * Resource
     * 
     * @var ResourceInterface
     */
    private $resource;
    
    /**
     * Stored permissions setted by a call to isAllowed
     * 
     * @var array|null
     */
    private $current = null;
    
    /**
     * Current status of isAllowed
     * 
     * @var bool|null
     */
    private $allowed = null;
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\AclInteractionInterface::grant()
     */
    public function grant(array $permissions = []): void
    {
        $this->set("allow", $permissions);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\AclInteractionInterface::deny()
     */
    public function deny(array $permissions = []): void
    {
        $this->set("deny", $permissions);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\AclInteractionInterface::isAllowed()
     */
    public function isAllowed(array $permissions): AclInteractionInterface
    {
        $value = (isset($permissions[1])) ? $this->resource->getPermissions($permissions) : $this->resource->getPermission($permissions[0]);
        
        $this->allowed = (bool) ( ($this->user->getPermission() & $value) === $value );
        $this->current = $permissions;
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\AclInteractionInterface::isNotAllowed()
     */
    public function isNotAllowed(array $permissions): AclInteractionInterface
    {
        return $this->isAllowed($permissions);
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\AclInteractionInterface::getUser()
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }
    
    /**
     * Set the user for the interaction process
     * 
     * @param AclUserInterface $user
     *   Acl user
     */
    public function setUser(AclUserInterface $user): void
    {
        $this->user = $user;
    }
    
    /**
     * Set the resource for the interaction process
     * 
     * @param ResourceInterface $resource
     *   Resource
     */
    public function setResource(ResourceInterface $resource): void
    {
        $this->resource = $resource;
    }
    
    /**
     * Perform permission modification on a user
     * 
     * @param string $action
     *   Action to set
     * @param array $permissions
     *   Permission to set
     */
    private function set(string $action, array $permissions): void
    {
        $executable = null === $this->allowed;
        $executable = $executable || ($action === "allow") ? !$this->allowed : $this->allowed;
        
        if(!$executable) {
            $this->clear();

            return;
        }
            
        $permissions = (null !== $this->current) ? $this->current : $permissions;
        
        $this->resource->{$action}($permissions)->to($this->user);
        
        $this->clear();
    }
    
    /**
     * Clear properties after actions
     */
    private function clear(): void
    {
        $this->current = null;
        $this->allowed = null;
    }
    
}
