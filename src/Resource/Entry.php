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

/**
 * Native basic implementation of EntryInterface
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class Entry implements EntryInterface
{
    
    /**
     * Entry name
     * 
     * @var string
     */
    private $name;
    
    /**
     * Permissions setted
     * 
     * @var string[]
     */
    private $permissions;
    
    /**
     * Initialize an acl entry
     * 
     * @param string $name
     *   Entry name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\EntryInterface::getName()
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\EntryInterface::getPermissions()
     */
    public function getPermissions(): array
    {
        return $this->permissions ?? [];
    }

    /**
     * Add a permission for this entry
     * 
     * @param string $permission
     *   Permission name
     * 
     * @return self
     *   Fluent
     */
    public function addPermission(string $permission): self
    {
        $this->permissions[] = $permission;
        
        return $this;
    }
    
}
