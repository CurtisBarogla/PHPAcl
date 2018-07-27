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

namespace Ness\Component\Acl;

use Ness\Component\User\UserInterface;
use Ness\Component\Acl\Exception\ResourceNotFoundException;
use Ness\Component\Acl\Exception\InvalidArgumentException;
use Ness\Component\Acl\Exception\PermissionNotFoundException;
use Psr\SimpleCache\CacheInterface;

/**
 * Simple acl.
 * For those who DEFINITELY does not like the other acl :)
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class SimpleAcl implements AclInterface
{
    
    /**
     * Reference to the last resource registered into the acl
     * 
     * @var string|null
     */
    private $currentResource = null;
    
    /**
     * Current wrapped entries
     * 
     * @var string|null
     */
    private $currentWrapProcessor = null;
    
    /**
     * Current wrap permission/entry
     * 
     * @var array|null
     */
    private $currentPipeline = null;
    
    /**
     * If next permissions/entries found must be registered into pipeline
     * 
     * @var bool
     */
    private $pipeline = false;
    
    /**
     * Resources already checked
     * 
     * @var bool[]
     */
    private $resourceChecked = null;
    
    /**
     * Reference to all resources registered into the acl
     * 
     * @var array
     */
    protected $acl;
    
    /**
     * Acl processors
     * 
     * @var \Closure[]
     */
    protected $processors = [];
    
    /**
     * Acl behaviour for further registrated resources
     * 
     * @var int
     */
    protected $behaviour;
    
    /**
     * Resource name index
     *
     * @var string
     */
    private const NAME_INDEX = "name";
    
    /**
     * Permissions index
     * 
     * @var string
     */
    private const PERMISSIONS_INDEX = "permissions";
    
    /**
     * Entries index
     *
     * @var string
     */
    private const ENTRIES_INDEX = "entries";
    
    /**
     * Behaviour index
     *
     * @var string
     */
    private const BEHAVIOUR_INDEX = "behaviour";
    
    /**
     * Processors index
     * 
     * @var string
     */
    private const PROCESS_INDEX = "processors";
    
    /**
     * Resource parent index
     *
     * @var string
     */
    private const PARENT_INDEX = "parent";
    
    /**
     * Root permission index
     * 
     * @var string
     */
    private const ROOT_INDEX = "root";
    
    /**
     * All permissions are granted be default and be denied
     * 
     * @var int
     */
    public const BLACKLIST = 0;
    
    /**
     * All permissions are denied be default and be allowed
     *
     * @var int
     */
    public const WHITELIST = 1;
    
    /**
     * Used to store the acl into the cache
     * 
     * @var string
     */
    public const CACHE_KEY = "_cached_simple_acl_";
    
    /**
     * Attribute name for storing permissions from a processed user
     * 
     * @var string
     */
    public const USER_ATTRIBUTE = "SIMPLE_ACL_PERMISSIONS";
    
    /**
     * Max permissions allowed
     * 
     * @var int
     */
    public const MAX = 31;
    
    /**
     * Initialize the acl
     * 
     * @param int $behaviour
     *   Default behaviour of the acl. By default, setted to whitelist
     * 
     * @throws InvalidArgumentException
     *   When given behaviour is no a valid one
     */
    public function __construct(int $behaviour = self::WHITELIST)
    {
        $this->assignBehaviour($behaviour);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\AclInterface::isAllowed()
     */
    public function isAllowed(UserInterface $user, $resource, string $permission, ?\Closure $update = null): bool
    {
        if($resource instanceof AclBindableInterface) {
            $update = function(UserInterface $user, bool $result) use ($permission, $resource) {
                return $resource->updateAclPermission($user, $permission, $result);
            };
            $resource = $resource->getAclResourceName();
        }
        
        $this->validateResourceName($resource);
                
        if(null === $attribute = $user->getAttribute(self::USER_ATTRIBUTE)) {
            $user->addAttribute(self::USER_ATTRIBUTE, []);
            $attribute = $user->getAttribute(self::USER_ATTRIBUTE);
        }
        
        $required = $this->getIndex($this->acl[$resource], $permission, self::PERMISSIONS_INDEX);
        if(isset($attribute["<{$resource}>"]) || (null === $update && isset($attribute[$resource])))
            return (bool) ( ( ($attribute[$resource] ?? $attribute["<{$resource}>"]) & $required) === $required );
        
        $locked = false;
        if(!isset($attribute[$resource])) {
            $mask = ($this->acl[$resource][self::BEHAVIOUR_INDEX] === self::BLACKLIST) ? $this->acl[$resource][self::ROOT_INDEX] : 0;
            foreach ($this->processors as $name => $processor) {
                $processables = $this->combineProcessable($this->acl[$resource], $name);
                $processor->call($this->getProcessorAclWrapper($mask, $locked, $resource), $user, $processables);  
                if($locked)
                    break;
            }
            
            $attribute[($locked) ? "<{$resource}>" : $resource] = $mask;
            $user->addAttribute(self::USER_ATTRIBUTE, $attribute);
        } else
            $mask = $attribute[$resource];

        $result = (bool) ( ($mask & $required) === $required);
        
        if(null === $update || $locked)
            return $result;
        
        $update = ($this->acl[$resource][self::BEHAVIOUR_INDEX] === self::BLACKLIST) 
                            ? !\Closure::bind($update, null)($user, $result) 
                            : \Closure::bind($update, null)($user, $result);
        
        return (null !== $update) ? ($update || $result && $update) : $result;
    }
    
    /**
     * Active acl pipeline.
     * Therefore, all entries or permissions found will be stored into a hash table
     * Usefull for many call to allowed with the same permissions setted into isAllowed
     */
    public function pipeline(): void
    {
        $this->pipeline = true;
    }
    
    /**
     * Clear the last pipeline call
     */
    public function endPipeline(): void
    {
        $this->currentPipeline = null;
        $this->pipeline = false;
    }
    
    /**
     * Cache the acl via a PSR-16 Cache implementation
     * 
     * @param CacheInterface $cache
     *   PSR-16 Cache implementation
     * 
     * @return bool
     *   True if the acl has been cached with success. False otherwise
     */
    public function cache(CacheInterface $cache): bool
    {
        return $cache->set(self::CACHE_KEY, \json_encode([
            "map"       =>  $this->acl,
            "behaviour" =>  $this->behaviour
        ]));
    }
    
    /**
     * Build the acl from a cached version if exists
     * 
     * @param CacheInterface $cache
     *   PSR-16 Cache implementation
     * 
     * @return bool
     *   True if the acl has been initialized from the cache. False otherwise
     */
    public function buildFromCache(CacheInterface $cache): bool
    {
        if(null !== $acl = $cache->get(self::CACHE_KEY, null)) {
            $acl = \json_decode($acl, true);
            
            $this->acl = $acl["map"];
            $this->behaviour = $acl["behaviour"];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Invalidate a cached acl
     * 
     * @param CacheInterface $cache
     *   PSR-16 Cache implementation
     */
    public function invalidateCache(CacheInterface $cache): void
    {
        $cache->delete(self::CACHE_KEY);
    }
    
    /**
     * Build the acl from a set of files.
     * File can be a directory
     * 
     * @param array $files
     *   File initializing the acl
     *   
     * @throws \LogicException
     *   When a given file is neither a directory or a file
     */
    public function buildFromFiles(array $files): void
    {
        // deny some methods into file to not alter the acl integrity 
        $include = \Closure::bind(function(string $file): void {
            include $file;
            // make sure that the curent resource cursor is cleared after the inclusion
            $this->end();
        }, new class($this) {
            
            /**
             * Acl
             *
             * @var SimpleAcl
             */
            private $acl;
            
            /**
             * Initialize wrapper
             * 
             * @param SimpleAcl $acl
             *   Current acl reference
             */
            public function __construct(SimpleAcl $acl)
            {
                $this->acl = $acl;
            }
            
            /**
             * @see \Ness\Component\Acl\SimpleAcl::addResource()
             */
            public function addResource(string $resource, ?string $parent = null): self
            {
                $this->acl->addResource($resource, $parent);
                
                return $this;
            }
            
            /**
             * @see \Ness\Component\Acl\SimpleAcl::addPermission()
             */
            public function addPermission(string $permission, ?string $resource = null): self
            {
                $this->acl->addPermission($permission, $resource);
                
                return $this;
            }
            
            /**
             * @see \Ness\Component\Acl\SimpleAcl::wrapProcessor()
             */
            public function wrapProcessor(string $processor): self
            {
                $this->acl->wrapProcessor($processor);
                
                return $this;
            }
            
            /**
             * @see \Ness\Component\Acl\SimpleAcl::addEntry()
             */
            public function addEntry(string $entry, array $permissions, ?string $resource = null): self
            {
                $this->acl->addEntry($entry, $permissions, $resource);
                
                return $this;
            }
            
            /**
             * @see \Ness\Component\Acl\SimpleAcl::endWrapProcessor()
             */
            public function endWrapProcessor(): self
            {
                $this->acl->endWrapProcessor();
                
                return $this;
            }
            
            /**
             * @see \Ness\Component\Acl\SimpleAcl::end()
             */
            public function end(): void
            {
                $this->acl->end();
            }
            
        });
        
        foreach ($files as $file) {
            if(!\file_exists($file))
                throw new \LogicException("This file '{$file}' is neither a valid file or directory");
            if(\is_dir($file)) {
                foreach (new \DirectoryIterator($file) as $file)
                    if(!$file->isDot())
                        $include($file->getPathname());
            } else 
                $include($file);
        }
    }
    
    /**
     * Change behaviour of all further registered resources
     * 
     * @param int $behaviour
     *   Behaviour to set
     *  
     * @throws InvalidArgumentException
     *   When given behaviour is no a valid one
     */
    public function changeBehaviour(int $behaviour): void
    {
        $this->assignBehaviour($behaviour);
    }
    
    /**
     * Register a processor. 
     * A processor a executed on each linked entries before the acl made its decision over a user
     * 
     * @param string $processor
     *   Processor name
     * @param \Closure $action
     *   Action to execute
     *   Takes as first parameter a user and as second an array of entries linked to this processor. Entry can be null
     */
    public function registerProcessor(string $processor, \Closure $action): void
    {
        $this->processors[$processor] = $action;
    }    
    
    /**
     * Add a resource into the acl.
     * All further permissions or entries registered will be linked to this resource until end() method is called
     * 
     * @param string $resource
     *   Resource name
     * @param string|null $parent
     *   Parent resource. All permissions and entries will be applied to this one
     * 
     * @return self
     *   Fluent
     *   
     * @throws \LogicException
     *   If a resource with the same name is already registered
     * @throws ResourceNotFoundException
     *   If given parent resource is not registered
     */
    public function addResource(string $resource, ?string $parent = null): self
    {
        if(isset($this->acl[$resource]))
            throw new \LogicException("This resource '{$resource}' is already registered into the acl");
        
        if(null !== $parent)
            if(!isset($this->acl[$parent]))
                throw new ResourceNotFoundException("This resource '{$parent}' given as parent is not registered into the acl");
        
        $this->acl[$resource] = [];
        $this->acl[$resource][self::NAME_INDEX] = $resource;
        $this->acl[$resource][self::PERMISSIONS_INDEX] = null;
        $this->acl[$resource][self::ENTRIES_INDEX] = null;
        $this->acl[$resource][self::BEHAVIOUR_INDEX] = (null !== $parent) ? $this->acl[$parent][self::BEHAVIOUR_INDEX] : $this->behaviour;
        $this->acl[$resource][self::PROCESS_INDEX] = null;
        $this->acl[$resource][self::PARENT_INDEX] = $parent;
        $this->acl[$resource][self::ROOT_INDEX] = (null !== $parent) ? $this->acl[$parent][self::ROOT_INDEX] : 0;

        $this->currentResource = $resource;
        
        return $this;
    }
    
    /**
     * Add a permission to the last added resource or given one.
     * Given one will always have the priority
     * 
     * @param string $permission
     *   Permission name
     * @param string|null $resource
     *   A specific resource or null to set the permission into the last added resource
     * 
     * @return self
     *   Fluent
     *   
     * @throws \LogicException
     *   If a permission with the same name is already registered for the given resource
     * @throws \LogicException
     *   If no resource has been setted
     * @throws \LogicException
     *   When max permissions allowed is reached
     * @throws ResourceNotFoundException
     *   When given resource is not registered
     */
    public function addPermission(string $permission, ?string $resource = null): self
    {
        $pointed = &$this->point($resource);

        if(isset($pointed[self::PERMISSIONS_INDEX][$permission]))
            throw new \LogicException("This permission '{$permission}' is already setted into resource '{$resource}'");

        $count = 0;
        $this->loopOnResource($resource, function(array $current, string $name) use (&$count, $permission, $resource): void {
            if(null !== $current[self::PERMISSIONS_INDEX]) {
                if(isset($current[self::PERMISSIONS_INDEX][$permission]))
                    throw new \LogicException("This permission '{$permission}' is already registered into parent resource '{$name}' and cannot be redeclared into resource '{$resource}'");
                $count += \count($current[self::PERMISSIONS_INDEX]);
            }
        });
        
        if($count >= self::MAX)
            throw new \LogicException("Max permissions allowed reached for resource '{$resource}'");

        $setted = $pointed[self::PERMISSIONS_INDEX][$permission] = ($count === 0) ? 1 : 1 << $count;
        $pointed[self::ROOT_INDEX] |= $setted;
        $this->acl[$resource][self::ENTRIES_INDEX]["ROOT"] = $pointed[self::ROOT_INDEX];
        
        return $this;
    }
    
    /**
     * Wrap all further registrated entries to be executed by a processor
     * 
     * @param string $processor
     *   Processor identifier
     * 
     * @return self
     *   Fluent
     */
    public function wrapProcessor(string $processor): self
    {
        $this->currentWrapProcessor = $processor; 
        
        return $this;
    }
    
    /**
     * Add an entry into the acl
     * 
     * @param string $entry
     *   Entry name
     * @param array $permissions
     *   Permissions assigned to this entry. A permission can be either a permission or a parent entry
     * @param string|null $resource
     *   A specific resource or null to set the permission into the last added resource
     * 
     * @return self
     *   Fluent
     *   
     * @throws PermissionNotFoundException
     *   If a permission cannot be assigned
     * @throws \LogicException
     *   If no resource has been setted
     * @throws ResourceNotFoundException
     *   When given resource is not registered
     */
    public function addEntry(string $entry, array $permissions, ?string $resource = null): self
    {        
        $pointed = &$this->point($resource);
        
        if($entry === "ROOT")
            throw new InvalidArgumentException("ROOT entry name is reserved and cannot be reassigned into resource '{$resource}'");
        
        $value = 0;
        foreach ($permissions as $permission) {
            try {
                $value |= $this->getIndex($pointed, $permission, self::PERMISSIONS_INDEX);                
            } catch (PermissionNotFoundException $e) {
                $value |= $this->getIndex($pointed, $permission, self::ENTRIES_INDEX);
            }
        }
        
        $pointed[self::ENTRIES_INDEX][$entry] = $value;
        
        if(null !== $this->currentWrapProcessor)
            $pointed[self::PROCESS_INDEX][$this->currentWrapProcessor][] = $entry;
        
        return $this;
    }
    
    /**
     * Finalize registration of wrapped entries
     * 
     * @return self
     *   Fluent
     */
    public function endWrapProcessor(): self
    {
        $this->currentWrapProcessor = null;
        
        return $this;
    }
    
    /**
     * Finalize registration of the last added resource
     */
    public function end(): void
    {
        $this->currentResource = null;
    }
    
    /**
     * Build a "readable" permission representation of a permission mask over an acl resource
     * 
     * @param SimpleAcl $acl
     *   Acl builded
     * @param string $resource
     *   Resource to get the permission reprensentation
     * @param int|string $mask
     *   Mask to represent. Can be either an int or an entry setted into the resource
     * @param \Closure|null $decorator
     *   Decorator. Leave to null... to get a nice echoable red and green representation
     * 
     * @return string
     *   Readable permission representation of the given mask over the resource
     *   
     * @throws ResourceNotFoundException
     *   If the resource is not setted
     * @throws \TypeError
     *   When mask is neither a string or an int
     * @throws PermissionNotFoundException
     *   When mask is a not founded resource entry
     */
    public static function buildMaskRepresentation(SimpleAcl $acl, string $resource, $mask, ?\Closure $decorator = null): string
    {
        if(!\is_string($mask) && !\is_int($mask))
            throw new \TypeError("Mask MUST be an int or a string");
        
        if(!isset($acl->acl[$resource]))
            throw new ResourceNotFoundException("This resource '{$resource}' is not registered into the acl");
        
        if(null === $decorator) {
            $decorator  = function(string& $representation, string $permission, bool $granted): void {
                $color = ($granted) ? "green" : "red";
                $representation .= "<span style=\"color:{$color}\">{$permission}</span>|";
            };
        }
            
        $representation = '';
        if(\is_string($mask))
            $mask = $acl->getIndex($acl->acl[$resource], $mask, self::ENTRIES_INDEX);
        
        $acl->loopOnResource($resource, function(array $current, string $name) use ($mask, &$representation, $decorator): void {
            foreach ($current[self::PERMISSIONS_INDEX] as $permission => $value) {
                \Closure::bind($decorator, null)($representation, $permission, (bool) ( ($mask & $value) === $value));
            }
        }, false);
    
        return $representation;
    }
    
    /**
     * Point on the current resource or the given one
     * 
     * @param string|null $resource
     *   Resource to point
     *  
     * @return &array
     *   Resource pointed
     * 
     * @throws \LogicException
     *   When no resource pointed
     * @throws ResourceNotFoundException
     *   When given resource is not registered
     */
    protected function &point(?string& $resource): array
    {
        $resource = $resource ?? $this->currentResource;
        
        if(null === $resource)
            throw new \LogicException("No resource has been declared to register permission or entries");
        
        if(!isset($this->acl[$resource]))
            throw new ResourceNotFoundException("This resource '{$resource}' is not registered into the acl");
            
        return $this->acl[$resource];
    }
    
    /**
     * Try to get the value of a permission or an entry from a resource from a resource and its parents.
     * If $what is a resource entry and this entry has been overwritten, will return the last one
     * 
     * @param array $resource
     *   Resource to check
     * @param string $what
     *   What to get
     * @param string $index
     *   Index to check. permission or entries
     * 
     * @return int
     *   Permission or entry value
     * 
     * @throws PermissionNotFoundException
     *   When entry or permission cannot be found
     */
    protected function getIndex(array $resource, string $what, string $index): int
    {
        if(isset($resource[$index][$what]))
            return $resource[$index][$what];

        if(isset($this->currentPipeline[$resource[self::NAME_INDEX]][$index][$what]))
            return $this->currentPipeline[$resource[self::NAME_INDEX]][$index][$what];
        
        $value = null;
        $visited = null;

        $this->loopOnResource($resource[self::NAME_INDEX], function(array $parent, string $name) use ($what, $index, &$value, &$visited): void {
            $visited[] = $name;
            if(null === $value && isset($parent[$index][$what])) {
                $value = $parent[$index][$what];
                unset($visited);
            }                
        });

        
        if(null === $value) {
            $type = ($index === self::PERMISSIONS_INDEX) ? "permission" : "entry";
            if(\count($visited) > 1) {
                unset($visited[0]);
                \krsort($visited);
                $exception = new PermissionNotFoundException(\sprintf("This %s '%s' is not registred into resource '%s' neither into one of its parent '%s'",
                    $type,
                    $what,
                    $resource[self::NAME_INDEX],
                    \implode(", ", $visited)));
                $exception->setPermission($what);
                
                throw $exception;
            }
            $exception = new PermissionNotFoundException("This {$type} '{$what}' is not registered into resource '{$resource[self::NAME_INDEX]}'");
            $exception->setPermission($what);
            
            throw $exception;
        }

        if($this->pipeline)
            $this->currentPipeline[$resource[self::NAME_INDEX]][$index][$what] = $value;
            
        return $value;
    }

    /**
     * Execute an action over a resource and all its parents if declared
     * 
     * @param string $resource
     *   Base resource
     * @param \Closure $action
     *   Action to execute on the given resource and its declared parents
     *   Takes as first parameter the current resource and as second its name
     * @param bool $reverse
     *   Resource order which the action is executed
     */
    protected function loopOnResource(string $resource, \Closure $action, bool $reverse = true): void
    {
        $last = $current = $this->acl[$resource];
        
        if($reverse || null === $current[self::PARENT_INDEX]) {
            unset($last);            
            $action->call($this, $current, $current[self::NAME_INDEX]);
        }
        
        if(null !== $current[self::PARENT_INDEX]) {
            while (null !== $current[self::PARENT_INDEX]) {
                $current = $this->acl[$current[self::PARENT_INDEX]];
                if($reverse)
                    $action->call($this, $current, $current[self::NAME_INDEX]);
                else 
                    $parents[] = $current[self::NAME_INDEX];
            }
            
            if($reverse)
                return;
            
            \krsort($parents);
            foreach ($parents as $parent) {
                $action->call($this, $this->acl[$parent], $parent);
            }
            $action->call($this, $last, $last[self::NAME_INDEX]);
        }
    }
    
    /**
     * Combine all rules processables from a resource and its parents
     * 
     * @param array $resource
     *   Resource to get all processables
     * @param string $processor
     *   Processor identifier
     * 
     * @return array|null
     *   All processables or null if no processable found for the given processor
     */
    private function combineProcessable(array $resource, string $processor): ?array
    {
        if(null === $resource[self::PARENT_INDEX]) {
            if(!isset($resource[self::PROCESS_INDEX][$processor]))
                return null;
            
            return $resource[self::PROCESS_INDEX][$processor];
        }
            
        $processables = $resource[self::PROCESS_INDEX][$processor] ?? null;
        
        $this->loopOnResource($resource[self::NAME_INDEX], function(array $parent, string $name) use (&$processables, $processor): void {
            if(isset($parent[self::PROCESS_INDEX][$processor])) {
                foreach ($parent[self::PROCESS_INDEX][$processor] as $toProcess) {
                    $processables[] = $toProcess;
                }
            }
        });
        
        return (null !== $processables) ? \array_unique($processables) : null;
    }
    
    /**
     * Change behaviour of the acl
     *
     * @param int $behaviour
     *   Behaviour to set
     *
     * @throws InvalidArgumentException
     *   When given behaviour is not a valid value
     */
    private function assignBehaviour(int $behaviour): void
    {
        if($behaviour !== self::WHITELIST && $behaviour !== self::BLACKLIST)
            throw new InvalidArgumentException("Acl behaviour MUST be one of the value determined into the acl");
            
        $this->behaviour = $behaviour;
    }
    
    /**
     * Validate a resource name
     * 
     * @param string $resource
     *   Resource name
     *   
     * @throws \TypeError
     *   When neither a string or an AclBindableInterface component
     * @throws InvalidArgumentException
     *   When does not matche the pattern
     * @throws ResourceNotFoundException
     *   When not registered into the acl
     */
    private function validateResourceName($resource): void
    {
        if(!isset($this->resourceChecked[$resource])) {
            if(!\is_string($resource) && !$resource instanceof AclBindableInterface)
                throw new \TypeError(\sprintf("Resource MUST be a string or an implementation of AclBindableInterface. '%s' given",
                    \is_object($resource) ? \get_class($resource) : \gettype($resource)));
            
            if(0 === \preg_match("#^[a-zA-Z0-9_]+$#", $resource))
                throw new InvalidArgumentException("Resource name '{$resource}' is invalid");
                    
            if(!isset($this->acl[$resource]))
                throw new ResourceNotFoundException("This resource '{$resource}' is not registered into the acl");
                        
            $this->resourceChecked[$resource] = true;
        }
    }
    
    /**
     * Provide an object wrapper to process a resource
     * 
     * @param int& $mask
     *   Default permission mask
     * @param bool& $locked
     *   Lock state
     * @param string $resource
     *   Resource name
     * @return object
     *   Acl processor wrapper
     */
    private function getProcessorAclWrapper(int& $mask, bool& $locked, string $resource)
    {
        return new class(
            $mask,
            $this,
            $this->acl[$resource][self::BEHAVIOUR_INDEX],
            function(string $permission) use ($resource): int {
                try {
                    return $this->getIndex($this->acl[$resource], $permission, self::PERMISSIONS_INDEX);
                } catch (PermissionNotFoundException $e) {
                    return $this->getIndex($this->acl[$resource], $permission, self::ENTRIES_INDEX);
                }
            },
            $locked)
        {
            
            /**
             * Default mask permission
             *
             * @var int
             */
            private $permission;
            
            /**
             * Reference to acl
             *
             * @var SimpleAcl
             */
            private $acl;
            
            /**
             * Reference to resource behaviour
             *
             * @var int
             */
            private $behaviour;
            
            /**
             * Reference to determine a permission or an entry
             *
             * @var \Closure
             */
            private $finder;
            
            /**
             * If permission has been locked
             *
             * @var bool
             */
            private $locked;
            
            /**
             * Initialize the wrapper
             *
             * @param int $permission
             *   Permission mask
             * @param SimpleAcl $acl
             *   Acl reference
             * @param int $behaviour
             *   Reference to resource behaviour
             * @param \Closure $finder
             *   Reference to determine a permission or an entry
             * @param bool $locked
             *   If permissions has been locked
             */
            public function __construct(int& $permission, SimpleAcl $acl, int $behaviour, \Closure $finder, bool& $locked)
            {
                $this->permission = &$permission;
                $this->acl = $acl;
                $this->behaviour = $behaviour;
                $this->finder = $finder;
                $this->locked = &$locked;
            }
            
            /**
             * Get behaviour of the current resource
             * 
             * @return int
             *   Resource behaviour
             */
            public function getBehaviour(): int
            {
                return $this->behaviour;
            }
            
            /**
             * Grant permission or an entry
             * 
             * @param string $permission
             *   Permission or entry name
             */
            public function grant(string $permission): void
            {
                try {
                    if(!$this->locked)
                        $this->permission |= $this->finder->call($this->acl, $permission);
                } catch (PermissionNotFoundException $e) {
                }
            }
            
            /**
             * Deny permission or an entry
             *
             * @param string $permission
             *   Permission or entry name
             */
            public function deny(string $permission): void
            {
                try {
                    if(!$this->locked)
                        $this->permission &= ~($this->finder->call($this->acl, $permission));
                } catch (PermissionNotFoundException $e) {
                }
            }
            
            /**
             * Lock the mask for the resource. 
             * Therefore it cannot be modified 
             */
            public function lock(): void
            {
                $this->locked = true;
            }
            
        };
    }

}
