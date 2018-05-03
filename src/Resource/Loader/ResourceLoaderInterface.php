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
use Zoe\Component\Acl\Resource\ResourceCollectionInterface;
use Zoe\Component\Acl\Exception\ResourceNotFoundException;

/**
 * Responsible to get resource representation from external sources.
 * Source can be a file (php, yml, xml...), database connection, whatever...
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResourceLoaderInterface
{
    
    /**
     * Represent prefix which allow the loader if a collection or a single resource must be loaded
     * 
     * @var char
     */
    public const COLLECTION_IDENTIFIER = '_';
    
    /**
     * Load a resource or a collection.
     * If resource name given contains the collection identifier prefix, a collection MUST be returned
     * 
     * @param string $resource
     *   Resource/collection to load
     * 
     * @return ResourceInterface|ResourceCollectionInterface
     *   Collection/Resource loaded
     *   
     * @throws ResourceNotFoundException
     *   If resource given does not refer to a loadable one
     */
    public function load(string $resource);
    
}
