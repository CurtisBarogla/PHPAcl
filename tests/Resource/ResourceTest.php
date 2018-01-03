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
use Zoe\Component\Acl\Resource\Resource;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\Mask\MaskCollection;
use Zoe\Component\Acl\Resource\EntityInterface;
use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Exception\PermissionNotFoundException;
use Zoe\Component\Acl\Exception\EntityNotFoundException;
use Zoe\Component\Acl\Exception\InvalidResourceBehaviourException;
use Zoe\Component\Acl\Processor\AclProcessorInterface;
use Zoe\Component\Acl\JsonRestorableInterface;
use Zoe\Component\Acl\Resource\Entity;

/**
 * Resource testcase
 * 
 * @see \Zoe\Component\Acl\Resource\Resource
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ResourceTest extends TestCase
{
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getName()
     */
    public function testGetName(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertSame("Foo", $resource->getName());
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getBehaviour()
     */
    public function testGetBehaviour(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertSame(ResourceInterface::BLACKLIST, $resource->getBehaviour());
        
        $resource = new Resource("Foo", ResourceInterface::WHITELIST);
        
        $this->assertSame(ResourceInterface::WHITELIST, $resource->getBehaviour());
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::addPermission()
     */
    public function testAddPermission(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertNull($resource->addPermission("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getPermission()
     */
    public function testGetPermission(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addPermission("Foo");
        $resource->addPermission("Bar");
        $resource->addPermission("Moz");
        
        $this->assertSame(1, $resource->getPermission("Foo")->getValue());
        $this->assertSame(2, $resource->getPermission("Bar")->getValue());
        $this->assertSame(4, $resource->getPermission("Moz")->getValue());
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getPermissions()
     */
    public function testGetPermissions(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addPermission("Foo");
        $resource->addPermission("Bar");
        
        $this->assertInstanceOf(MaskCollection::class, $resource->getPermissions());
        $this->assertCount(2, $resource->getPermissions());
        
        $this->assertCount(1, $resource->getPermissions(["Foo"]));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::registerEntity()
     */
    public function testRegisterEntity(): void
    {
        $entity = $this->getMockBuilder([EntityInterface::class, JsonRestorableInterface::class])->getMock();
        $entity->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertNull($resource->registerEntity($entity));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getEntity()
     */
    public function testGetEntity(): void
    {
        $entity = $this->getMockBuilder([EntityInterface::class, JsonRestorableInterface::class])->getMock();
        $entity->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resource->registerEntity($entity);
        
        $this->assertSame($entity, $resource->getEntity("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getEntities()
     */
    public function testGetEntities(): void
    {
        $fooEntity = $this->getMockBuilder([EntityInterface::class, JsonRestorableInterface::class])->getMock();
        $fooEntity->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        
        $barEntity = $this->getMockBuilder([EntityInterface::class, JsonRestorableInterface::class])->getMock();
        $barEntity->expects($this->once())->method("getName")->will($this->returnValue("Bar"));
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resource->registerEntity($fooEntity);
        $resource->registerEntity($barEntity);
        
        $this->assertSame(["Foo" => $fooEntity, "Bar" => $barEntity], $resource->getEntities());
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertNull($resource->getEntities());
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::process()
     */
    public function testProcess(): void
    {
        // no entity registered
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $this->assertNull($resource->process([], $user));
        
        // entities registered
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $fooProcessor = $this->getMockBuilder(AclProcessorInterface::class)->getMock();
        $fooProcessor->expects($this->once())->method("process")->with($resource, $user)->will($this->returnValue(null));
        $processors = [
            "FooProcessor"  =>  $fooProcessor   
        ];
        
        // continue
        $nullProcessorEntity = $this->getMockBuilder([EntityInterface::class, JsonRestorableInterface::class])->getMock();
        $nullProcessorEntity->expects($this->once())->method("getName")->will($this->returnValue("NULL"));
        $nullProcessorEntity->expects($this->once())->method("getProcessor")->will($this->returnValue(null));
        
        // continue
        $emptyEntity = $this->getMockBuilder([EntityInterface::class, JsonRestorableInterface::class])->getMock();
        $emptyEntity->expects($this->once())->method("getName")->will($this->returnValue("EMPTY"));
        $emptyEntity->expects($this->once())->method("getProcessor")->will($this->returnValue("EMPTY"));
        $emptyEntity->expects($this->once())->method("count")->will($this->returnValue(0));
        
        // processed
        $fooProcessorEntity = $this->getMockBuilder([EntityInterface::class, JsonRestorableInterface::class])->getMock();
        $fooProcessorEntity->expects($this->once())->method("getName")->will($this->returnValue("FOOPROCESSOR"));
        $fooProcessorEntity->expects($this->once())->method("getProcessor")->will($this->returnValue("FooProcessor"));
        $fooProcessorEntity->expects($this->once())->method("count")->will($this->returnValue(1));
        
        $fooProcessor->expects($this->once())->method("setEntity")->with($fooProcessorEntity)->will($this->returnValue(null));
        
        $resource->registerEntity($nullProcessorEntity);
        $resource->registerEntity($emptyEntity);
        $resource->registerEntity($fooProcessorEntity);
        
        $this->assertNull($resource->process($processors, $user));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::isProcessed()
     */
    public function testIsProcessed(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertFalse($resource->isProcessed());
        
        $resource->process([], $user);
        
        $this->assertTrue($resource->isProcessed());
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::jsonSerialize()
     */
    public function testJsonSerialize(): void
    {
        $entityFoo = new Entity("Foo", "FooProcessor");
        $entityFoo->add("Foo", ["Foo", "Bar"]);
        $entityFoo->add("Bar", ["Moz", "Poz"]);
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resource->registerEntity($entityFoo);
        
        $this->assertNotFalse(\json_encode($resource));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::restoreFromJson()
     */
    public function testRestoreFromJson(): void
    {
        $entityFoo = new Entity("Foo", "FooProcessor");
        $entityFoo->add("Foo", ["Foo", "Bar"]);
        $entityFoo->add("Bar", ["Moz", "Poz"]);
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resource->registerEntity($entityFoo);
        
        $json = \json_encode($resource);
        
        $this->assertEquals($resource, Resource::restoreFromJson($json));
        
        $json = \json_decode($json, true);
        
        $this->assertEquals($resource, Resource::restoreFromJson($json));
        
        // no entity
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $json = \json_encode($resource);
        
        $this->assertEquals($resource, Resource::restoreFromJson($json));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::__construct()
     */
    public function testExceptionWhenInvalidBehaviourIsGiven(): void
    {
        $this->expectException(InvalidResourceBehaviourException::class);
        $this->expectExceptionMessage("This behaviour '3' is invalid for resource 'Foo'. Use one defined into the interface");
        
        $resource = new Resource("Foo", 3);
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getPermission()
     */
    public function testExceptionGetPermissionWhenPermissionIsNotDefined(): void
    {
        $exceptionExpected = new PermissionNotFoundException("This permission 'Bar' for resource 'Foo' is not defined");
        $exceptionExpected->setInvalidPermission("Bar");
        $this->assertSame("Bar", $exceptionExpected->getInvalidPermission());
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'Bar' for resource 'Foo' is not defined");
        $this->expectExceptionObject($exceptionExpected);
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resource->getPermission("Bar");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::addPermission()
     */
    public function testExceptionAddPermissionWhenMaxPermissionsCountIsReached(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Resource cannot be have more than '31' permissions");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        for ($i = 0; $i < 32; $i++) {
            $resource->addPermission((string) $i);
        }
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::registerEntity()
     */
    public function testExceptionRegisterEntityWhenEntityDoesNotImplementJsonRestorable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("This entity 'Foo' MUST implement JsonRestorableInterface for resource 'Bar'");
        
        $entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $entity->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        
        $resource = new Resource("Bar", ResourceInterface::BLACKLIST);
        $resource->registerEntity($entity);
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getEntity()
     */
    public function testExceptionGetEntityWhenEntityIsNotRegistered(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage("This entity is not registered for resource 'Foo'");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resource->getEntity("Foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::process()
     */
    public function testExceptionProcessWhenProcessIsNotRegistered(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("This processor 'FooProcessor' for entity 'BarEntity' attached to 'MozResource' resource is not registered");
        
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        
        $entity = $this->getMockBuilder([EntityInterface::class, JsonRestorableInterface::class])->getMock();
        $entity->expects($this->exactly(2))->method("getName")->will($this->returnValue("BarEntity"));
        $entity->expects($this->once())->method("getProcessor")->will($this->returnValue("FooProcessor"));
        $entity->expects($this->once())->method("count")->will($this->returnValue(1));
        
        $resource = new Resource("MozResource", ResourceInterface::BLACKLIST);
        $resource->registerEntity($entity);
        
        $resource->process([], $user);
    }
    
}
