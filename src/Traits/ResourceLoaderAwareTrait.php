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

namespace Ness\Component\Acl\Traits;

use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface;

/**
 * Trait to make a component valid to ResourceLoaderAwareInteface
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
trait ResourceLoaderAwareTrait
{
    
    /**
     * Resource loader
     * 
     * @var ResourceLoaderInterface
     */
    private $loader;
    
    /**
     * Get linked resource loader
     * 
     * @return ResourceLoaderInterface
     *   Resource loader
     */
    public function getLoader(): ResourceLoaderInterface
    {
        return $this->loader;
    }
    
    /**
     * Link a resource loader
     * 
     * @param ResourceLoaderInterface $loader
     *   Resource loader
     */
    public function setLoader(ResourceLoaderInterface $loader): void
    {
        $this->loader = $loader;
    }
    
}
