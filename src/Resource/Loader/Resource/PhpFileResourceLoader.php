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

/**
 * Initialize resource from a php file/or a directory of php files.
 * This file can either return an array describing ONE resource or an instance of a ResourceInterface implementation
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class PhpFileResourceLoader implements ResourceLoaderInterface
{
    
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
     */
    public function __construct(array $files)
    {
        $this->files = $files;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface::load()
     */
    public function load(string $resource): ResourceInterface
    {   
        if(!$this->builded) {
            $this->buildFiles();
            
            $inject = \Closure::bind(function(string $file, array& $extendsReference) {
                $extends = function(string $extends, ResourceInterface $resource) use (&$extendsReference): ResourceInterface {
                    $extendsReference[$resource->getName()] = $extends;  
                    
                    return $resource;
                };
                
                return include $file;
            }, null);
            
            foreach ($this->files as $name => $file)               
                $this->buildLoadable($file, $name, $inject($file, $this->toExtends));
            
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
        $instance = new Resource($name, $resource["behaviour"] ?? ResourceInterface::WHITELIST);
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
     * Build files into local file property to be used on next call of load
     * 
     * @throws \LogicException
     *   When a file or a directory does nos exist
     */
    private function buildFiles(): void
    {
        foreach ($this->files as $index => $file) {
            if(!\file_exists($file))
                throw new \LogicException("This file '{$file}' is neither a directory or a file");
            
            if(\is_dir($file)) {
                foreach (new \DirectoryIterator($file) as $file) {
                    if($file->isDot()) continue;                    
                    $this->files[$file->getBasename(".php")] = $file->getPathname();
                }
            } else {
                $this->files[(new \SplFileInfo($file))->getBasename(".php")] = $file;
            }
            
            unset($this->files[$index]);
        }
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
        
        // it a simple array representing the class. The file name is the resource name
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
    
}
