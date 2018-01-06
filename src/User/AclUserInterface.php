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

namespace Zoe\Component\Acl\User;

use Zoe\Component\User\UserInterface;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\Exception\PermissionNotFoundException;
use Zoe\Component\Acl\Mask\Mask;

/**
 * User interacting with an acl
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclUserInterface extends UserInterface
{
    
    /**
     * Grant some permissions over a resource.
     * Look for a raw permission before looking for an entity value
     * 
     * @param ResourceInterface $resource
     *   Resource which the permissions are granted
     * @param array $permissions
     *   Permissions to grant. This can be either a raw permission or a resource value
     *   
     * @throws PermissionNotFoundException
     *   When a permission is invalid over the resource
     */
    public function grant(ResourceInterface $resource, array $permissions): void;
    
    /**
     * Deny some permissions over a resource
     * Look for a raw permission before looking for an entity value
     * 
     * @param ResourceInterface $resource
     *   Resource which the permissions are denied
     * @param array $permissions
     *   Permissions to deny. This can be either a raw permission or a resource value
     *   
     * @throws PermissionNotFoundException
     *   When a permission is invalid over the resource
     */
    public function deny(ResourceInterface $resource, array $permissions): void;
    
    /**
     * Get permissions granted or denied
     * 
     * @return Mask
     *   Permission mask
     */
    public function getPermission(): Mask;
    
}
