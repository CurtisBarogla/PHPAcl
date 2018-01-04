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

namespace Zoe\Component\Acl\Resource;

/**
 * Make a component aware of an entity
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface EntityAwareInterface
{
    
    /**
     * Get entity dispatched into the component
     * 
     * @return EntityInterface
     *   Entity dispatched
     */
    public function getEntity(): EntityInterface;
    
    /**
     * Set the entity dispatched to the component
     * 
     * @param EntityInterface $entity
     *   Entity to dispatch
     */
    public function setEntity(EntityInterface $entity): void;
    
}
