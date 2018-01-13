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

namespace Zoe\Component\Acl;

use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\User\UserInterface;
use Zoe\Component\Acl\Loader\ResourceLoaderInterface;
use Zoe\Component\Acl\Processor\AclProcessorInterface;
use Zoe\Component\Acl\Mask\Mask;
use Zoe\Component\Acl\User\AclUser;
use Zoe\Component\User\Exception\InvalidUserAttributeException;
use Zoe\Component\Acl\User\AclUserInterface;

/**
 * Native Acl implementation
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
    private $loader;
    
    /**
     * Resource processors
     * 
     * @var AclProcessorInterface[]
     */
    private $processors = [];
    
    /**
     * Components binded
     * 
     * @var AclBindableInterface[]
     */
    private $binded;
    
    /**
     * Already loaded resource
     * 
     * @var ResourceInterface[]
     */
    private static $loaded = [];
    
    /**
     * Already determined permission masks
     * 
     * @var Mask[]
     */
    private static $determined = [];
    
    /**
     * Initialize acl
     * 
     * @param ResourceLoaderInterface $loader
     *   Resource loader
     */
    public function __construct(ResourceLoaderInterface $loader)
    {
        $this->loader = $loader;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\AclInterface::getResource()
     */
    public function getResource(string $resource): ResourceInterface
    {
        return $this->memoize(self::$loaded, $resource, [$this->loader, "load"], [$resource]);
    }
    
    /**
     * Register a processor
     * 
     * @param AclProcessorInterface $processor
     *   Process implementation
     */
    public function registerProcessor(AclProcessorInterface $processor): void
    {
        $this->processors[$processor->getIdentifier()] = $processor;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\AclInterface::isAllowed()
     */
    public function isAllowed(UserInterface $user, string $resource, array $permissions = [], ?callable $bind = null): bool
    {
        // restrict acl user
        if($user instanceof AclUserInterface)
            throw new \InvalidArgumentException("User given cannot be an instance of AclUserInterface as the acl handle its creation");
        
        if(empty($permissions))
            return true;
            
        $fromUser = false;
        $loaded = $this->getResource($resource);
        $identifier = "_PERMISSION_{$resource}";
        $required = $this->initResourceMaskPermission($loaded, $permissions)->getValue();
        $permissionMask = $this->initUserMaskPermission($user, $loaded, $identifier, $fromUser);
        
        if(!$fromUser) {
            $user = new AclUser($permissionMask, $user);
            if(!$loaded->isProcessed())
                $loaded->process($this->processors, $user);
            
            $user->addAttribute($identifier, $permissionMask);
        }
        
        $granted = (bool) ( ($permissionMask->getValue() & $required) === $required );
        
        if(null === $bind && !isset($this->binded[$resource]))
            return $granted;
            
        if(!$user instanceof AclUserInterface)
            $user = new AclUser($permissionMask, $user);

        $this->executeWorkflow(isset($this->binded[$resource]), $loaded, $user, [$this->binded[$resource], "_onBind"], $required, $granted); 
        $this->executeWorkflow(null !== $bind, $loaded, $user, $bind, $required, $granted);
        
        return $granted;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\AclInterface::bind()
     */
    public function bind(AclBindableInterface $bindable): void
    {
        $this->binded[$bindable->_getResourceName()] = $bindable; 
    }
    
    /**
     * Execute an acl flow
     * 
     * @param bool $isExecutable
     *   If the callable must be processed
     * @param ResourceInterface $resource
     *   Resource to link
     * @param AclUserInterface $user
     *   User to link
     * @param array[callable|null]|callable|null $executable
     *   A callable
     * @param int $required
     *   Required permissions to perform actions
     * @param bool $granted
     *   Setted to true if granted
     */
    private function executeWorkflow(
        bool $isExecutable,
        ResourceInterface $resource,
        AclUserInterface $user,
        $executable,
        int $required,
        bool& $granted): void
    {
        if(!$isExecutable)
            return;
        
        $executables = \call_user_func($executable, $user, $granted);
        if(null === $executables || empty($executables))
            return;
                    
        $user = clone $user;
        
        if(\is_array($executables)) {
            foreach ($executables as $executable) {
                if(!\is_callable($executable))
                    continue;
                \call_user_func($executable, $user, $resource);
            }
        } else {
            \call_user_func($executables, $user, $resource);
        }
                    
        $granted = ( ($user->getPermission()->getValue() & $required) === $required );
    }
    
    /**
     * Initialize a mask permission from a user attribute or from a resource if not defined
     * 
     * @param UserInterface $user
     *   User which the mask must be initialized
     * @param ResourceInterface $resource
     *   Resource which the mask must be initialized
     * @param string $identifier
     *   Permission mask identifier
     * @param bool $fromUser
     *   Setted to true if the mask comes from the user attribute
     * 
     * @return Mask
     *   Mask permission
     */
    private function initUserMaskPermission(UserInterface $user, ResourceInterface $resource, string $identifier, bool& $fromUser): Mask
    {
        try {
            $fromUser = true;     

            return $user->getAttribute($identifier);
        } catch (InvalidUserAttributeException $e) {
            $fromUser = false;
            $base = ($resource->getBehaviour() === ResourceInterface::WHITELIST) ? 0 : $resource->getPermissions()->total()->getValue();
            
            return new Mask($identifier, $base);
        }        
    }
    
    /**
     * Initialize a mask over a resource.
     * 
     * @param ResourceInterface $resource
     *   Resource
     * @param array $permissions
     *   Permissions to set into the mask
     * 
     * @return Mask
     *   Permissions mask
     */
    private function initResourceMaskPermission(ResourceInterface $resource, array $permissions): Mask
    {
        return (\count($permissions) > 1) ? 
            $this->memoize(self::$determined, $resource->getName()."_".\implode(",", $permissions), function() use ($resource, $permissions): Mask {
                return $resource->getPermissions($permissions)->total();
            }) : 
            $resource->getPermission($permissions[0]);
    }
    
    /**
     * Kinda lazy load expensive action
     * 
     * @param array $container
     *   Values container
     * @param string $key
     *   Value identifier
     * @param callable $value
     *   Value to store
     * @param array $args
     *   Args passed to the callable
     * 
     * @return mixed
     *   Hit from the container or call to callable
     */
    private function memoize(array& $container, string $key, callable $value, array $args = [])
    {
        if(isset($container[$key]))
            return $container[$key];
        
        $value = \call_user_func($value, ...$args);
        $container[$key] = $value;
        
        return $value;
    }
    
}
