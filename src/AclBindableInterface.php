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
    
}
