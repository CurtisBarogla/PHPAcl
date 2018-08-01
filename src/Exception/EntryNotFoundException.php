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
 * EntryNotFound exception
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class EntryNotFoundException extends \Exception
{
    
    /**
     * Entry not found
     * 
     * @var string
     */
    private $entry;
    
    /**
     * Set not founded entry
     * 
     * @param string $entry
     *   Entry name
     */
    public function setEntry(string $entry): void
    {
        $this->entry = $entry;
    }
    
    /**
     * Get not founded entry
     * 
     * @return string
     *   Entry name
     */
    public function getEntry(): string
    {
        return $this->entry;
    }
    
}
