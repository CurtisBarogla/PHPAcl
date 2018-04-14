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

use Zoe\Component\Acl\Exception\ResourceNotFoundException;

/**
 * Represent a collection of resources
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResourceCollectionInterface extends \IteratorAggregate
{
    
    /**
     * Get an unique resource collection identifier
     * 
     * @return string
     *   Resource identifier
     */
    public function getIdentifier(): string;
    
    /**
     * Get a resource from the collection
     * 
     * @param string $resource
     *   Resource name
     *   
     * @return ResourceInterface
     *   Resource registered
     *   
     * @throws ResourceNotFoundException
     *   If given resource is not registered
     */
    public function get(string $resource): ResourceInterface;
    
}
