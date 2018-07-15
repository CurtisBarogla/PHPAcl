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
 * User interacting with the acl component
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclUserInterface extends UserInterface
{
    
    /**
     * Identifier to access permission setted into the user's attributes
     * 
     * @var string
     */
    public const ACL_ATTRIBUTE_IDENTIFIER = "ACL_RESOURCE";
    
    /**
     * Get permission accorded
     * 
     * @return int
     *   Value representing the permission
     */
    public function getPermission(): int;
    
    /**
     * Set permission accorded
     * 
     * @param int $permission
     *   Value representing the permission
     */
    public function setPermission(int $permission): void;
    
    /**
     * Grant permission over the resource setted into the next call of to
     * 
     * @param string|array $permissions
     *   Permission to grant
     *   
     * @return AclUserInterface
     *   Fluent
     *   
     * @throws \TypeError
     *   When permission is neither a string or an array
     */
    public function grant($permissions): AclUserInterface;
    
    /**
     * Deny permission over the resource setted into the next call of to
     *
     * @param string|array $permissions
     *   Permission to deny
     *
     * @return AclUserInterface
     *   Fluent
     *   
     * @throws \TypeError
     *   When permission is neither a string or an array
     */
    public function deny($permissions): AclUserInterface;
    
    /**
     * Finalize all grant an deny actions over a resource
     * 
     * @param ResourceInterface $resource
     *   Resource processed
     *   
     * @throws PermissionNotFoundException
     *   When a permission has been not setted into the resource
     */
    public function on(ResourceInterface $resource): void;
    
    /**
     * Lock permission on the resource.
     * If previous permission setting has been made and not attributed via a on() call, lock MUST attribute it before locking the user
     * 
     * @param ResourceInterface $resource
     *   Resource to lock
     */
    public function lock(ResourceInterface $resource): void;
    
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
    public function isLocked(ResourceInterface $resource): bool;
    
}
