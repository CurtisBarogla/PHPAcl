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

use Zoe\Component\Acl\Exception\PermissionNotFoundException;
use Zoe\Component\Acl\User\AclUserInterface;

/**
 * Describe a resource made of permission and entries
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResourceInterface
{
    
    /**
     * Blacklist behaviour. All permissions are denied and must be granted
     * 
     * @var int
     */
    public const BLACKLIST = 0;
    
    /**
     * Whitelist behaviour. All permissions are granted and must be denied
     * 
     * @var int
     */
    public const WHITELIST = 1;
    
    /**
     * Permission name representing all permissions 
     * 
     * @var string
     */
    public const ALL_PERMISSIONS = "all";
    
    /**
     * Permissions reserved. Cannot be added, and MUST be initialized by the implementation 
     * 
     * @var array
     */
    public const RESERVED_PERMISSIONS = [self::ALL_PERMISSIONS];
    
    /**
     * 32 bits
     * 
     * @var int
     */
    public const MAX_PERMISSIONS = 31;
    
    /**
     * Get resource name
     * 
     * @return string
     *   Resource name
     */
    public function getName(): string;
    
    /**
     * Get resource behaviour.
     * For comparison operations, use one of the constants declared into the interface
     * 
     * @return int
     *   Resource behaviour
     */
    public function getBehaviour(): int;
    
    /**
     * Register a set of permissions to grant for the next call of (to).
     * Permissions can be either a raw permission or an entry value representing a set of permissions
     * 
     * @param array|string $permissions
     *   Permissions to grant. Can be either a string or a set of permissions
     * 
     * @return ResourceInterface
     *   Fluent
     *   
     * @throws PermissionNotFoundException
     *   When a permission is not registered nor as a raw or entity value
     * @throws \TypeError
     *   When given value is neither a string or an array
     */
    public function grant($permissions): ResourceInterface;
    
    /**
     * Register a set of permissions to deny for the next call of (to).
     * Permissions can be either a raw permission or an entry value representing a set of permissions
     * 
     * @param array $permissions
     *   Permissions to deny
     * 
     * @return ResourceInterface
     *   Fluent
     *   
     * @throws PermissionNotFoundException
     *   When a permission is not registered nor as a raw or entity value
     * @throws \TypeError
     *   When given value is neither a string or an array
     */
    public function deny($permissions): ResourceInterface;
    
    /**
     * Execute all grant or/and deny operations on an AclUser
     * 
     * @param AclUserInterface $user
     *   User to alter
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
     *   When given permission is not registered into the resource
     */
    public function getPermission(string $permission): int;
    
    /**
     * Get a set of permissions value from the resource
     *
     * @param array $permissions
     *   Permissions name
     *
     * @return int
     *   Permissions value
     *
     * @throws PermissionNotFoundException
     *   When a given permission is not registered into the resource
     */
    public function getPermissions(array $permissions): int;
    
}
