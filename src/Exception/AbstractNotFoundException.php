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
 * Common to all not found exceptions
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
abstract class AbstractNotFoundException extends \InvalidArgumentException
{
    
    /**
     * Invalid value
     *
     * @var string
     */
    private $notFound;
    
    /**
     * Initialize exception
     *
     * @param string $message
     *   Exception message
     * @param string|null $value
     *   Not found value
     */
    public function __construct(string $message, string $value)
    {
        parent::__construct($message);
        $this->notFound = $value;
    }
    
    /**
     * Get not founded value
     *
     * @return string
     *   Invalid value
     */
    public function getNotFoundValue(): string
    {
        return $this->notFound;
    }
    
}
