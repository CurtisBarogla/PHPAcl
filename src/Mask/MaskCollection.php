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

use Zoe\Component\Acl\Exception\InvalidMaskException;

/**
 * Collection of bit masks
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class MaskCollection implements \JsonSerializable, \IteratorAggregate, \Countable
{
    
    /**
     * Masks registered
     * 
     * @var Mask[]
     */
    private $masks;
    
    /**
     * Collection identifier
     * 
     * @var string
     */
    private $identifier;
    
    /**
     * Initialize collection
     * 
     * @param string $identifier
     *   Collection identifier
     */
    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }
    
    /**
     * {@inheritDoc}
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): \Generator
    {
        foreach ($this->masks as $mask)
            yield $mask->getIdentifier() => $mask;
    }
    
    /**
     * Get collection identifier
     * 
     * @return string
     *   Collection identifier
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
    
    /**
     * Get a mask initialized totalizing all values setted into the collection
     * 
     * @return Mask
     *   Total mask value
     */
    public function total(?string $totalIdentifier = null): Mask
    {
        $total = new Mask($totalIdentifier ?? "TOTAL_COLLECTION_{$this->identifier}");
        foreach ($this->masks as $mask)
            $total->add($mask);
        
        return $total;
    }
    
    /**
     * Add a mask into the collection
     * 
     * @param Mask $mask
     *   Mask to add
     */
    public function add(Mask $mask): void
    {
        $this->masks[$mask->getIdentifier()] = $mask;
    }
    
    /**
     * Get a mask from the collection
     * 
     * @param string $mask
     *   Mask identifier
     * 
     * @return Mask
     *   Mask
     *   
     * @throws InvalidMaskException
     *   When the given mask is not registered into the collection
     */
    public function get(string $mask): Mask
    {
        return $this->masks[$mask];
    }
    
    /**
     * {@inheritDoc}
     * @see \Countable::count()
     */
    public function count(): int
    {
        if(null === $this->masks)
            return 0;
        
        return \count($this->masks);
    }
    
    /**
     * {@inheritDoc}
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        return [
            "identifier"    =>  $this->identifier,
            "masks"         =>  $this->masks
        ];
    }

    /**
     * Restore a mask collection from his json representation
     * This json representation can be either a string or an array 
     * 
     * @param string|array $json
     *   String or array collection representation
     * 
     * @return MaskCollection
     *   Mask collection restored
     */
    public static function restore($json): MaskCollection
    {
        if(!\is_array($json))
            $json = \json_decode($json, true);
        
        $collection = new MaskCollection($json["identifier"]);
        $collection->masks = \array_map(function(array $mask): Mask {
            return new Mask($mask["identifier"], $mask["value"]);
        }, $json["masks"]);
        
        return $collection;
    }
    
}
