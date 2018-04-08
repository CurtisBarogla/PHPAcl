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
use Zoe\Component\Acl\Exception\PermissionNotFoundException;
use Zoe\Component\Acl\Exception\EntryNotFoundException;

/**
 * Registered into acl.
 * Permission name and Resource name MUST respect [a-z_]+ pattern
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResourceInterface
{
    
    /**
     * Max permissions allowed to be registered
     * 
     * @var int
     */
    public const MAX_PERMISSIONS = 30;
    
    /**
     * Refer to key for accessing all permissions value
     * 
     * @var string
     */
    public const ALL = "all";
    
    /**
     * Permissions reserverd
     * 
     * @var string[]
     */
    public const PERMISSIONS_RESERVED = [self::ALL];
    
    /**
     * All permissions MUST be whitelisted
     * 
     * @var int
     */
    public const WHITELIST = 0;
    
    /**
     * All permissions are allowed and therefore must be blacklisted
     * 
     * @var int
     */
    public const BLACKLIST = 1;
    
    /**
     * Allow a set of permissions
     * 
     * @param array $permissions
     *   Permission to allow
     * 
     * @return self
     *   Fluent interface
     *   
     * @throws PermissionNotFoundException
     *   When a permission cannot be granted
     */
    public function allow(array $permissions): ResourceInterface;
    
    /**
     * Deny a set of permissions
     * 
     * @param array $permission
     *   Permission to deny
     * 
     * @return self
     *   Fluent interface
     *   
     * @throws PermissionNotFoundException
     *   When a permission cannot be denied
     */
    public function deny(array $permission): ResourceInterface;
    
    /**
     * Process allowed and denied permissions over the resource on an acl user
     * 
     * @param AclUserInterface $user
     *   User which to allow or restrict permissions
     */
    public function to(AclUserInterface $user): void;
    
    /**
     * Get a permission value from the resource
     * 
     * @param string $permission
     *   Permission name
     *  
     * @return int
     *   Permission value
     * 
     * @throws PermissionNotFoundException
     *   When a permission cannot be found
     */
    public function getPermission(string $permission): int;
    
    /**
     * Get a value from a set of permissions from the resource.
     * If permissions parameter is setted to null, must return value of all permissions setted
     *
     * @param array|null $permissions
     *   Permissions name
     *
     * @return int
     *   Permissions value
     *
     * @throws PermissionNotFoundException
     *   When a permission cannot be found
     */
    public function getPermissions(?array $permissions = null): int;
    
    /**
     * Get permissions associated to a resource entry
     * 
     * @param string $entry
     *   Entry name
     * 
     * @return array
     *   All permissions allowed to this entry
     *   
     * @throws EntryNotFoundException
     *   When given entry is not registered
     */
    public function getEntry(string $entry): array;
    
    /**
     * Get resource name
     * 
     * @return string
     *   Resource name
     */
    public function getName(): string;
    
    /**
     * Get resource behaviour.
     * For comparison operations, use one of the const defined into the interface
     * 
     * @return int
     */
    public function getBehaviour(): int;
    
}
