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
 * Make a component bindable to the acl
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclBindableInterface
{
    
    /**
     * Action done on the component before the acl makes his decision
     * 
     * @param AclInteractionInterface $interaction
     *   Interaction between acl and user over a resource
     */
    public function _onBind(AclInteractionInterface $interaction): void;
    
    /**
     * Refer to a resource loadable by the acl
     * 
     * @return string
     *   Resource name which the component refer
     */
    public function _getResourceName(): string;
    
}
