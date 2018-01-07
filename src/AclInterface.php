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
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\Exception\ResourceNotFoundException;
use Zoe\Component\Acl\Exception\PermissionNotFoundException;

/**
 * Entry to check user permissions over a resource
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclInterface
{
    
    /**
     * Get a resource by its name
     * 
     * @param string $resource
     *   Resource name
     * 
     * @return ResourceInterface
     *   Resource loaded
     *   
     * @throws ResourceNotFoundException
     *   When no resource has been found for this name
     */
    public function getResource(string $resource): ResourceInterface;
    
    /**
     * Check if a user has permissions to perform action over a resource 
     * 
     * @param UserInterface $user
     *   User to check the permissions
     * @param string $resource
     *   Resource name
     * @param array $permissions
     *   Permissions to check
     * @param callable|null $bind
     *   Callback called before each decision. Can be null. <br />
     *   Take as parameters the user and if the user is already granted over the given permissions<br />
     *   This callback can return either null or an other callback that take as parameters the user and the resource <br />
     *  
     * @return bool
     *   True if the user is able to perform action. False otherwise
     *   
     * @throws ResourceNotFoundException
     *   When resource is invalid
     * @throws PermissionNotFoundException
     *   When a permission is invalid
     */
    public function isAllowed(UserInterface $user, string $resource, array $permissions = [], ?callable $bind = null): bool;
    
    /**
     * Bind a component bindable on the acl
     * 
     * @param AclBindableInterface $bindable
     *   Bindable component
     */
    public function bind(AclBindableInterface $bindable): void;
    
}
