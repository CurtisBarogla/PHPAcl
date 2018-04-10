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

namespace Zoe\Component\Acl\User;

use Zoe\Component\User\UserInterface;

/**
 * User communicating with an acl component.
 * This user MUST never be instantiated by other component than the acl itself
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclUserInterface extends UserInterface
{
    
    /**
     * Get permissions value associated to a resource
     * 
     * @return int
     *   Permissions value
     */
    public function getPermission(): int;
    
    /**
     * Set permissions value into the user
     * 
     * @param int $permission
     *   Permissions value
     */
    public function setPermission(int $permission): void;
    
}
