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
    
}
