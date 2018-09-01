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

namespace Ness\Component\Acl\Resource;

/**
 * Common way to describe an acl entry
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface EntryInterface extends \IteratorAggregate
{
    
    /**
     * Get entry name
     * 
     * @return string
     *   Entry name
     */
    public function getName(): string;
    
    /**
     * Get all permissions setted for this entry
     * 
     * @return string[]
     *   All permission accorded
     */
    public function getPermissions(): array;
    
}
