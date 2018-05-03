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
 * Represent a user interacting with an acl component.
 * This user SHOULD never be accessed/instantiated directly, except via an external acl component
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclUserInterface extends UserInterface
{
    
    /**
     * Get permission bit value accorded for a resource
     * 
     * @return int
     *   Bit permission value
     */
    public function getPermission(): int;
    
    
    /**
     * Set permission bit value accorded for a resource
     * 
     * @param int $permission
     *   Bit permission value
     */
    public function setPermission(int $permission): void;
    
}
