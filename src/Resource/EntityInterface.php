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

use Zoe\Component\Acl\Exception\EntityValueNotFoundException;

/**
 * Entity is registered into a resource as a collection of permissions attributed to a value
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface EntityInterface extends \IteratorAggregate, \Countable
{
    
    /**
     * Get entity name
     * 
     * @return string
     *   Entity name
     */
    public function getName(): string;
    
    /**
     * Get a value from the entity
     * 
     * @param string $value
     *   Value name
     * 
     * @return array
     *   All permission applied to this entity value
     *   
     * @throws EntityValueNotFoundException
     *   When the value is not registered
     */
    public function get(string $value): array;
    
    /**
     * Check if a value is registered into the entity
     * 
     * @param string $value
     *   Value name
     * 
     * @return bool
     *   True if the entity has the given value. False otherwiser
     */
    public function has(string $value): bool;
    
    /**
     * Get processor handling this entity.
     * Return null if no processor is setted
     * 
     * @return string|null
     *   Processor name or null
     */
    public function getProcessor(): ?string;
    
}
