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

use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Exception\ResourceNotFoundException;
use Ness\Component\Acl\Exception\ParseErrorException;
use Ness\Component\Acl\Resource\Resource;
use Ness\Component\Acl\Resource\ExtendableResource;
use Ness\Component\Acl\Traits\FileLoaderTrait;

/**
 * Initialize resource from a php file/or a directory of php files.
 * This file can either return an array describing ONE resource or an instance of a ResourceInterface implementation
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class PhpFileResourceLoader implements ResourceLoaderInterface
{
    
    use FileLoaderTrait;
    
    /**
     * An array of file paths 
     * 
     * @var string[]
     */
    private $files;
    
    /**
     * All resources loadables
     * 
     * @var ResourceInterface[]
     */
    private $loadables;
    
    /**
     * If files has been builded into local property
     * 
     * @var bool
     */
    private $builded = false;
    
    /**
     * Resources that need to be extended
     * 
     * @var array
     */
    private $toExtends = [];
    
    /**
     * Initialize resource loader.
     * 
     * @param array $files
     *   Can be either a simple php file or a directory containing a set of php files.
     *   Each file MUST refer the resource name
     *   
     * @throws \LogicException
     *   When a file is neither a valid directory or file
     */
    public function __construct(array $files)
    {
        $this->files = $files;
        
        $this->checkFiles();
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface::load()
     */
    public function load(string $resource): ResourceInterface
    {   
        if(!$this->builded) {
            $this->buildFiles();
            
            foreach ($this->files as $name => $file)               
                $this->buildLoadable($file, $name, $this->inject($file)());
            
            unset($this->files);
            $this->builded = true;
        }
        
        if(!isset($this->loadables[$resource]))
            throw new ResourceNotFoundException("This resource '{$resource}' cannot be loaded via this loader");
        
        return isset($this->toExtends[$resource]) 
            ? ExtendableResource::buildFromBasicResource($this->loadables[$resource])->extendsFrom($this->load($this->toExtends[$resource])) 
            : $this->loadables[$resource];
    }
    
    /**
     * Parse a resource array
     * 
     * @param string $file
     *   Filename
     * @param string $name
     *   Resource name
     * @param array $resource
     *   Array representing a resource
     *   
     * @throws ParseErrorException
     *   When an invalid key is given
     */
    private function parse(string $file, string $name, array $resource): ResourceInterface
    {
        switch ($resource["behaviour"] ?? "whitelist") {
            case "whitelist":
                $behaviour = ResourceInterface::WHITELIST;
                break;
            case "blacklist":
                $behaviour = ResourceInterface::BLACKLIST;
                break;
            default:
                throw new ParseErrorException(\sprintf("Resource behaviour given into file '%s' for resource '%s' is invalid. Valids values are whitelist or blacklist. '%s' given",
                    $file,
                    $name,
                    (\is_object($resource["behaviour"]) ? \get_class($resource["behaviour"]) : ( (\is_string($resource["behaviour"]) ? $resource["behaviour"] : \gettype($resource["behaviour"]))) )));
        }
        $instance = new Resource($name, $behaviour);
        if(isset($resource["extends"]))
            $this->toExtends[$name] = $resource["extends"];
        if(isset($resource["permissions"])) {
            if(!\is_array($resource["permissions"]))
                throw new ParseErrorException("Permissions setted for resource '{$name}' MUST be an array into file '{$file}'");
            
            foreach ($resource["permissions"] as $permission)
                $instance->addPermission($permission);
        }
        
        return $instance;
    }
    
    /**
     * Convert all resource files not matter the returning value into a resource
     * 
     * @param string $file
     *   Filepath
     * @param string $name
     *   Resource name
     * @param ResourceInterface|array
     *   Value of a loadable file
     * 
     * @throws \LogicException
     *   When the given file does not return a valid value
     */
    private function buildLoadable(string $file, string $name, $fileValue): void
    {
        if(!\is_array($fileValue) && !$fileValue instanceof ResourceInterface)
            throw new \LogicException("File '{$file}' does not return a value handled by this loaded. It must return an array or an instance of ResourceInterface");
            
        // resource is an instance of ResourceInterface
        if($fileValue instanceof ResourceInterface) {
            if($name !== $fileValue->getName())
                throw new \LogicException("Resource instance name '{$fileValue->getName()}' not concordant with filename '{$name}' into file '{$file}'");
            
            $this->loadables[$fileValue->getName()] = $fileValue;
            
            return;
        }
        
        // it's a simple array representing the class. The file name is the resource name
        if(empty($fileValue) || !empty(\array_intersect_key(\array_flip(["behaviour", "extends", "permissions"]), $fileValue))) {
            $resource = $this->parse($file, $name, $fileValue);
            $this->loadables[$resource->getName()] = $resource;
            
            return;
        }
        
        // multiple resources into same file
        foreach ($fileValue as $index => $resource) {
            // we assume at this point that the index is the resource name
            if(\is_string($index)) {
                $resource = $this->parse($file, $index, $resource);
                $this->loadables[$resource->getName()] = $resource;
            } else if($resource instanceof ResourceInterface)
                // instance of ResourceInterface
                $this->loadables[$resource->getName()] = $resource;
            else 
                throw new \LogicException(\sprintf("Invalid value given into file '%s' as resource. MUST be an array indexed by the resource name or an instance of ResourceInterface. '%s' given",
                    $file,
                    (\is_object($resource) ? \get_class($resource) : \gettype($resource))));
        }
    }
    
    /**
     * Inject a resource file
     * 
     * @param string $file
     *   File to inject
     * 
     * @return \Closure
     *   Closure with new scope attached to it
     */
    private function inject(string $file): \Closure
    {
        return \Closure::bind(function() use ($file) {
            return include $file;
        }, new class($this->toExtends) {
            
            /**
             * Reference to extendables resources
             *
             * @var string[]
             */
            private $extendReference;
            
            /**
             * Initialize extender
             *
             * @param array& $extendReference
             *   Reference to local extendables resources array
             */
            public function __construct(array& $extendReference)
            {
                $this->extendReference =& $extendReference;
            }
            
            /**
             * Extends the parent resource to the resource
             *
             * @param string $parent
             *   Parent resource name to extends
             * @param ResourceInterface $resource
             *   Resource name which the parent is inherited
             *
             * @return ResourceInterface
             *   Resource added
             */
            public function extendsFromTo(string $parent, ResourceInterface $resource): ResourceInterface
            {
                $this->extendReference[$resource->getName()] = $parent;
                
                return $resource;
            }
            
        });
    }
    
}
