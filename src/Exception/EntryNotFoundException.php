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
     * Initialize exception
     * 
     * @param string $entry
     *   Entry not found
     * @param string $message
     *   Exception message
     * @param int $code
     *   Exception code
     * @param \Throwable $previous
     */
    public function __construct(string $entry, string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
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
