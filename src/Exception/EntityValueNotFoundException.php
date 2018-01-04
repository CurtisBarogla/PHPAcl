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
 * When a value is not registered into an entity resource
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class EntityValueNotFoundException extends \InvalidArgumentException
{
    
    /**
     * Invalid entity value
     * 
     * @var string|null
     */
    private $value;
    
    /**
     * Get invalid entity value.
     * Can be null if no entity value has been setted for this exception
     *
     * @return string|null
     *   Invalid entity value
     */
    public function getInvalidValue(): ?string
    {
        return $this->value;
    }
    
    /**
     * Set invalid entity value
     *
     * @param string $value
     *   Invalid entity value
     */
    public function setInvalidValue(string $value): void
    {
        $this->value = $value;
    }
    
}
