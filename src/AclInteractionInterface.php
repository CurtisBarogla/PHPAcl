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

/**
 * Interaction between an AclUser and an AclInterface
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclInteractionInterface 
{
    
    /**
     * Grant the binded user a set of permissions over a binded resource.
     * Must grant permissions in priority permissions asked by a precedent call of isAllowed
     * 
     * @param array|null $permissions
     *   Permissions to allow. Let empty to grant permissions asked by isAllowed 
     */
    public function grant(array $permissions = []): void;

    /**
     * Deny the binded user a set of permissions over a binded resource.
     * Must deny permissions in priority permissions asked by a precedent call of isAllowed
     * 
     * @param array|null $permissions
     *   Permissions to deny. Let empty to grant permissions asked by isAllowed 
     */
    public function deny(array $permissions = []): void;
    
    /**
     * Check if the user is allowed to perform actions
     * 
     * @param array $permissions
     *   Permissions to check
     * 
     * @return AclInteractionInterface
     *   Fluent
     */
    public function isAllowed(array $permissions): AclInteractionInterface;
    
    /**
     * Check if the user is not allowed to perform actions
     *
     * @param array $permissions
     *   Permissions to check
     *
     * @return AclInteractionInterface
     *   Fluent
     */
    public function isNotAllowed(array $permissions): AclInteractionInterface;
    
    /**
     * Get binded user
     * 
     * @return UserInterface
     *   User
     */
    public function getUser(): UserInterface;
        
}
