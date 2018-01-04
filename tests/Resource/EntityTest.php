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

namespace ZoeTest\Component\Acl\Resource;

use PHPUnit\Framework\TestCase;
use Zoe\Component\Internal\GeneratorTrait;
use Zoe\Component\Acl\Resource\Entity;
use Zoe\Component\Acl\Exception\EntityValueNotFoundException;

/**
 * Entity testcase
 * 
 * @see \Zoe\Component\Acl\Resource\Entity
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class EntityTest extends TestCase
{
    
    use GeneratorTrait;
    
    /**
     * @see \Zoe\Component\Acl\Resource\Entity::getIterator()
     */
    public function testGetIterator(): void
    {
        $entity = new Entity("Foo");
        $entity->add("Foo", ["Foo", "Bar"]);
        $entity->add("Bar", ["Moz", "Poz"]);
        
        $expected = $this->getGenerator(["Foo" => ["Foo", "Bar"], "Bar" => ["Moz", "Poz"]]);
        
        $this->assertTrue($this->assertGeneratorEquals($expected, $entity->getIterator()));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Entity::getName()
     */
    public function testGetName(): void
    {
        $entity = new Entity("Foo");
        
        $this->assertSame("Foo", $entity->getName());
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Entity::add()
     */
    public function testAdd(): void
    {
        $entity = new Entity("Foo");
        
        $this->assertNull($entity->add("Foo", ["Foo", "Bar"]));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Entity::get()
     */
    public function testGet(): void
    {
        $entity = new Entity("Foo");
        $entity->add("Foo", ["Foo", "Bar"]);
        
        $this->assertSame(["Foo", "Bar"], $entity->get("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Entity::getProcessor()
     */
    public function testGetProcessor(): void
    {
        $entity = new Entity("Foo");
        
        $this->assertNull($entity->getProcessor());
        
        $entity = new Entity("Foo", "FooProcessor");
        
        $this->assertSame("FooProcessor", $entity->getProcessor());
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Entity::jsonSerialize()
     */
    public function testJsonSerialize(): void
    {
        $entity = new Entity("Foo");
        $entity->add("Foo", ["Foo", "Bar"]);
        $entity->add("Bar", ["Moz", "Poz"]);
        
        $this->assertNotFalse(\json_encode($entity));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Entity::count()
     */
    public function testCount(): void
    {
        $entity = new Entity("Foo");
        
        $this->assertSame(0, \count($entity));
        
        $entity = new Entity("Foo");
        $entity->add("Foo", ["Foo", "Bar"]);
        $entity->add("Bar", ["Moz", "Poz"]);
        
        $this->assertSame(2, \count($entity));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Entity::restoreFromJson()
     */
    public function testRestoreFromJson(): void
    {
        $entity = new Entity("Foo");
        $entity->add("Foo", ["Foo", "Bar"]);
        $entity->add("Bar", ["Moz", "Poz"]);
        
        $json = \json_encode($entity);
        
        $this->assertEquals($entity, Entity::restoreFromJson($json));
        
        $json = \json_decode($json, true);
        
        $this->assertEquals($entity, Entity::restoreFromJson($json));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\Resource\Entity::get()
     */
    public function testExceptionGetWhenInvalidValueIsGiven(): void
    {
        $expectedException = new EntityValueNotFoundException("This value 'Foo' is not registered into entity 'Bar'");
        $expectedException->setInvalidValue("Foo");
        $this->assertSame("Foo", $expectedException->getInvalidValue());
        $this->expectException(EntityValueNotFoundException::class);
        $this->expectExceptionMessage("This value 'Foo' is not registered into entity 'Bar'");
        $this->expectExceptionObject($expectedException);
        
        $entity = new Entity("Bar");
        $entity->get("Foo");
    }
    
}
