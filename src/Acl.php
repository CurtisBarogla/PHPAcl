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
     * @var ResourceProcessorInterface
     */
    private $processors;
    
    /**
     * Resources already loaded
     * 
     * @var ResourceInterface
     */
    private $resources;
    
    /**
     * Already fetched permissions
     * 
     * @var int[]
     */
    private $fetched;
    
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
        $required = $this->getPermission($instance, $resource, $permission);
        $locked = false;
        $masks = null;
        $mask = $this->initializeMaskUser($user, $resource, $locked, $masks);
        
        if($locked || (null !== $mask && !isset($bindable) && null === $update) )    
            return (bool) ( ($mask & $required) === $required );
        
        if(null === $mask) {
            $aclUser = new AclUser($user);
            if($instance->getBehaviour() === ResourceInterface::BLACKLIST)
                $instance->grantRoot()->to($aclUser);
            if(null !== $result = $this->executeProcessors($instance, $aclUser, $required))
                return $result;
            
            $masks[$resource] = $mask = $aclUser->getPermission();
            $user->addAttribute(AclUser::ACL_ATTRIBUTE_IDENTIFIER, $masks);
        }
        
        if(null === $update && null === $bindable)
            return (bool) ( ($mask & $required) === $required );

        $result = (bool) ( ($mask & $required) === $required );
            
        $update = (null !== $update)
            ? ((null !== $bindable)
                ? $update($user, $bindable)
                : $update($user))
            : $bindable->updateAclPermission($user, $permission, $result);
            
        if(null === $update)
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
     * Execute all registered acl processor
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
                return (bool) ( ($user->getPermission() & $required) === $required );
        }
        
        return null;
    }
    
    /**
     * Initializer user mask permission from its attributes
     * 
     * @param UserInterface $user
     *   User
     * @param string $resourceName
     *   Resource name
     * @param bool& $locked
     *   Setted to true if the user has a locked resource into its attributes
     * @param array|null& $masks
     *   Reference to all registered masked into user attribute
     * 
     * @return int|null
     *   The permission mask or null if the user has no attribute for the given resource
     */
    private function initializeMaskUser(UserInterface $user, string $resourceName, bool& $locked, ?array& $masks): ?int
    {
        if(null === $masks = $user->getAttribute(AclUser::ACL_ATTRIBUTE_IDENTIFIER)) {
            $user->addAttribute(AclUser::ACL_ATTRIBUTE_IDENTIFIER, [$resourceName => 0]);
            
            return null;
        }
        
        if(isset($masks["<{$resourceName}>"])) {
            $locked = true;
            
            return $masks["<{$resourceName}>"];
        }
        
        return $masks[$resourceName] ?? null;
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
