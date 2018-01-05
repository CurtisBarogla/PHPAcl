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

namespace Zoe\Component\Acl\Loader;

use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\Exception\ResourceNotFoundException;

/**
 * Responsible to load resource at runtime
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
     *   Resource name to load
     *   
     * @return ResourceInterface
     *   Loaded resource
     *   
     * @throws ResourceNotFoundException
     *   When the resource cannot be loaded
     */
    public function load(string $resource): ResourceInterface;
    
}
