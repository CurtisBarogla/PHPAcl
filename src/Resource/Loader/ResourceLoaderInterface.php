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

namespace Ness\Component\Acl\Resource\Loader;

use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Exception\ResourceNotFoundException;

/**
 * Responsible to load resources from multiple sources
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
     *   Resource loaded
     *   
     * @throws ResourceNotFoundException
     *   When given resource name does not refer to a loadable one
     */
    public function load(string $resource): ResourceInterface;
    
}
