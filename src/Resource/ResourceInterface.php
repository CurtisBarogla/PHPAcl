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

use Ness\Component\Acl\User\AclUserInterface;
use Ness\Component\Acl\Exception\ResourceNotFoundException;
use Ness\Component\Acl\Exception\PermissionNotFoundException;

/**
 * Common way to describe the resource component over the acl
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResourceInterface
{
    
    /**
     * Resource behaviour blacklist
     * 
     * @var int
     */
    public const BLACKLIST = 0;
    
    /**
     * Resource behaviour whitelist
     * 
     * @var int
     */
    public const WHITELIST = 1;
    
    /**
     * Get resource name
     * 
     * @return string
     *   Resource name
     */
    public function getName(): string;
    
    /**
     * Get behaviour of the resource
     * 
     * @return int
     */
    public function getBehaviour(): int;
    
    /**
     * Grant permission(s) to the user setted into the next call to to()
     * 
     * @param array|string $permission
     *   Permission(s) to grant
     * 
     * @return ResourceInterface
     *   Fluent
     *   
     * @throws \TypeError
     *   When type given is not an array or a string
     * @throws ResourceNotFoundException
     *   When a permission is not setted into the resource
     */
    public function grant($permission): ResourceInterface;
    
    /**
     * Special method granting all permissions
     * 
     * @return ResourceInterface
     *   Fluent
     */
    public function grantRoot(): ResourceInterface;
    
    /**
     * Deny permission(s) to the user setted into the next call to to()
     *
     * @param array|string $permission
     *   Permission(s) to deny
     *
     * @return ResourceInterface
     *   Fluent
     *
     * @throws \TypeError
     *   When type given is not an array or a string
     * @throws ResourceNotFoundException
     *   When a permission is not setted into the resource
     */
    public function deny($permission): ResourceInterface;
    
    /**
     * Finalize all operations previously setted over the given user and update its permission
     * 
     * @param AclUserInterface $user
     *   User to alter the permission
     */
    public function to(AclUserInterface $user): void;
    
    /**
     * Get all permissions declared into the resource
     * 
     * @return string[]
     *   All permissions
     */
    public function getPermissions(): array;
    
    /**
     * Get the value of a/multiple permission
     * 
     * @param string|array $permission
     *   Permission value to get. Can be either an array or a string
     * 
     * @return int
     *   Permission value
     *   
     * @throws \TypeError
     *   When given permission is not an array or a string
     * @throws PermissionNotFoundException
     *   When permission not found
     */
    public function getPermission($permission): int;
    
}
