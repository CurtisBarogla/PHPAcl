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
 * Helpers to set and access to entity via EntityAwareInterface
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
trait EntityAwareTrait
{
    
    /**
     * Entity dispatched via interface
     * 
     * @var EntityInterface
     */
    protected $entity;
    
    /**
     * Get entity dispatched into the component
     * 
     * @return EntityInterface
     *   Entity dispatched
     */
    public function getEntity(): EntityInterface
    {
        return $this->entity;
    }
    
    /**
     * Set the entity dispatched to the component
     *
     * @param EntityInterface $entity
     *   Entity to dispatch
     */
    public function setEntity(EntityInterface $entity): void
    {
        $this->entity = $entity;
    }
    
}
