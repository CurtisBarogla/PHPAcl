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
use Zoe\Component\Acl\Resource\Resource;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\Resource\ResourceCollection;
use Zoe\Component\Acl\Exception\InvalidPermissionException;
use Zoe\Component\Acl\Exception\ResourceNotFoundException;

/**
 * ResourceCollection testcase
 * 
 * @see \Zoe\Component\Acl\Resource\ResourceCollection
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ResourceCollectionTest extends TestCase
{
    
    use GeneratorTrait;
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::getIterator()
     */
    public function testGetIterator(): void
    {
        $resourceFoo = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resourceBar = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resourceFoo->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        $resourceBar->expects($this->once())->method("getName")->will($this->returnValue("Bar"));
        
        $expected = $this->getGenerator(["Foo" => $resourceFoo, "Bar" => $resourceBar]);
        
        $collection = new ResourceCollection("Foo");
        $collection->add($resourceFoo);
        $collection->add($resourceBar);
        
        $this->assertTrue($this->assertGeneratorEquals($expected, $collection->getIterator()));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::getIdentifier()
     */
    public function testGetIdentifier(): void
    {
        $collection = new ResourceCollection("Foo");
        
        $this->assertSame("Foo", $collection->getIdentifier());
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::get()
     */
    public function testGet(): void
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        
        $collection = new ResourceCollection("Foo");
        $collection->add($resource);
        
        $this->assertSame($resource, $collection->get("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::add()
     */
    public function testAdd(): void
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        
        $collection = new ResourceCollection("Foo");
        
        $this->assertSame($collection, $collection->add($resource));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::initializeCollection()
     */
    public function testInitializeCollection(): void
    {
        $resourceFoo = $this->getMockBuilder(Resource::class)->disableOriginalConstructor()->getMock();
        $resourceFoo
            ->expects($this->exactly(5))->method("addPermission")
            ->withConsecutive(["moz"], ["foo"], ["bar"], ["moz"], ["poz"])
            ->will($this->onConsecutiveCalls(
                $resourceFoo, 
                $resourceFoo, 
                $resourceFoo, 
                $this->throwException(new InvalidPermissionException()), 
                $resourceFoo));
        $resourceFoo->addPermission("moz");
        $resourceFoo->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        
        $collection = ResourceCollection::initializeCollection("Foo", [$resourceFoo], ["foo", "bar", "moz", "poz"]);
        
        $this->assertSame($resourceFoo, $collection->get("Foo"));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::get()
     */
    public function testExceptionGetWhenResourceIsNotRegistered(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource 'Foo' is not registered into resource collection 'FooCollection'");
        
        $collection = new ResourceCollection("FooCollection");
        
        $collection->get("Foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::initializeCollection()
     */
    public function testExceptionInitializeCollectionWhenResourceGivenIsNotANativeOne(): void
    {
        $classResourceName = Resource::class;
        $collectionName = ResourceCollection::class;
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Resource collection {$collectionName} only accept instances of {$classResourceName}");
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        
        $collection = ResourceCollection::initializeCollection("Foo", [$resource]);
    }

}
