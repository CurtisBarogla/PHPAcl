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

/**
 * Make a component bindable to the acl process
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclBindableComponentInterface
{
    
    /**
     * Refer to a resource registered into the acl
     * 
     * @return string
     *   Resource name
     */
    public function aclGetResourceName(): string;
    
}
