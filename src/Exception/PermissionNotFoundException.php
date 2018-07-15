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

namespace Ness\Component\Acl\Exception;

/**
 * PermissionNotFound exception
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class PermissionNotFoundException extends \Exception
{
    
    /**
     * Permission not found
     * 
     * @var string
     */
    private $permission;

    /**
     * Set not found permission
     * 
     * @param string $permission
     *   Not founded permission
     */
    public function setPermission(string $permission): void
    {
        $this->permission = $permission;
    }
    
    /**
     * Get setted not founded permission
     * 
     * @return string
     *   Not founded permission
     */
    public function getPermission(): string
    {
        return $this->permission;
    }
    
}
