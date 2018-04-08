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

/**
 * Interact with Resource and AclUser
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclInterface
{
    
    /**
     * Check if a user is allowed to perform actions over a resource
     * 
     * @param UserInterface $user
     *   User to check the permissions
     * @param string $resource
     *   Resource name
     * @param array $permissions
     *   Permissions to check
     * @param callable|null $bind
     *   Processed before the decision is make. Take as parameter an AclInteractionInterface
     * 
     * @return bool
     *  True if the user is allowed to perform actions over permissions setted
     *  
     * @throws ResourceNotFoundException
     *   When asked resource is not found
     */
    public function isAllowed(UserInterface $user, string $resource, array $permissions = [], ?callable $bind = null): bool;
    
    /**
     * Get a resource
     * 
     * @param string $resource
     *   Resource name
     * 
     * @return ResourceInterface
     *   Resource asked
     *   
     * @throws ResourceNotFoundException
     *   When asked resource is not found
     */
    public function getResource(string $resource): ResourceInterface;
    
    /**
     * Bind a component bindable to the acl
     * 
     * @param AclBindableInterface $bindable
     *   Bindable component
     */
    public function bind(AclBindableInterface $bindable): void;
    
}
