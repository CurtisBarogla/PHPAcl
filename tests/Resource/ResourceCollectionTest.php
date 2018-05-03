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
use PHPUnit\Framework\MockObject\MockObject;
use Zoe\Component\Acl\Resource\ResourceInterface;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
use Zoe\Component\Acl\Resource\ResourceCollection;
use Zoe\Component\Acl\Resource\ProcessableResourceInterface;
use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Exception\ResourceNotFoundException;
use Zoe\Component\Acl\Resource\Resource;
use Zoe\Component\Acl\Exception\InvalidPermissionException;

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
        $resources = [
            "Foo"   =>  $this->mockResource($this->once(), "Foo"),
            "Bar"   =>  $this->mockResource($this->once(), "Bar")
        ];
        $expected = $this->getGenerator($resources);
        
        $collection = new ResourceCollection("FooCollection");
        foreach ($resources as $resource)
            $collection->addResource($resource);
        
        $this->assertTrue($this->assertGeneratorEquals($expected, $collection->getIterator()));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::getName()
     */
    public function testGetName(): void
    {
        $collection = new ResourceCollection("FooCollection");
        
        $this->assertSame("FooCollection", $collection->getName());
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::getResource()
     */
    public function testGetResource(): void
    {
        $resource = $this->mockResource($this->once(), "Foo");
        
        $collection = new ResourceCollection("FooCollection");
        $collection->addResource($resource);
        
        $this->assertSame($resource, $collection->getResource("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::shouldBeProcessed()
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::process()
     */
    public function testShouldBeProcessAndProcess(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $notProcessable = $this->mockResource($this->once(), "Foz");
        $processableFooToProcess = $this->mockResource($this->once(), "Foo", true);
        $processableFooToProcess->expects($this->once())->method("shouldBeProcessed")->with($user)->will($this->returnValue(true));
        $processableFooToProcess->expects($this->once())->method("process")->with([], $user)->will($this->returnValue(null));        
        
        $processableBarToNotProcess = $this->mockResource($this->once(), "Bar", true);
        $processableBarToNotProcess->expects($this->once())->method("shouldBeProcessed")->with($user)->will($this->returnValue(false));
        $processableBarToNotProcess->expects($this->never())->method("process");
        
        $collection = new ResourceCollection("FooCollection");
        $collection->addResource($notProcessable);
        $collection->addResource($processableFooToProcess);
        $collection->addResource($processableBarToNotProcess);
        
        $this->assertTrue($collection->shouldBeProcessed($user));
        $this->assertNull($collection->process([], $user));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::addResource()
     */
    public function testAddResource(): void
    {
        $collection = new ResourceCollection("FooCollection");
        
        $this->assertNull($collection->addResource($this->mockResource($this->once(), "Foo")));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::initializeCollection()
     */
    public function testInitializeCollection(): void
    {
        $resourceNotImplementingAddPermissionMethod = $this->mockResource($this->once(), "Foo");
        // Resources here are based on native implementation of ResourceInterface displaying an addPermission() method
        $resourceBar = $this->getMockBuilder(Resource::class)->disableOriginalConstructor()->getMock();
        $resourceBar->expects($this->once())->method("getName")->will($this->returnValue("Bar"));
        // all shared permissions are added into
        $resourceBar->expects($this->exactly(3))->method("addPermission")->withConsecutive(["foo"], ["bar"], ["moz"])->will($this->returnValue(null));
        
        $exception = new InvalidPermissionException("", ResourceInterface::RESOURCE_ERROR_CODE_ALREADY_REGISTERED_PERMISSION);
        $resourceFoz = $this->getMockBuilder(Resource::class)->disableOriginalConstructor()->getMock();
        $resourceFoz->expects($this->once())->method("getName")->will($this->returnValue("Foz"));
        $resourceFoz
            ->expects($this->exactly(3))
            ->method("addPermission")
            ->withConsecutive(["foo"], ["bar"], ["moz"])
            ->will($this->onConsecutiveCalls(null, null, $this->throwException($exception)));
        
        $collection = ResourceCollection::initializeCollection(
            "FooCollection", 
            [$resourceNotImplementingAddPermissionMethod, $resourceBar, $resourceFoz],
            ["foo", "bar", "moz"]);
        
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::getResource()
     */
    public function testExceptionGetResourceWhenGivenResourceIsNotRegistered(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource 'Foo' is not registered into resource collection 'FooCollection'");
        
        $collection = new ResourceCollection("FooCollection");
        $collection->getResource("Foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::process()
     */
    public function testExceptionProcessWhenShouldProcessNotCalled(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("No resource are processable actually. Did you make a call to shouldBeProcessed ?");
        
        $collection = new ResourceCollection("FooCollection");
        
        $collection->process([], $this->getMockBuilder(AclUserInterface::class)->getMock());
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::initializeCollection()
     */
    public function testExceptionInitializeCollectionWhenReservedPermissionIsGivenAsSharedPermissions(): void
    {
        $reservedPermission = ResourceInterface::RESERVED_PERMISSIONS[\array_rand(ResourceInterface::RESERVED_PERMISSIONS)];
        $this->expectException(InvalidPermissionException::class);
        $this->expectExceptionMessage("Reserved permissions are given as shared. '{$reservedPermission}'");
        
        $collection = ResourceCollection::initializeCollection("FooCollection", [], [$reservedPermission, "foo"]);
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::initializeCollection()
     */
    public function testInitializeCollectionWhenAResourceIsNotAnInstanceOfResourceInterface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Resource MUST be an instance of ResourceInterface for initializing collection : 'FooCollection'");
        
        $collection = ResourceCollection::initializeCollection("FooCollection", ["foo"]);
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\ResourceCollection::initializeCollection()
     */
    public function testExceptionInitializeCollectionWhenAResourceThrowACatchableErrorCode(): void
    {
        $this->expectException(InvalidPermissionException::class);
        
        $exception = new InvalidPermissionException("", ResourceInterface::RESOURCE_ERROR_CODE_INVALID_PERMISSION);
        $resource = $this->getMockBuilder(Resource::class)->disableOriginalConstructor()->getMock();
        $resource->expects($this->once())->method("addPermission")->with("Foo")->will($this->throwException($exception));
        
        $collection = ResourceCollection::initializeCollection("FooCollection", [$resource], ["Foo"]);
    }
    
    /**
     * Mock a ResourceInterface with name returned by the getter
     
     * @param InvokedCount $count
     *   Count for getName()
     * @param string $name
     *   Mocked resource name
     * @param bool $processable
     *   If the resource if a processable one
     * 
     * @return MockObject
     *   Mocked resource
     */
    private function mockResource(InvokedCount $count, string $name, bool $processable = false): MockObject
    {
        if(!$processable)
            $mock = $this->getMockBuilder(ResourceInterface::class)->getMock();
        else 
            $mock = $this->getMockBuilder([ResourceInterface::class, ProcessableResourceInterface::class])->getMock();
        
        $mock->expects($count)->method("getName")->will($this->returnValue($name));
        
        return $mock;
    }
    
}
