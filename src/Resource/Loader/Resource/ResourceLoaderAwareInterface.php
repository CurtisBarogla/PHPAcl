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

namespace Ness\Component\Acl\Resource\Loader\Resource;

/**
 * Make a component aware of a resource loader
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResourceLoaderAwareInterface
{
    
    /**
     * Get linked resource loader
     * 
     * @return ResourceLoaderInterface
     *   Resource loader
     */
    public function getLoader(): ResourceLoaderInterface;
    
    /**
     * Link a resource loader
     * 
     * @param ResourceLoaderInterface $loader
     *   Resource loader
     */
    public function setLoader(ResourceLoaderInterface $loader): void;
    
}
