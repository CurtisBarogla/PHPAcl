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

use Zoe\Component\Acl\Exception\PermissionNotFoundException;
use Zoe\Component\Acl\Mask\Mask;
use Zoe\Component\Acl\Mask\MaskCollection;
use Zoe\Component\Acl\Processor\AclProcessorInterface;
use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Exception\EntityNotFoundException;
use Zoe\Component\Acl\Exception\InvalidResourceBehaviourException;

/**
 * Resource linked to acl
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResourceInterface
{
    
    /**
     * 32 bits
     * 
     * @var int
     */
    public const MAX_PERMISSIONS = 31;
    
    /**
     * Resource is in a blacklist behaviour.
     * All permissions are accorded to all users and must be blacklisted to restrict access
     * 
     * @var int
     */
    public const BLACKLIST = 0;
    
    /**
     * Resource is in a whitlist behaviour
     * All permission are denied to all users and must be whitelisted one by one
     * 
     * @var int
     */
    public const WHITELIST = 1;
    
    /**
     * Get resource name
     * 
     * @return string
     *   Resource name
     */
    public function getName(): string;
    
    /**
     * Get resource behaviour.
     * 0 : BLACKLIST <br />
     * 1 : WHITELIST <br />
     * Use one of the const defined into the interface if a comparaison must be done
     * 
     * @return int
     *   Resource behaviour
     *   
     * @throws InvalidResourceBehaviourException
     *   When behaviour is invalid
     */
    public function getBehaviour(): int;
    
    /**
     * Get a permission defined into the resource
     * 
     * @param string $permission
     *   Permission name
     * 
     * @return Mask
     *   Permission mask
     *   
     * @throws PermissionNotFoundException
     *   When the given permission is invalid
     */
    public function getPermission(string $permission): Mask;
    
    /**
     * Get all permissions defined into the resource
     * 
     * @param array|null $permissions
     *   Specific permissions to get or null
     * 
     * @return MaskCollection
     *   All permissions defined or given one
     *   
     * @throws PermissionNotFoundException
     *   When a permission is invalid
     */
    public function getPermissions(?array $permissions = null): MaskCollection;
    
    /**
     * Get a registered entity
     * 
     * @param string $entity
     *   Entity name
     * 
     * @return EntityInterface
     *   Entity
     *   
     * @throws EntityNotFoundException
     *   When the given entity is not registered
     */
    public function getEntity(string $entity): EntityInterface;
    
    /**
     * Get all entities registered into the resource. 
     * Return null if no entity has been registered
     * 
     * @return EntityInterface[]|null
     *   Array of entities indexed by its name or null  
     */
    public function getEntities(): ?array;
        
    /**
     * Process the resource values over a set of AclProcessor for an acl user.
     * Entity with no value or processor defined MUST be skipped
     * 
     * @param AclProcessorInterface[] $processors
     *   Array of processors indexed by its name
     * @param AclUserInterface $user
     *   User processed
     *   
     * @throws \RuntimeException
     *   When the resource cannot be builded
     */
    public function process(array $processors, AclUserInterface $user): void;
    
    /**
     * Check if the resource has been already processed
     * 
     * @return bool
     *   True if the resource has been processed. False otherwise
     */
    public function isProcessed(): bool;
    
}
