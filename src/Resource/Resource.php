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

namespace Zoe\Component\Acl\Resource;

use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Exception\PermissionNotFoundException;
use Zoe\Component\User\Exception\InvalidUserAttributeException;
use Zoe\Component\Acl\Exception\InvalidPermissionException;
use Zoe\Component\Acl\Exception\EntryValueNotFoundException;

/**
 * Native implementation of ResourceInterface
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class Resource implements ResourceInterface, ProcessableResourceInterface
{
    
    /**
     * User currently managed by the resource
     * 
     * @var AclUserInterface
     */
    private $user;
    
    /**
     * Current permission value defined into the user
     * 
     * @var int
     */
    private $current = 0;
    
    /**
     * Resource name
     * 
     * @var string
     */
    protected $name;
    
    /**
     * Resource behaviour
     * 
     * @var int
     */
    protected $behaviour;
    
    /**
     * Permissions setted
     * 
     * @var int[]
     */
    protected $permissions = [];
    
    /**
     * Resource entries
     * 
     * @var array[]
     */
    protected $entries = [];

    /**
     * Attribute name used for storing an already processed user over the resource
     *
     * @var string
     */
    public const USER_ATTRIBUTE_RESOURCE_IDENTIFIER = "ACL_RESOURCE_PERMISSIONS";
    
    /**
     * Initialize a resource
     * 
     * @param string $name
     *   Resource name
     * @param int $behaviour
     *   Resource behaviour
     */
    public function __construct(string $name, int $behaviour)
    {
        $this->name = $name;
        $this->behaviour = $behaviour;
        $this->permissions[self::ALL_PERMISSIONS] = 0;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getName()
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getBehaviour()
     */
    public function getBehaviour(): int
    {
        return $this->behaviour;
    }
    
    /**
     * This implementation will not check validity pattern of given permission as permission is checked when added.
     * Therefore, a PermissionNotFoundException is raised if invalid permission is given.
     * This implementation allows use of an entry value.
     * 
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::grant()
     */
    public function grant($permission): ResourceInterface
    {
        $this->set($permission, "grant");
        
        return $this;
    }
    
    /**
     * This implementation will not check validity pattern of given permission as permission is checked when added.
     * Therefore, a PermissionNotFoundException is raised if invalid permission is given.
     * This implementation allows use of an entry value.
     * 
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::deny()
     */
    public function deny($permission): ResourceInterface
    {
        $this->set($permission, "deny");
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::to()
     */
    public function to(AclUserInterface $user): ResourceInterface
    {
        if(null === $this->user) {
            $this->user = $user;
            $this->current = $this->user->getPermission();
        }
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::finalize()
     */
    public function finalize(): void
    {
        if(null === $this->user)
            return;
        
        $this->user->setPermission($this->current);
        $this->current = 0;
        $this->user = null;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getPermission()
     */
    public function getPermission(string $permission): int
    {
        if(!isset($this->permissions[$permission])) {
            foreach ($this->entries as $entry) {
                foreach ($entry as $values)
                    if(isset($values[$permission]))
                        return $values[$permission];
            }
            
            throw new PermissionNotFoundException("This permission '{$permission}' is neither registered as an entity value or a permission into resource '{$this->name}'");
        }
        
        return $this->permissions[$permission];
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ResourceInterface::getPermissions()
     */
    public function getPermissions(array $permissions): int
    {
        if(!isset($permissions[1]))
            return $this->getPermission($permissions[0]);
            
        $total = 0;
        foreach ($permissions as $permission) {
            $total |= $this->getPermission($permission);
        }
        
        return $total;
    }

    /**
     * This implementation will simply look if the attribute (USER_ATTRIBUTE_RESOURCE_IDENTIFIER) 
     * exists and if this resource name containing processed resource permissions over all processors is present
     * 
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ProcessableResourceInterface::shouldBeProcessed()
     */
    public function shouldBeProcessed(AclUserInterface $user): bool
    {
        try {
            return !isset($user->getAttribute(self::USER_ATTRIBUTE_RESOURCE_IDENTIFIER)[$this->name]);
        } catch (InvalidUserAttributeException $e) {
            return true;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\ProcessableResourceInterface::process()
     */
    public function process(array $processors, AclUserInterface $user): void
    {
        foreach ($this->entries as $processor => $entry) {
            if(empty($processor))
                continue;
            if(!isset($processors[$processor]))
                throw new \LogicException(\sprintf("This processor '%s' is not registered into given processors '%s'",
                    $processor,
                    \implode(", ", \array_keys($processors))));
            
            $processors[$processor]->process($this, $user);
        }
        
        try {
            $attribute = $user->getAttribute(self::USER_ATTRIBUTE_RESOURCE_IDENTIFIER);
        } catch (InvalidUserAttributeException $e) {
            $user->addAttribute(self::USER_ATTRIBUTE_RESOURCE_IDENTIFIER, []);
        }
        
        $attribute[$this->name] = $user->getPermission();
        
        $user->addAttribute(self::USER_ATTRIBUTE_RESOURCE_IDENTIFIER, $attribute);
    }
    
    /**
     * Add a permission to the resource.
     * This implementation is restricting to a certain permission pattern.
     * An already registered permission cannot be override.
     * MUST respect pattern [a-z_].
     * Cannot have more than allowed permissions.
     * Cannot register reserved permissions
     * 
     * @param string $permission
     *   Permission name
     *   
     * @throws InvalidPermissionException
     *   When the permission is considered invalid. Refer to error message for more information
     */
    public function addPermission(string $permission): void
    {
         $currentCountPermissions = \count($this->permissions);
         
         switch ($permission) {
             case !\preg_match("#^[a-z_]+$#", $permission):
                 $message = "This permission '{$permission}' is invalid as it does not respect [a-z_] pattern";
                 $code = ResourceInterface::RESOURCE_ERROR_CODE_INVALID_PERMISSION;
                 break;
             case \in_array($permission, self::RESERVED_PERMISSIONS):
                 $message = "This permission '{$permission}' is invalid as it is reserved";
                 $code = ResourceInterface::RESOURCE_ERROR_CODE_RESERVED_PERMISSION;
                 break;
             case isset($this->permissions[$permission]):
                $message = "This permission '{$permission}' is already registered into resource '{$this->name}' and cannot be redefined";
                $code = ResourceInterface::RESOURCE_ERROR_CODE_ALREADY_REGISTERED_PERMISSION;
                break;
             case $currentCountPermissions - \count(self::RESERVED_PERMISSIONS) > self::MAX_PERMISSIONS:
                $message = "Cannot add more permission for resource '{$this->name}'. Max permission allowed setted to " . self::MAX_PERMISSIONS;
                $code = ResourceInterface::RESOURCE_ERROR_CODE_MAX_PERMISSIONS_REACHED;
                break;
         }
         
         if(isset($message))
             throw new InvalidPermissionException($message, $code);

         $this->permissions[self::ALL_PERMISSIONS] |= $this->permissions[$permission] = 1 << $currentCountPermissions - \count(self::RESERVED_PERMISSIONS);
    }
    
    /**
     * Add an entry to the resource
     * 
     * @param string $name
     *   Entry name
     * @param string $processor
     *   Processor handling the entry
     */
    public function addEntry(string $name, ?string $processor = null): void
    {
        $this->entries[$processor][$name] = [];
    }
    
    /**
     * Add a value the an entity already registered
     * 
     * @param string $value
     *   Value name
     * @param array $permissions
     *   Permissions setted for this value
     * @param string $entry
     *   Entry which the value is linked
     * 
     * @throws EntryValueNotFoundException
     *   When given entity is not registered
     * @throws PermissionNotFoundException
     *   When a given permission is not registered
     */
    public function addValue(string $value, array $permissions, string $entry): void
    {
        foreach ($this->entries as $processor => $entries) {
            if(isset($entries[$entry])) {
                $this->entries[$processor][$entry][$value] = $this->getPermissions($permissions);
                return;                
            }
        }
        
        throw new EntryValueNotFoundException("This entry '{$entry}' is not registered into resource '{$this->name}'");
    }
    
    
    /**
     * Set permission for the current operation depending of the given context
     * 
     * @param string|array $permission
     *   Permission handled
     * @param string $action
     *   Action to set (grant or deny)
     * 
     * @throws \LogicException
     *   When no user has been declared
     * @throws \TypeError
     *   When permission is neither an array or a string
     */
    private function set($permission, string $action): void
    {
        if(null === $this->user)
            throw new \LogicException("Cannot perform action on permissions user as it has not been defined by a previous of to() or action on it has been finalized"); 
        
        $type = \gettype($permission);
        switch ($action) {
            case $action === "grant":
                $this->current |= ($type === "array") ? $this->getPermissions($permission) : $this->getPermission($permission);
                break;
            case $action === "deny":
                $this->current &= ($type === "array") ? $this->getPermissions($permission) : ~($this->getPermission($permission));
                break;
        }
    }

}
