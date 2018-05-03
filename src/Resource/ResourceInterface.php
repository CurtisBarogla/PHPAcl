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
use Zoe\Component\Acl\Exception\InvalidPermissionException;

/**
 * Represent a resource.
 * Resource holds a set of permissions and entries and handle an user over its permissions.
 * Directly communicate with the acl component 
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResourceInterface
{
    
    /**
     * Blacklist behaviour.
     * All permissions are allowed and must be denied one by one for the user
     * 
     * @var int
     */
    public const BLACKLIST = 0;
    
    /**
     * Whitelist behaviour
     * All permission are denied and must be allowed one by one for the user
     * 
     * @var int
     */
    public const WHITELIST = 1;
    
    /**
     * Identifier representing all permissions defined into the resource
     * 
     * @var string
     */
    public const ALL_PERMISSIONS = "all";
    
    /**
     * Permissions identifier reserved.
     * Implementation MUST initialize all this permissions or MUST thrown a LogicException
     * 
     * @var string[]
     */
    public const RESERVED_PERMISSIONS = [self::ALL_PERMISSIONS];
    
    /**
     * Max permissions allowed to be registered excluded reserved one
     * 
     * @var int
     */
    public const MAX_PERMISSIONS = 31;
    
    /**
     * Error code when a permissions is reserved
     * 
     * @var int
     */
    public const RESOURCE_ERROR_CODE_RESERVED_PERMISSION = 0;
    
    /**
     * Error code when a permissions is invalid
     *
     * @var int
     */
    public const RESOURCE_ERROR_CODE_INVALID_PERMISSION = 1;
    
    /**
     * Error code when a permissions is already registered
     *
     * @var int
     */
    public const RESOURCE_ERROR_CODE_ALREADY_REGISTERED_PERMISSION = 2;
    
    /**
     * Error code when max registered permissions count is reached
     *
     * @var int
     */
    public const RESOURCE_ERROR_CODE_MAX_PERMISSIONS_REACHED = 3;
    
    /**
     * Get resource name
     * 
     * @return string
     *   Resource name
     */
    public function getName(): string;
    
    /**
     * Get resource behaviour.
     * Return its int representation. For comparison operation, use one on the const defined into the interface
     * 
     * @return int
     *   Int reprensentation of the resource behaviour
     */
    public function getBehaviour(): int;
    
    /**
     * Grant a/set of permissions to the currently setted
     * 
     * @param array|string $permission
     *   Permission/s to grant. This can be either a string or an array
     *  
     * @return ResourceInterface
     *   Fluent
     *   
     * @throws PermissionNotFoundException
     *   If a given permission is not registered
     * @throws \TypeError
     *   If given permission is neither an array or a string
     * @throws InvalidPermissionException
     *   If given permission does not respect imposed pattern [a-z_]
     * @throws \LogicException
     *   When no user has beed setted
     */
    public function grant($permission): ResourceInterface;
    
    /**
     * Deny a/set of permissions to the currently setted
     *
     * @param array|string $permission
     *   Permission/s to deny. This can be either a string or an array
     *
     * @return ResourceInterface
     *   Fluent
     *
     * @throws PermissionNotFoundException
     *   If a given permission is not registered
     * @throws \TypeError
     *   If given permission is neither an array or a string
     * @throws InvalidPermissionException
     *   If given permission does not respect imposed pattern [a-z_]
     * @throws \LogicException
     *   When no user has beed setted
     */
    public function deny($permission): ResourceInterface;
    
    /**
     * Set the user for further permissions modifications 
     * 
     * @param AclUserInterface $user
     *   User which permission are modified
     *   
     * @return ResourceInterface
     *   Fluent
     */
    public function to(AclUserInterface $user): ResourceInterface;
    
    /**
     * Finalize operations done on the user for this resource
     * 
     * @throws \LogicException
     *   When no user has been defined
     */
    public function finalize(): void;
    
    /**
     * Get bit representation of a permission
     * 
     * @param string $permission
     *   Permission name
     * 
     * @return int
     *   Bit representation of the permission
     * 
     * @throws PermissionNotFoundException
     *   When given permission is not registered into the resource
     */
    public function getPermission(string $permission): int;
    
    /**
     * Get bit representation of a set of permissions
     * 
     * @param array $permissions
     *   Set of permissions
     * 
     * @return int
     *   Bit representation of the permissions
     *   
     * @throws PermissionNotFoundException
     *   When a permission if not registered into the resource
     */
    public function getPermissions(array $permissions): int;
    
}
