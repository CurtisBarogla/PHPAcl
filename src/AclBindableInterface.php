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

use Zoe\Component\Acl\User\AclUserInterface;

/**
 * Make a component bindable to the acl
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclBindableInterface
{
    
    /**
     * Executed when the acl made his decision over a set of permissions over the resource
     * 
     * @param AclUserInterface $user
     *   User processed
     * @param bool $granted
     *   Set to true if the user is already granted over all permissions
     * 
     * @return array[callable|null]|null
     *   Can return an array containing callables or null, or null to skip the binding process.
     *   Each callable take as parameters the AclUserInterface and the resource currently processed
     */
    public function _onBind(AclUserInterface $user, bool $granted): ?array;
    
    /**
     * Resource name.
     * Must refer to a declared one into the acl
     * 
     * @return string
     *   Resource name
     */
    public function _getResourceName(): string;
    
}
