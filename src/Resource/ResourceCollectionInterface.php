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
 * Represent a set of resources sharing same purpose
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResourceCollectionInterface extends \IteratorAggregate
{
    
    /**
     * Get collection name
     * 
     * @return string
     *   Collection name
     */
    public function getName(): string;
    
    /**
     * Get a resource registered into the collection
     * 
     * @param string $resource
     *   Resource name
     * 
     * @return ResourceInterface
     *   Resource stored
     *   
     * @throws ResourceNotFoundException
     *   When given resource is not registered
     */
    public function getResource(string $resource): ResourceInterface;
    
}
