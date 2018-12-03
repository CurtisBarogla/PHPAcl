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
use Ness\Component\Acl\Normalizer\LockPatternNormalizerInterface;
use Ness\Component\Acl\Signal\ResetSignalHandlerInterface;

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
     * Lock pattern resource name normalizer
     * 
     * @var LockPatternNormalizerInterface
     */
    private $normalizer;
    
    /**
     * Reset signal handler
     * 
     * @var ResetSignalHandlerInterface
     */
    private $handler;
    
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
     * @var AclUser[]
     */
    private $loaded;
    
    /**
     * Initialize acl
     * 
     * @param ResourceLoaderInterface $resourceLoader
     *   Resource loader
     * @param EntryLoaderInterface $entryLoader
     *   Entry loader
     * @param LockPatternNormalizerInterface
     *   Lock pattern resource name normalizer
     * @param ResetSignalHandlerInterface $handler
     *   Reset signal handler
     */
    public function __construct(
        ResourceLoaderInterface $resourceLoader, 
        EntryLoaderInterface $entryLoader, 
        LockPatternNormalizerInterface $normalizer,
        ResetSignalHandlerInterface $handler)
    {
        $this->resourceLoader = $resourceLoader;
        $this->entryLoader = $entryLoader;
        $this->normalizer = $normalizer;
        $this->handler = $handler;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\AclInterface::isAllowed()
     */
    public function isAllowed(UserInterface $user, $resource, string $permission, ?\Closure $update = null): bool
    {
        $bindable = null;
        $resourceInstance = $this->validateAndLoadResource($resource, $bindable);
        $user = $this->initializeAclUser($user);
        $mask = $user->getPermission($resourceInstance);
        $required = $this->getPermission($resourceInstance, $resource, $permission);
        
        if($user->isLocked($resourceInstance) || (null !== $mask && !isset($bindable) && null === $update) ) 
            return (bool) ( ($mask & $required) === $required );

        if(null === $mask) {
            $this->initializePermissions($resourceInstance, $user);
            if(null !== $lockResult = $this->executeProcessors($resourceInstance, $user, $required)) {
                unset($this->loaded[$user->getName()]);
                return $lockResult;
            }
            $mask = $user->getPermission($resourceInstance);
            unset($this->loaded[$user->getName()]);
        }
        
        if(null === $bindable && null === $update)
            return (bool) ( ($mask & $required) === $required );
        
        $result = (bool) ( ($mask & $required) === $required );

        if(null === $update = $this->defineUpdate($update, $bindable, $user, $permission, $result))
            return $result;
                     
        return ($resourceInstance->getBehaviour() === ResourceInterface::WHITELIST) ? $update : !$update;
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
     * Initialize an acl user from the given user, call the handler and memoize it for the acl lifetime
     * 
     * @param UserInterface $user
     *   Base user which the acl one must be initialized
     *   
     * @return AclUser
     *   Initialized acl user
     */
    private function initializeAclUser(UserInterface $user): AclUser
    {
        if(isset($this->loaded[$user->getName()]))
            $user = $this->loaded[$user->getName()];
        else {
            $this->handler->handle($user, AclUser::ACL_ATTRIBUTE_IDENTIFIER);
            $user = $this->loaded[$user->getName()] = new AclUser($user, $this->normalizer);
        }
        
        return $user;
    }
    
    /**
     * Initialize permissions of a user over a Resource depending of its behaviour
     * 
     * @param ResourceInterface $resource
     *   Resource which the permission is initialized
     * @param AclUser $user
     *   User to initialized
     */
    private function initializePermissions(ResourceInterface $resource, AclUser $user): void
    {
        if($resource->getBehaviour() === ResourceInterface::WHITELIST) {
            $user->setPermission($resource, 0);
            return;
        }
        
        $resource->grantRoot()->to($user);
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
     * @param AclUser $user
     *   Acl user
     * @param int $required
     *   Current required permission mask
     * 
     * @return bool|null
     *   Return a boolean if the user has been locked during the execution of a processor representing the user right to perform the action or null
     */
    private function executeProcessors(ResourceInterface $resource, AclUser $user, int $required): ?bool
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
