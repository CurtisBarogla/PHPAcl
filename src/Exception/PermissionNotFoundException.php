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

namespace Zoe\Component\Acl\Exception;

/**
 * Permission not found from a resource
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class PermissionNotFoundException extends \InvalidArgumentException
{
    
    /**
     * Invalid permission
     * 
     * @var string|null
     */
    private $permission;
    
    /**
     * Get invalid permission.
     * Can be null if no permission has been setted for this exception
     * 
     * @return string|null
     *   Invalid permission
     */
    public function getInvalidPermission(): ?string
    {
        return $this->permission;
    }
    
    /**
     * Set invalid permission
     * 
     * @param string $permission
     *   Invalid permission
     */
    public function setInvalidPermission(string $permission): void
    {
        $this->permission = $permission;
    }
    
    
}