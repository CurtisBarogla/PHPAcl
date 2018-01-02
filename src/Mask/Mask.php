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

namespace Zoe\Component\Acl\Mask;

/**
 * Bit mask
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class Mask implements \JsonSerializable
{
    
    /**
     * Mask identifier
     * 
     * @var string
     */
    private $identifier;
    
    /**
     * Mask value
     * 
     * @var int
     */
    private $value;
    
    /**
     * Initialize a mask
     * 
     * @param string $identifier
     *   Mask identifier
     * @param int $value
     *   Mask value
     */
    public function __construct(string $identifier, int $value = 0)
    {
        $this->identifier = $identifier;
        $this->value = $value;
    }
    
    /**
     * Get mask identifier
     * 
     * @return string
     *   Mask identifier
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
    
    /**
     * Get mask value
     * 
     * @return int
     *   Mask value
     */
    public function getValue(): int
    {
        return $this->value;
    }
    
    /**
     * Add value of an another mask to this one
     * 
     * @param Mask $mask
     *   Mask to add
     */
    public function add(Mask $mask): void
    {
        $this->value |= $mask->getValue();
    }
    
    /**
     * Sub value of an another mask to this one
     * 
     * @param Mask $mask
     *   Mask to sub
     */
    public function sub(Mask $mask): void
    {
        $this->value &= ~($mask->getValue());
    }
    
    /**
     * Shift one bit or more to left
     * 
     * @param int $value
     *   Number of bits to shift
     */
    public function lshift(int $value = 1): void
    {
        $this->value <<= $value;
    }
    
    /**
     * Shifth one bit or more to right
     * 
     * @param int $value
     *   Number of bits to shift
     */
    public function rshift(int $value = 1): void
    {
        $this->value >>= $value;
    }
    
    /**
     * {@inheritDoc}
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize(): array
    {
        return [
            "identifier"    =>  $this->identifier,
            "value"         =>  $this->value
        ];     
    }

}
