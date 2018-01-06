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

namespace Zoe\Component\Acl\Resource;

use Zoe\Component\Acl\JsonRestorableInterface;
use Zoe\Component\Acl\Exception\EntityValueNotFoundException;

/**
 * Native entity implementation
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class Entity implements EntityInterface, JsonRestorableInterface
{
    
    /**
     * Entity name
     * 
     * @var string
     */
    private $name;

    /**
     * Values registered
     * 
     * @var array|null
     */
    private $values;
    
    /**
     * Acl processor identifier handling the entity
     * 
     * @var string|null
     */
    private $processor;
    
    /**
     * Initialize an entity
     * 
     * @param string $name
     *   Entity name
     * @param string $processor
     *   Acl processor identifier
     */
    public function __construct(string $name, ?string $processor = null)
    {
        $this->name = $name;
        $this->processor = $processor;
    }
    
    /**
     * {@inheritDoc}
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): \Generator
    {
        foreach ($this->values as $value => $permissions) {
            yield $value => $permissions;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\EntityInterface::getName()
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Link resource permission to entity value
     * 
     * @param string $value
     *   Entity value
     * @param array $permissions
     *   Permissions applied to this value. No verification are done over the setted permissions on this implementation. So be careful
     */
    public function add(string $value, array $permissions): void
    {
        $this->values[$value] = $permissions;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\EntityInterface::get()
     */
    public function get(string $value): array
    {
        if(!isset($this->values[$value])) {
            $expection = new EntityValueNotFoundException(\sprintf("This value '%s' is not registered into entity '%s'",
                $value,
                $this->name));
            $expection->setInvalidValue($value);
            
            throw $expection;
        }
            
        return $this->values[$value];
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\EntityInterface::has()
     */
    public function has(string $value): bool
    {
        return isset($this->values[$value]);
    }
   
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Resource\EntityInterface::getProcessor()
     */
    public function getProcessor(): ?string
    {
        return $this->processor;
    }

    /**
     * {@inheritDoc}
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize(): array
    {
        return [
            "name"      =>  $this->name,
            "processor" =>  $this->processor,
            "values"    =>  $this->values
        ];     
    }
    
    /**
     * {@inheritDoc}
     * @see \Countable::count()
     */
    public function count()
    {
        return (null !== $this->values) ? \count($this->values) : 0;
    }

    /**
     * Restore the entity
     * 
     * @see \Zoe\Component\Acl\JsonRestorableInterface::restoreFromJson()
     * 
     * @return EntityInterface
     *   Entity restored
     */
    public static function restoreFromJson($json): EntityInterface
    {
        if(!\is_array($json))
            $json = \json_decode($json, true);
        
        $entity = new Entity($json["name"], $json["processor"]);
        $entity->values = $json["values"];
        
        return $entity;
    }

}
