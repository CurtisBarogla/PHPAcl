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

namespace Ness\Component\Acl;

use Ness\Component\User\UserInterface;
use Ness\Component\Acl\Exception\PermissionNotFoundException;
use Ness\Component\Acl\Exception\ResourceNotFoundException;

/**
 * Manage autorisations/permission over User and Resource.
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclInterface
{
    
    /**
     * Check if a user is allowed to perform action on a resource
     * 
     * @param UserInterface $user
     *   User to check the permissions
     * @param string|AclBindableInterface $resource
     *   Resource name or an acl bindable component
     * @param string $permission
     *   Permission to check
     * @param \Closure|null $update
     *   Update rights of the user over the given resource before the acl made its decision. 
     *   Permissions updated here MUST NOT be propagated to other isAllowed calls
     *   This Closure takes as parameter the user currently processed by the acl.
     *   Takes as second parameter, if, and only if, the given resource is and AclBindableInterface component, the component
     *   If resource is an AclBindableInterface component and $update is provided, $update HAS the last word no matter what
     *   MUST return a boolean
     * 
     * @return bool
     *   True if the user can perform all actions over given permissions
     * 
     * @throws \TypeError
     *   When given resource is not a string or an AclBindableInterface
     * @throws PermissionNotFoundException
     *   When given permission is not checkable
     * @throws ResourceNotFoundException
     *   When the given resource does not exist
     */
    public function isAllowed(UserInterface $user, $resource, string $permission, ?\Closure $update = null): bool;
    
}
