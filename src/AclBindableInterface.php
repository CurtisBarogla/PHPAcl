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

/**
 * Allow a component to communicate with the acl
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclBindableInterface
{
    
    /**
     * Identify the resource to the acl.
     * MUST refer a valid acl resource <br />
     * MUST be registered in a hierarchical way from the more specialized to the general one loadable <br />
     * (e.g) <br />
     *    <pre>["Article_{$this->id}", "Article"]</pre>
     * will try to load Article_{$this->id} than fallback on Article resource
     * 
     * @return string[]
     *   A list of resource names which refer to a valid one into the acl.
     */
    public function getAclResourceHierarchy(): array;
    
    /**
     * Update a user over a permission asked by the acl
     * If a boolean is returned, the permission will be updated depending of the resource behaviour
     * 
     * @param UserInterface $user
     *   User passed to the acl
     * @param string $permission
     *   Permission currently to verify
     * @param bool $granted
     *   If the current permission is currently granted
     * 
     * @return bool|null
     *   Depending of the resource behaviour, will deny or grant an exceptional permission
     *   Return null to make no update at all
     */
    public function updateAclPermission(UserInterface $user, string $permission, bool $granted): ?bool;
    
}
