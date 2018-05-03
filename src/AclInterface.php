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

use Zoe\Component\User\UserInterface;
use Zoe\Component\Acl\Exception\ResourceNotFoundException;
use Zoe\Component\Acl\Exception\InvalidResourceException;
use Zoe\Component\Acl\Exception\PermissionNotFoundException;

/**
 * Responsible to check permissions accorded to users over a set of resources. 
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclInterface
{
    
    /**
     * Check if an user if able to perform action over a resource.
     * Resource can be either its simple name or an AclBindableComponent. <br />
     * Resource name MUST contains only alpha caracters ([A-Za-z] accepted) <br />
     * 
     * @param UserInterface $user
     *   User which the permissions must be checked
     * @param string|AclBindableComponentInterface $resource
     *   Resource name or an AclBindableComponent
     * @param array $permissions
     *   Permissions to check
     * @param callable|null $bind
     *   Executed before the acl mades its decision over the user and the permissions setted. 
     *   MUST be executed only if given resource is a string
     * 
     * @return bool
     *   True if the user is allowed to perform action over ALL permissions given. False otherwise
     *   
     * @throws \TypeError
     *   If resource is neither a string or an AclBindableComponent
     * @throws ResourceNotFoundException
     *   If resource is not registered
     * @throws InvalidResourceException
     *   When resource name is invalid
     * @throws PermissionNotFoundException
     *   When a given permission is not registered for the given resource
     */
    public function isAllowed(UserInterface $user, $resource, array $permissions, ?callable $bind = null): bool;
    
}
