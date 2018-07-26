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

/**
 * Allow a component to communicate with the acl
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclBindableInterface
{
    
    /**
     * Identify the component to the acl.
     * MUST refer a valid acl resource
     * 
     * @return string
     *   Acl resource name
     */
    public function getAclResourceName(): string;
    
    /**
     * Update a user over a permission asked by the acl
     * If true is returned, the permission will be updated depending of the resource behaviour
     * 
     * @param UserInterface $user
     *   User passed to the acl
     * @param string $permission
     *   Permission currently to verify
     * 
     * @return bool
     *   Depending of the resource behaviour, will deny or grant an exceptional permission
     */
    public function updateAclPermission(UserInterface $user, string $permission): bool;
    
}
