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
use Ness\Component\Acl\Exception\EntryNotFoundException;
use Psr\SimpleCache\CacheInterface;
use Ness\Component\Acl\Normalizer\LockPatternNormalizerInterface;

/**
 * A simple acl for those who does not like my other implementation
 * All is fluent and in memory
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
final class SimpleAcl implements AclInterface
{
    
    /**
     * Reference to the last resource added
     * 
     * @var string|null
     */
    private $currentResource = null;
    
    /**
     * Reference for registering further entries into a processor
     * 
     * @var string|null
     */
    private $currentProcessor = null;
    
    /**
     * Multi mode
     * 
     * @var bool
     */
    private $multi = false;
    
    /**
     * Currently saved permissions
     * 
     * @var int[]
     */
    private $currentMulti;
    
    /**
     * Resources already validated
     * 
     * @var bool[]
     */
    private $resourceValidated;

    /**
     * Acl map
     * 
     * @var array[]
     */
    private $acl;
    
    /**
     * Acl behaviour
     * 
     * @var int
     */
    private $behaviour;
    
    /**
     * Lock pattern resource name normalizer
     * 
     * @var LockPatternNormalizerInterface
     */
    private $normalizer;
    
    /**
     * Acl processors
     * 
     * @var \Closure[]
     */
    private $processors = [];
    
    /**
     * Resource name index
     * 
     * @var string
     */
    private const NAME_INDEX = "name";
    
    /**
     * Resource permissions index
     *
     * @var string
     */
    private const PERMISSIONS_INDEX = "permissions";
    
    /**
     * Resource entries index
     *
     * @var string
     */
    private const ENTRIES_INDEX = "entries";
    
    /**
     * Entries linked to a processor
     * 
     * @var string
     */
    private const PROCESSED = "processed_entries";
    
    /**
     * Entries global
     * 
     * @var string
     */
    private const GLOBAL_ENTRIES = "global_entries";
    
    /**
     * Resource parent index
     *
     * @var string
     */
    private const PARENT_INDEX = "parent";
    
    /**
     * Resource behaviour index
     *
     * @var string
     */
    private const BEHAVIOUR_INDEX = "behaviour";
    
    /**
     * Max permissions allowed by resource
     * 
     * @var int
     */
    private const MAX = 31;
    
    /**
     * Blacklist acl
     * All permissions are granted by default
     * 
     * @var int
     */
    public const BLACKLIST = 0;
    
    /**
     * Whitelist acl
     * All permissions are denied by default
     *
     * @var int
     */
    public const WHITELIST = 1;
    
    /**
     * Attribute name
     * 
     * @var string
     */
    public const ACL_USER_ATTRIBUTE = "NESS_ACL_PERMISSIONS";
    
    /**
     * Acl cache key
     * 
     * @var string
     */
    public const CACHE_IDENTIFIER = "ness_builded_cached_acl";
    
    /**
     * Initialize acl
     * 
     * @param LockPatternNormalizerInterface $normalizer
     *   Lock pattern resource name normalizer
     * @param int $behaviour
     *   Acl behaviour. By default WHITELIST
     * 
     * @throws InvalidArgumentException
     *   When given behaviour is invalid
     */
    public function __construct(LockPatternNormalizerInterface $normalizer, int $behaviour = self::WHITELIST)
    {
        $this->normalizer = $normalizer;
        $this->assignBehaviour($behaviour);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\AclInterface::isAllowed()
     */
    public function isAllowed(UserInterface $user, $resource, string $permission, ?\Closure $update = null): bool
    {
        $bindable = null;
        $this->validateResource($resource, $bindable);
        
        if($this->multi)
            $required = $this->handleMulti($resource, $permission);
        else
            $required = $this->lookForPermission($resource, $permission);
        
        $attribute = null;
        $locked = false;
        $initialized = true;
        $mask = null;
        $this->initializeUser($user, $resource, $attribute, $initialized, $locked, $mask);
        
        if($locked || !$initialized && null === $update && null === $bindable)
            return (bool) ( ($mask & $required) === $required );

        if($initialized) {
            foreach ($this->processors as $identifier => $processor) {
                $processor->call($this->instantiateAclProcessorWrapper($mask, $locked, $resource, $identifier), $user);
                
                if($locked) {
                    unset($attribute[$resource[self::NAME_INDEX]]);
                    $attribute[$this->normalizer->apply($resource[self::NAME_INDEX])] = $mask;
                    $user->addAttribute(self::ACL_USER_ATTRIBUTE, $attribute);
                    
                    return (bool) ( ($mask & $required) === $required );
                }
            }
            
            $attribute[$resource[self::NAME_INDEX]] = $mask;
            $user->addAttribute(self::ACL_USER_ATTRIBUTE, $attribute);
        }
        
        if($update === null && null === $bindable)
            return (bool) ( ($mask & $required) === $required );
        
        $result = (bool) ( ($mask & $required) === $required );
        
        $update = (null !== $update) 
                        ? ((null !== $bindable) 
                            ? (null !== ($call = $update($user, $bindable)) ? $call : $bindable->updateAclPermission($user, $permission, $result)) 
                            : (null !== ($call = $update($user)) ? $call : $bindable->updateAclPermission($user, $permission, $result)))
                        : $bindable->updateAclPermission($user, $permission, $result);
            
        if(null === $update)
            return $result;
        
        return ($resource[self::BEHAVIOUR_INDEX] === self::WHITELIST) ? $update : !$update;
    }
    
    /**
     * Set the acl into multi mode.
     * Useful for optimizing the acl during multiple call to the same permission (in a loop for example)
     */
    public function multi(): void
    {
        $this->multi = true;
    }
    
    /**
     * Clear the last multi setup and set it to off
     */
    public function clearMulti(): void
    {
        $this->currentMulti = null;
        $this->multi = false;
    }
    
    /**
     * Cache a builded acl via a PSR-16 Cache implementation.
     * Processors are not cached
     * 
     * @param CacheInterface $cache
     *   PSR-16 Cache implementation
     * 
     * @return bool
     *   True if cached with success. False otherwise
     */
    public function cache(CacheInterface $cache): bool
    {
        return $cache->set(self::CACHE_IDENTIFIER, \json_encode([
            "acl"       =>  $this->acl,
            "behaviour" =>  $this->behaviour
        ]));
    }
    
    /**
     * Try to get a cached version of the acl from a PSR-16 Cache implementation
     * 
     * @param CacheInterface $cache
     *   PSR-16 Cache implementation
     * 
     * @return bool
     *   True if the acl has been initialized from a cached one. False otherwise
     */
    public function buildFromCache(CacheInterface $cache): bool
    {
        if(null !== $acl = $cache->get(self::CACHE_IDENTIFIER)) {
            $acl = \json_decode($acl, true);
            $this->behaviour = $acl["behaviour"];
            $this->acl = $acl["acl"];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Initialize the acl from a set of files or directories
     * Processor cannot be registered into this files
     * 
     * @param array $files
     *   Files or directories initializing the acl
     *   
     * @throws \LogicException 
     *   When a file is invalid
     * @throws \LogicException
     *   When an error happen into a file
     */
    public function buildFromFiles(array $files): void
    {
        $include = function(string $file): void {
            try {
                // make sure no processor are registered into included files
                $currentProcessorCount = \count($this->processors);
                $currentBehaviour = $this->behaviour;
                include $file;
                if($currentProcessorCount !== \count($this->processors))
                    throw new \LogicException("Cannot register acl processor into file");
                // clear cursor if not ended
                $this->endProcessor();
                $this->endResource();
                $this->changeBehaviour($currentBehaviour);
            } catch (\LogicException|EntryNotFoundException|ResourceNotFoundException $e) {
                throw new \LogicException("An error happen into this file '{$file}' during the initialization of the acl. See : {$e->getMessage()}");
            }
        };
        
        foreach ($files as $file) {
            if(!\file_exists($file))
                throw new \LogicException("This file '{$file}' is neither a valid file or directory");
            if(\is_dir($file)) {
                foreach (new \DirectoryIterator($file) as $file) {
                    if(!$file->isDot())
                        $include($file->getPathname());    
                }
            } else {
                $include($file);
            }
        }
    }
    
    /**
     * Register a processor to handle a user over the acl
     * 
     * @param string $identifier
     *   An unique identifier
     * @param \Closure $processor
     *   Action to call. Takes as parameter the user
     */
    public function registerProcessor(string $identifier, \Closure $processor): void
    {
        $this->processors[$identifier] = $processor;
    }
    
    /**
     * Change the behaviour of the acl.
     * All further registered resources will be assigned to this one
     * 
     * @param int $behaviour
     *   Acl behaviour
     * 
     * @throws InvalidArgumentException
     *   When given behaviour is invalid
     */
    public function changeBehaviour(int $behaviour): void
    {
        $this->assignBehaviour($behaviour);
    }

    /**
     * Add a resource into the acl
     * 
     * @param string $resource
     *   Resource name
     * @param string|null $parent
     *   Parent resource
     *   
     * @return self
     *   Fluent
     *   
     * @throws ResourceNotFoundException
     *   If parent resource is not registered
     * @throws \LogicException
     *   If resource is already registered
     * @throws \LogicException
     *   When a resource is already in registration
     */
    public function addResource(string $resource, ?string $parent = null): self
    {
        if(null !== $this->currentResource)
            throw new \LogicException("Cannot add a new resource now. End registration of '{$this->currentResource}' before new registration");
        
        if(isset($this->acl[$resource]))
            throw new \LogicException("This resource '{$resource}' is already registered into the acl");
        
        if(null !== $parent && !isset($this->acl[$parent]))
            throw new ResourceNotFoundException("This parent resource '{$parent}' is not registered into the acl and cannot be extended to '{$resource}'");
        
        $this->acl[$resource][self::NAME_INDEX] = $resource;
        $this->acl[$resource][self::PERMISSIONS_INDEX] = null;
        $this->acl[$resource][self::ENTRIES_INDEX] = null;
        $this->acl[$resource][self::ENTRIES_INDEX]["ROOT"] = (null !== $parent) ? $this->acl[$parent][self::ENTRIES_INDEX]["ROOT"] : 0;
        $this->acl[$resource][self::PARENT_INDEX] = $parent;
        $this->acl[$resource][self::BEHAVIOUR_INDEX] = (null !== $parent) ? $this->acl[$parent][self::BEHAVIOUR_INDEX] : $this->behaviour;
    
        $this->currentResource = $resource;
        
        return $this;
    }
    
    /**
     * Add a permission to the last added resource
     * 
     * @param string $permission
     *   Permission name
     *   
     * @return self
     *   Fluent
     * 
     * @throws \LogicException
     *   When the given permission has been already declared into the resource or a parent one
     * @throws \LogicException
     *   When max permissions count allowed is reached for this resource
     * @throws ResourceNotFoundException
     *   When given resource is not registered
     */
    public function addPermission(string $permission): self
    {
        $resource = null;
        $pointed = &$this->point($resource);

        $count = 0;
        $this->loopOnResource($pointed, function(array $current, string $name) use (&$count, $permission, $resource): bool {
            if(isset($current[self::PERMISSIONS_INDEX][$permission])) {
                if($name === $resource)
                    throw new \LogicException("This permission '{$permission}' is already registered into resource '{$resource}'");
                else 
                    throw new \LogicException("This permission '{$permission}' has been already declared into parent resource '{$name}' of resource '{$resource}' and cannot be redeclared");
            }

            if(null !== $current[self::PERMISSIONS_INDEX])
                $count += \count($current[self::PERMISSIONS_INDEX]);
            
            return true;
        });

        if($count >= self::MAX)
            throw new \LogicException("Cannot add more permission into resource '{$resource}'");
        
        $pointed[self::ENTRIES_INDEX]["ROOT"] |= $pointed[self::PERMISSIONS_INDEX][$permission] = ($count === 0) ? 1 : 1 << $count;
        
        return $this;
    }
    
    /**
     * Wrap all further registered entries into a processor
     * 
     * @param string $processor
     *   Processor identifier
     * 
     * @return self
     *   Fluent
     */
    public function wrapProcessor(string $processor): self
    {
        $this->currentProcessor = $processor;
        
        return $this;
    }
    
    /**
     * Add an entry into a resource.
     * If wrapProcessor has been called, this entry will be assigned to a processor
     * 
     * @param string $entry
     *   Entry name
     * @param array $permissions
     *   Permission accorded to this entry. Can be either a permission or a previously declared entry
     * @param string|null $resource
     *   Resource to attached to entry or null
     * 
     * @return self
     *   Fluent
     *   
     * @throws EntryNotFoundException
     *   If a permission is not a valid permission nor a valid entry
     */
    public function addEntry(string $entry, array $permissions, ?string $resource = null): self
    {        
        $pointed = &$this->point($resource);
        
        if($entry === "ROOT")
            throw new \LogicException("ROOT entry cannot be overriden into resource '{$resource}'");
        
        $value = 0;
        
        foreach ($permissions as $permission) {
            try {
                $value |= $this->lookForPermission($pointed, $permission); 
            } catch (PermissionNotFoundException $e) {
                $value |= $this->lookForEntry($pointed, $permission, $this->currentProcessor);
            }
        }
        
        if(null !== $this->currentProcessor)
            $pointed[self::ENTRIES_INDEX][self::PROCESSED][$this->currentProcessor][$entry] = $value;
        else
            $pointed[self::ENTRIES_INDEX][self::GLOBAL_ENTRIES][$entry] = $value;
        
        return $this;
    }
    
    /**
     * Finalize registration of the last wrap
     * 
     * @return self
     *   Fluent
     */
    public function endProcessor(): self
    {
        $this->currentProcessor = null;
        
        return $this;
    }
    
    /**
     * End registration of the last resource
     * 
     * @return self
     *   Fluent
     */
    public function endResource(): self
    {
        $this->currentResource = null;
        
        return $this;
    }
    
    /**
     * Validate a resource
     * 
     * @param string|AclBindableInterface& $resource
     *   Resource to validate
     * @param AclBindableInterface|null& $bindable
     *   Will be assigned if give resource is an AclBindableInterface instance
     * 
     * @throws \TypeError
     *   If given resource is neither a string or an AclBindableInterface component
     * @throws ResourceNotFoundException
     *   If given resource is not registered
     */
    private function validateResource(&$resource, ?AclBindableInterface& $bindable): void
    {
        if($resource instanceof AclBindableInterface) {
            $bindable = $resource;            
            $resource = $resource->getAclResourceName();
        }
        
        if(!isset($this->resourceValidated[$resource])) {
            if(!\is_string($resource) && !$resource instanceof AclBindableInterface)
                throw new \TypeError(\sprintf("Resource MUST be a string or an instance of AclBindableInterface. '%s' given",
                    (\is_object($resource) ? \get_class($resource) : \gettype($resource))));
                            
            if(!isset($this->acl[$resource]))
                throw new ResourceNotFoundException("This resource '{$resource}' is not registered into the acl");
            
            $this->resourceValidated[$resource] = true;
        }
        
        $resource = $this->acl[$resource];
    }
    
    /**
     * Store permission mask value fetched for multiple call
     * 
     * @param array $resource
     *   Resource to handle
     * @param string $permission
     *   Permission to store
     * 
     * @return int
     *   Permission found or stored one via multi
     */
    private function handleMulti(array $resource, string $permission): int
    {
        return $this->currentMulti[$resource[self::NAME_INDEX]][$permission] 
                    ?? $this->currentMulti[$resource[self::NAME_INDEX]][$permission] = $this->lookForPermission($resource, $permission);
    }
    
    /**
     * Instantiate a new acl processor wrapper
     * 
     * @param int& $mask
     *   Current mask permission
     * @param bool& $locked
     *   Current locked flag
     * @param array $resource
     *   Resource to bind
     * @param string $processor
     *   Processor identifier
     * 
     * @return AclProcessorWrapper
     *   Acl processor wrapper
     */
    private function instantiateAclProcessorWrapper(int&$mask, bool& $locked, array $resource, string $processor): AclProcessorWrapper
    {
        return new AclProcessorWrapper(
            $processor,
            $mask,
            $locked,
            $resource[self::BEHAVIOUR_INDEX],
            \Closure::bind(function(string $permission) use ($resource, $processor): int {
                try {
                    return $this->lookForPermission($resource, $permission);
                } catch (PermissionNotFoundException $e) {
                    return $this->lookForEntry($resource, $permission, $processor);
                }
            }, $this),
            \Closure::bind(function() use ($resource, $processor): array {
                return $this->getProcessables($resource, $processor);
            }, $this));
    }
    
    /**
     * Get a list of all entries linked to a processor
     * 
     * @param array $resource
     *   Resource to get all entries
     * @param string $processor
     *   Processor name
     * 
     * @return array
     *   Array of mask permission indexed by entry name
     */
    private function getProcessables(array $resource, string $processor): array
    {
        $processables = [];
        
        $this->loopOnResource($resource, function(array $current, string $name) use (&$processables, $processor): bool {
            if(isset($current[self::ENTRIES_INDEX][self::PROCESSED][$processor])) {
                foreach ($current[self::ENTRIES_INDEX][self::PROCESSED][$processor] as $entry => $permission) {
                    if(!isset($processables[$entry]))
                        $processables[$entry] = $permission;
                }
            }
            return true;
        });
        
        return $processables;
    }
    
    /**
     * Look for a permission into a resource and its parents
     * 
     * @param array $resource
     *   Resource to look
     * @param string $permission
     *   Permission name
     * 
     * @return int
     *   Permission value
     *   
     * @throws PermissionNotFoundException
     *   When given permission is not registered
     */
    private function lookForPermission(array $resource, string $permission): int
    {
        $found = null;
        $parents = null;
        $this->loopOnResource($resource, function(array $current, string $name) use ($resource, $permission, &$found): bool {
            if(isset($current[self::PERMISSIONS_INDEX][$permission])) {
                $found = $current[self::PERMISSIONS_INDEX][$permission];
                
                return false;
            }
            
            return true;
        }, $parents);
        
        if(null === $found) {
            if(isset($parents))
                throw new PermissionNotFoundException(\sprintf("This permission '%s' is not registered into resource '%s' nor into one of its parents '%s'",
                    $permission,
                    $resource[self::NAME_INDEX],
                    \implode(", ", $parents)));
                
            throw new PermissionNotFoundException("This permission '{$permission}' is not registered into resource '{$resource[self::NAME_INDEX]}'");          
        }
        
        return $found;
    }
    
    /**
     * Look for an entry into a resource and its parents
     * 
     * @param string $resource
     *   Resource to look
     * @param string $entry
     *   Entry to get
     * @param string|null $processor
     *   Processor name or null to search into global
     * 
     * @return int
     *   Entry value
     *   
     * @throws EntryNotFoundException
     *   When no entry corresponds the given one
     */
    private function lookForEntry(array $resource, string $entry, ?string $processor): int
    {
        if("ROOT" === $entry)
            return $resource[self::ENTRIES_INDEX]["ROOT"];
        
        $found = null;
        $parents = null;
        
        $lookUpon = function(?string $processor) use ($resource, $entry, &$found): \Closure {
            return function(array $current, string $name) use ($resource, $entry, $processor, &$found): bool {                
                $toLook = (null !== $processor) 
                            ? $current[self::ENTRIES_INDEX][self::PROCESSED][$processor] ?? null
                            : $current[self::ENTRIES_INDEX][self::GLOBAL_ENTRIES] ?? null;
                if(isset($toLook[$entry])) {
                    $found = $toLook[$entry];
    
                    return false;
                }
                
                return true;
            };
        };
        
        // look in priority into resource and parents for an entry corresponding the processor
        $this->loopOnResource($resource, $lookUpon($processor), $parents);
        
        // fallback to global if not found into processor
        if($processor !== null && null === $found)
            $this->loopOnResource($resource, $lookUpon(null), $parents);
        
        if(null === $found) {
            if(isset($parents)) {
                $exception = new EntryNotFoundException($entry, \sprintf("This entry '%s' is not registered into resource '%s' nor into one of its parents '%s'",
                    $entry,
                    $resource[self::NAME_INDEX],
                    \implode(", ", \array_unique($parents))));
            } else {
                $exception = new EntryNotFoundException($entry, "This entry '{$entry}' is not registered into resource '{$resource[self::NAME_INDEX]}'");                
            }

            throw $exception;
        }
        
        return $found;
    }
    
    /**
     * Loop on resource and all its parents if setted
     * 
     * @param array $resource
     *   Start resource
     * @param \Closure $action
     *   Action to perform on each resource. Takes as first parameter the array representation of the current resource as a second its name
     *   MUST return true to continue the action or false to stop the loop
     * @param array|null& $parents
     *   Register all parents visited
     * @param bool $reverse
     *   Which order the action must be executed. By default from children to all parents 
     */
    private function loopOnResource(array $resource, \Closure $action, ?array& $parents = null, bool $reverse = true): void
    {
        while (true) {
            if(!$reverse) {
                $toLoop[] = $resource[self::NAME_INDEX];
                if(null === $resource[self::PARENT_INDEX]) {
                    \krsort($toLoop);
                    foreach ($toLoop as $resource) {
                        $resource = $this->acl[$resource];
                        if(!$action->call($this, $resource, $resource[self::NAME_INDEX])) {
                            unset($parents);
                            return;
                        }
                    }                    
                    return;
                }
            } else {
                if(!$action->call($this, $resource, $resource[self::NAME_INDEX])) {
                    unset($parents);
                    return;                
                }
                if(null === $resource[self::PARENT_INDEX])                        
                    return;
            }
            
            $resource = $this->acl[$resource[self::PARENT_INDEX]];
            $parents[] = $resource[self::NAME_INDEX];
        }
    }
    
    /**
     * Point to a specific resource for modifications
     * 
     * @param string& $resource
     *   Resource to modify
     * 
     * @return &array
     *   Pointed resource
     *   
     * @throws \LogicException
     *   When no resource are pointable
     * @throws ResourceNotFoundException
     *   When pointed resource is not registered
     */
    private function &point(?string& $resource): array
    {
        $resource = $resource ?? $this->currentResource;
        
        if(null === $resource)
            throw new \LogicException("No resource has been defined");
        
        if(!isset($this->acl[$resource]))
            throw new ResourceNotFoundException("This resource '{$resource}' is not registered into the acl");
        
        return $this->acl[$resource];
    }
    
    /**
     * Assign a new behaviour
     * 
     * @param int $behaviour
     *   Acl behaviour
     * 
     * @throws InvalidArgumentException
     *   When given behaviour is not a valid one
     */
    private function assignBehaviour(int $behaviour): void
    {
        if($behaviour !== self::WHITELIST && $behaviour !== self::BLACKLIST)
            throw new InvalidArgumentException("Behaviour is invalid. Use SimpleAcl::WHITELIST or SimpleAcl::BLACKLIST const");
        
        $this->behaviour = $behaviour;
    }
    
    /**
     * Initialize user permissions over a resource
     * 
     * @param UserInterface $user
     *   User to initialize
     * @param array $resource
     *   Resource to initialized
     * @param array& $attribute
     *   Initializes with value of the user's attribute
     * @param bool& $initialized
     *   Setted to true if the resource has been initialized into user attributes
     * @param bool& $locked
     *   Setted to true if the resource is locked
     * @param int|null& $mask
     *   Setted from the attribute or from resource initialization 
     */
    private function initializeUser(
        UserInterface $user, 
        array $resource, 
        ?array& $attribute, 
        bool& $initialized, 
        bool& $locked, 
        ?int& $mask): void
    {
        if(null === $attribute = $user->getAttribute(self::ACL_USER_ATTRIBUTE)) {  
            $user->addAttribute(self::ACL_USER_ATTRIBUTE, []);
            $attribute = [];
        }
        $normalized = $this->normalizer->apply($resource[self::NAME_INDEX]);
        if( ($locked = isset($attribute[$normalized])) || isset($attribute[$resource[self::NAME_INDEX]])) {
            $mask = $attribute[$normalized] ?? $attribute[$resource[self::NAME_INDEX]];
            $initialized = false;
            
            return;
        }
        
        $initialized = true;
        $mask = $attribute[$resource[self::NAME_INDEX]] = ($resource[self::BEHAVIOUR_INDEX] === self::WHITELIST) ? 0 : $resource[self::ENTRIES_INDEX]["ROOT"];
    }
    
}

/**
 * Used by processors to interact with the user
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
final class AclProcessorWrapper
{
    
    /**
     * Processor identifier
     * 
     * @var string
     */
    private $identifier;
    
    /**
     * Current mask permission
     * 
     * @var int
     */
    private $mask;
    
    /**
     * Lock state
     * 
     * @var bool
     */
    private $locked;
    
    /**
     * Resource behaviour
     * 
     * @var int
     */
    private $behaviour;
    
    /**
     * Used to find permission or entry
     * 
     * @var \Closure
     */
    private $finder;
    
    /**
     * Used to get all entries from the resource affiliated to this processor
     *
     * @var \Closure
     */
    private $entriesCombiner;
    
    /**
     * Strict mode
     * 
     * @var bool
     */
    private $strict = false;
    
    /**
     * Initialize wrapper
     * 
     * @param string $identifier
     *   Processor identifier
     * @param int& $mask
     *   Default permission mask
     * @param bool& $locked
     *   Lock state
     * @param int $behaviour
     *   Resource behaviour
     * @param \Closure $finder
     *   Permission/Entry finder
     * @param \Closure $entriesCombiner
     *   Entries processor combiner
     */
    public function __construct(string $identifier, int& $mask, bool& $locked, int $behaviour, \Closure $finder, \Closure $entriesCombiner)
    {
        $this->identifier = $identifier;
        $this->mask = &$mask;
        $this->locked = &$locked;
        $this->behaviour = $behaviour;
        $this->finder = $finder;
        $this->entriesCombiner = $entriesCombiner;
    }
    
    /**
     * Get behaviour of the resource
     * 
     * @return int
     *   Resource behaviour
     */
    public function getBehaviour(): int
    {
        return $this->behaviour;
    }
    
    /**
     * Grant a permission or an entry
     *
     * @param string $permission
     *   Permission or entry to grant
     */
    public function grant(string $permission): void
    {
        if(!$this->locked) {
            try {
                $this->mask |= \call_user_func($this->finder, $permission);
            } catch (EntryNotFoundException $e) {
                if($this->strict)
                    throw new EntryNotFoundException($permission, "This entry/permission '{$e->getEntry()}' cannot be processed into processor '{$this->identifier}'. See message : {$e->getMessage()}");
            }   
        }
    }
    
    /**
     * Deny a permission or an entry
     * 
     * @param string $permission
     *   Permission or entry to deny
     */
    public function deny(string $permission): void
    {
        if(!$this->locked) {
            try {
                $this->mask &= ~(\call_user_func($this->finder, $permission));
            } catch (EntryNotFoundException $e) {
                if($this->strict)
                    throw new EntryNotFoundException($permission, "This entry/permission '{$e->getEntry()}' cannot be processed into processor '{$this->identifier}'. See message : {$e->getMessage()}");
            }
        }
    }
    
    /**
     * Lock the permission
     */
    public function lock(): void
    {
        $this->locked = true;
    }
    
    /**
     * Get a list of all entries affiliated to this processor.
     * 
     * @return array|null
     *   Array indexed by entry name and valued by the permission accorded to this entry or null if no entry found
     */
    public function getEntries(): ?array
    {
        return (empty($entries = \call_user_func($this->entriesCombiner))) ? null : $entries;
    }
    
    /**
     * Set the wrapper in strict mode.
     * In this mode, if a permission or an entry has been not found, an exception will be thrown
     */
    public function setToStrict(): void
    {
        $this->strict = true;
    }
    
}
