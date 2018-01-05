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
 * Load a resource from php file.
 * Each php file MUST return an instance of an implementation of ResourceInterface
 * Each file name declared into the constructor MUST refer the name of the resource loaded
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NativeResourceLoader implements ResourceLoaderInterface
{
    
    /**
     * Resources loadables
     * 
     * @var array
     */
    private $loadables;
    
    /**
     * Initialize loader
     * 
     * @param array $resources
     *   Path to resource files
     *   
     * @throws \InvalidArgumentException
     *   When a file is invalid
     */
    public function __construct(array $resources)
    {
        foreach ($resources as $resource) {
            if(!\is_file($resource))
                throw new \InvalidArgumentException(\sprintf("This file '%s' does not exist",
                    $resource));
            
            $loadable = \substr($resource, \strrpos($resource, "/") + 1, -4);
            $this->loadables[$loadable] = $resource;
        }
    }
    
    /**
     * @throws \RuntimeException
     *   When file does not return a ResourceInterface
     * 
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Loader\ResourceLoaderInterface::load()
     */
    public function load(string $resource): ResourceInterface
    {
        if(!isset($this->loadables[$resource]))
            throw new ResourceNotFoundException(\sprintf("This resource '%s' cannot be loaded",
                $resource));
        
        $file = $this->loadables[$resource];
        $loaded = include_once $file;
        
        if(!$loaded instanceof ResourceInterface)
            throw new \RuntimeException(\sprintf("This acl resource file '%s' MUST return a ResourceInterface",
                $file));
            
        return $loaded;
    }

}
