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
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface;
use Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface;
use Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\User\AclUser;
use Ness\Component\Acl\User\AclUserInterface;

/**
 * Native implementation using acl components
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class Acl implements AclInterface
{
    
    /**
     * Resource loader
     * 
     * @var ResourceLoaderInterface
     */
    private $resourceLoader;
    
    /**
     * Entry loader
     * 
     * @var EntryLoaderInterface
     */
    private $entryLoader;
    
    /**
     * Processor executes when acl mades it decision
     * 
     * @var ResourceProcessorInterface[]
     */
    private $processors;
    
    /**
     * Resources already loaded
     * 
     * @var ResourceInterface[]
     */
    private $resources;
    
    /**
     * Already fetched permissions
     * 
     * @var int[]
     */
    private $fetched;
    
    /**
     * Acl users loaded
     * 
     * @var AclUserInterface[]
     */
    private $loaded;
    
    /**
     * Initialize acl
     * 
     * @param ResourceLoaderInterface $resourceLoader
     *   Resource loader
     * @param EntryLoaderInterface $entryLoader
     *   Entry loader
     */
    public function __construct(ResourceLoaderInterface $resourceLoader, EntryLoaderInterface $entryLoader)
    {
        $this->resourceLoader = $resourceLoader;
        $this->entryLoader = $entryLoader;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\AclInterface::isAllowed()
     */
    public function isAllowed(UserInterface $user, $resource, string $permission, ?\Closure $update = null): bool
    {
        $bindable = null;
        $instance = $this->validateAndLoadResource($resource, $bindable);
        $username = $user->getName();
        $user = $this->loaded[$username] ?? ( $this->loaded[$username] = new AclUser($user) );
        $mask = $user->getPermission($instance);
        $required = $this->getPermission($instance, $resource, $permission);
        
        if($user->isLocked($instance) || (null !== $mask && !isset($bindable) && null === $update) ) 
            return (bool) ( ($mask & $required) === $required );

        if(null === $mask) {
            ($instance->getBehaviour() === ResourceInterface::BLACKLIST) ? $instance->grantRoot()->to($user) : $user->setPermission($instance, 0);
            if(null !== $lockResult = $this->executeProcessors($instance, $user, $required)) {
                unset($this->loaded[$username]);
                return $lockResult;
            }
            $mask = $user->getPermission($instance);
            unset($this->loaded[$username]);
        }
        
        if(null === $bindable && null === $update)
            return (bool) ( ($mask & $required) === $required );
        
        $result = (bool) ( ($mask & $required) === $required );

        if(null === $update = $this->defineUpdate($update, $bindable, $user, $permission, $result))
            return $result;
                     
        return ($instance->getBehaviour() === ResourceInterface::WHITELIST) ? $update : !$update;
    }
    
    /**
     * Register a processor
     * Processors are executed each time the acl mades its decision.
     * Processors affects the permissions accorderd to a user
     * 
     * @param ResourceProcessorInterface $processor
     *   Resource processor
     */
    public function registerProcessor(ResourceProcessorInterface $processor): void
    {
        $this->processors[$processor->getIdentifier()] = $processor;
    }
    
    /**
     * Get all identifiers of all registered acl processors
     * 
     * @return string[]
     *   All identifiers
     */
    public function getProcessors(): array
    {
        return \array_keys($this->processors ?? []);
    }
    
    /**
     * Determine which update must be applied
     * 
     * @param \Closure $update
     *   Update closure
     * @param AclBindableInterface $bindable
     *   Update through bindable
     * @param UserInterface
     *   User linked to the update process
     * @param string $permission
     *   Permission currently handled
     * @param bool $result
     *   Current acl decision
     * 
     * @return bool|null
     *   An update result
     */
    private function defineUpdate(
        ?\Closure $update, 
        ?AclBindableInterface $bindable, 
        UserInterface $user, 
        string $permission, 
        bool $result): ?bool
    {
        if(null !== $update) {
            $update = (null !== $bindable) ? $update($user, $bindable) : $update($user);    
            return (null !== $update) ? $update : ( (null !== $bindable) ? $bindable->updateAclPermission($user, $permission, $result) : null);
        }
        
        return (null !== $bindable) ? $bindable->updateAclPermission($user, $permission, $result) : null;
    }
    
    /**
     * Execute all registered acl processors
     * 
     * @param ResourceInterface $resource
     *   Resource which the processor is executed
     * @param AclUserInterface $user
     *   Acl user
     * @param int $required
     *   Current required permission mask
     * 
     * @return bool|null
     *   Return a boolean if the user has been locked during the execution of a processor representing the user right to perform the action or null
     */
    private function executeProcessors(ResourceInterface $resource, AclUserInterface $user, int $required): ?bool
    {
        if(null === $this->processors)
            return null;

        foreach ($this->processors as $processor) {
            $processor->setUser($user);
            $processor->process($resource, $this->entryLoader);
            
            if($user->isLocked($resource))
                return (bool) ( ($user->getPermission($resource) & $required) === $required );
        }
        
        return null;
    }
    
    /**
     * Fetch a required mask permission for a resource
     * 
     * @param ResourceInterface $resource
     *   Resource
     * @param string $resourceName
     *   Resource name
     * @param string $permission
     *   Permission to get the mask
     * 
     * @return int
     *   Permission mask
     */
    private function getPermission(ResourceInterface $resource, string $resourceName, string $permission): int
    {
        if(isset($this->fetched[$resourceName][$permission]))
            return $this->fetched[$resourceName][$permission];
        
        $mask = $resource->getPermission($permission);
        
        $this->fetched[$resourceName][$permission] = $mask;
        
        return $mask;
    }
    
    /**
     * Validate a resource, convert if the given resource is an AclBindableInterface, and load it
     * 
     * @param string|AclBindableInterface $resource
     *   Resource to validate
     * @param AclBindableInterface|null& $bindable
     *   Setted if the given resource if an AclBindable component
     *   
     * @return ResourceInterface
     *   Loaded resource
     * 
     * @throws \TypeError
     *   If the given resource is not a string not an AclBindableInterface component
     */
    private function validateAndLoadResource(&$resource, ?AclBindableInterface& $bindable): ResourceInterface
    {
        if($resource instanceof AclBindableInterface) {
            $bindable = $resource;
            $resource = $resource->getAclResourceName();
        }
        
        if(!\is_string($resource)) {
            throw new \TypeError(\sprintf("Resource MUST be an instance of AclBindableInterface or a string. '%s' given",
                (\is_object($resource) ? \get_class($resource) : \gettype($resource))));
        }
        
        if(isset($this->resources[$resource]))
            return $this->resources[$resource];
        
        $instance = $this->resourceLoader->load($resource);
        
        $this->resources[$resource] = $instance;
        
        return $instance;
    }

}