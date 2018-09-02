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
     *   $this is not affected when called, and is up to be setted.
     *   Update rights of the user over the given resource before the acl mades its decision. <br /> 
     *   Permissions updated here MUST NOT be propagated to other isAllowed calls. <br />
     *   This Closure takes as parameter the user currently processed by the acl. <br />
     *   Takes as second parameter, if, and only if, the given resource is an AclBindableInterface component, the component. <br />
     *   If resource is an AclBindableInterface component and $update is provided, $update HAS the last word no matter what. <br />
     *   MUST return a boolean or null if no update <br />
     *   If the update returns null and the resource is an AclBindableInterface component, the acl will dump on it
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
