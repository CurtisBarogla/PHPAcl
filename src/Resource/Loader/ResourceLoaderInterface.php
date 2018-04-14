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

namespace Zoe\Component\Acl\Resource\Loader;

use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\Exception\ResourceNotFoundException;
use Zoe\Component\Acl\Resource\ResourceCollection;
use Zoe\Component\Acl\Resource\ResourceCollectionInterface;

/**
 * Responsible to load a resource from varios format
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResourceLoaderInterface
{
    
    /**
     * Load a resource by its name
     * 
     * @param string $resource
     *   Resource name  
     *
     * @return ResourceInterface
     *   Resource corresponding initialized
     *   
     * @throws ResourceNotFoundException
     *   When no resource correspond to a loadable one
     */
    public function load(string $resource): ResourceInterface;
    
    /**
     * Load a resources collection 
     * 
     * @param string $collection
     *   Collection name
     *   
     * @return ResourceCollection
     *   The entire collection
     *   
     * @throws ResourceNotFoundException
     *   If the collection cannot be loaded
     */
    public function loadCollection(string $collection): ResourceCollectionInterface;
    
}
