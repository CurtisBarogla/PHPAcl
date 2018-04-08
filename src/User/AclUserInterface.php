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
 * User interacting with the acl.
 * This user SHOULD NOT be instantiated except by an AclInterface component
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclUserInterface extends UserInterface
{
    
    /**
     * Get permission currently accorded over a resource
     * 
     * @return int
     *   Permission mask
     */
    public function getPermission(): int;
    
    /**
     * Register permission bit mask over a resource
     * 
     * @param int $permission
     *   Permission to set
     */
    public function setPermission(int $permission): void;
    
}
