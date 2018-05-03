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
use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Exception\PermissionNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Zoe\Component\User\Exception\InvalidUserAttributeException;
use Zoe\Component\Acl\Exception\InvalidPermissionException;
use Zoe\Component\Acl\Resource\ProcessableResourceInterface;
use Zoe\Component\Acl\Exception\EntryValueNotFoundException;

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
     * @see \Zoe\Component\Acl\Resource\Resource
     */
    public function testInterface(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertInstanceOf(ProcessableResourceInterface::class, $resource);
    }
    
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
     * @see \Zoe\Component\Acl\Resource\Resource::grant()
     */
    public function testGrant(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        
        $this->assertSame($resource, $resource->to($this->getMockedUser())->grant("foo")->grant(["foo", "bar"])->grant("bar"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::deny()
     */
    public function testDeny(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        
        $this->assertSame($resource, $resource->to($this->getMockedUser())->deny("foo")->deny("all")->deny(["foo", "bar"]));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::to()
     */
    public function testTo(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertSame($resource, $resource->to($this->getMockedUser()));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::finalize()
     */
    public function testFinalize(): void
    {
        $user = $this->getMockedUser();
        $user->expects($this->once())->method("setPermission")->with(5)->will($this->returnValue(null));
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        $resource->addPermission("foz");
        
        $this->assertNull($resource->to($user)->grant("all")->deny("all")->grant("foo")->grant(["foo", "foz"])->finalize());
        $this->assertNull($resource->finalize());
    } 
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getPermission()
     */
    public function testGetPermission(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        $resource->addEntry("FooEntry");
        $resource->addValue("FooValue", ["foo", "bar"], "FooEntry");
        
        $this->assertSame(1, $resource->getPermission("foo"));
        $this->assertSame(2, $resource->getPermission("bar"));
        $this->assertSame(3, $resource->getPermission("all"));
        $this->assertSame(3, $resource->getPermission("FooValue"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getPermissions()
     */
    public function testGetPermissions(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        
        $this->assertSame(3, $resource->getPermissions(["foo", "bar"]));
        $this->assertSame(3, $resource->getPermissions(["all", "foo"]));
        $this->assertSame(1, $resource->getPermissions(["foo"]));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::shouldBeProcessed()
     */
    public function testShouldBeProcessed(): void
    {
        $user = $this->getMockedUser();
        $user
            ->expects($this->exactly(3))
            ->method("getAttribute")
            ->withConsecutive([Resource::USER_ATTRIBUTE_RESOURCE_IDENTIFIER])
            ->will($this->onConsecutiveCalls(
                ["Foo" => 3], 
                ["Foo" => 3], 
                $this->throwException(new InvalidUserAttributeException())));
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resource2 = new Resource("Bar", ResourceInterface::BLACKLIST);
        
        $this->assertFalse($resource->shouldBeProcessed($user));
        $this->assertTrue($resource2->shouldBeProcessed($user));
        $this->assertTrue($resource->shouldBeProcessed($user));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::process()
     */
    public function testProcess(): void
    {
        
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::addPermission()
     */
    public function testAddPermission(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertNull($resource->addPermission("foo"));
        $this->assertNull($resource->addPermission("bar"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::addEntry()
     */
    public function testAddEnty(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertNull($resource->addEntry("FooEntry"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::addValue()
     */
    public function testAddValue(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        $resource->addEntry("FooEntry");
                
        $this->assertNull($resource->addValue("FooValue", ["foo", "bar"], "FooEntry"));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::grant()
     */
    public function testExceptionGrantWhenPermissionIsNotRegistered(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'foo' is neither registered as an entity value or a permission into resource 'Foo'");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->to($this->getMockedUser())->grant("foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::grant()
     */
    /*public function testExceptionGrantWhenPermissionIsNeitherAStringOrAnArray(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Permission MUST be a string or an array. 'boolean' given");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->to($this->getMockedUser())->grant(true);
    }*/
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::grant()
     */
    public function testExceptionGrantWhenNoUserHasBeenDefined(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot perform action on permissions user as it has not been defined by a previous of to() or action on it has been finalized");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->grant("foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::deny()
     */
    public function testExceptionDenyWhenPermissionIsNotRegistered(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'foo' is neither registered as an entity value or a permission into resource 'Foo'");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->to($this->getMockedUser())->deny("foo");
    }
    
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::deny()
     */
    /*public function testExceptionDenyWhenPermissionIsNeitherAStringOrAnArray(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Permission MUST be a string or an array. 'boolean' given");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->to($this->getMockedUser())->deny(true);
    }*/
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::deny()
     */
    public function testExceptionDenyWhenNoUserHasBeenDefined(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot perform action on permissions user as it has not been defined by a previous of to() or action on it has been finalized");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->deny("foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::addPermission()
     */
    public function testExceptionAddPermissionWhenPermissionDoesNotRespectValidPattern(): void
    {
        $this->expectException(InvalidPermissionException::class);
        $this->expectExceptionMessage("This permission 'Foo' is invalid as it does not respect [a-z_] pattern");
        $this->expectExceptionCode(ResourceInterface::RESOURCE_ERROR_CODE_INVALID_PERMISSION);
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addPermission("Foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::addPermission()
     */
    public function testExceptionAddPermissionWhenPermissionIsReserved(): void
    {
        $permission = ResourceInterface::RESERVED_PERMISSIONS[\array_rand(ResourceInterface::RESERVED_PERMISSIONS, 1)];
        $this->expectException(InvalidPermissionException::class);
        $this->expectExceptionMessage("This permission '{$permission}' is invalid as it is reserved");
        $this->expectExceptionCode(ResourceInterface::RESOURCE_ERROR_CODE_RESERVED_PERMISSION);
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addPermission($permission);
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::addPermission()
     */
    public function testExceptionAddPermissionWhenPermissionIsAlreadyRegistered(): void
    {
        $this->expectException(InvalidPermissionException::class);
        $this->expectExceptionMessage("This permission 'foo' is already registered into resource 'Foo' and cannot be redefined");
        $this->expectExceptionCode(ResourceInterface::RESOURCE_ERROR_CODE_ALREADY_REGISTERED_PERMISSION);
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addPermission("foo");
        $resource->addPermission("foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::addPermission()
     */
    public function testExceptionAddPermissionWhenMaxPermissionsAllowedIsReached(): void
    {
        $this->expectException(InvalidPermissionException::class);
        $this->expectExceptionMessage("Cannot add more permission for resource 'Foo'. Max permission allowed setted to " . ResourceInterface::MAX_PERMISSIONS);
        $this->expectExceptionCode(ResourceInterface::RESOURCE_ERROR_CODE_MAX_PERMISSIONS_REACHED);
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        for ($i = "aa"; $i < "zz"; $i++) {
            $resource->addPermission($i);
        }
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::addValue()
     */
    public function testExceptionAddValueWhenGivenEntryIsNotRegistered(): void
    {
        $this->expectException(EntryValueNotFoundException::class);
        $this->expectExceptionMessage("This entry 'FooEntry' is not registered into resource 'Foo'");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addValue("FooValue", ["foo", "bar"], "FooEntry");
    }
    
    /**
     * Initialize a new mocked acl user
     * 
     * @return MockObject
     *   Mocked acl user
     */
    private function getMockedUser(): MockObject
    {
        return $this->getMockBuilder(AclUserInterface::class)->getMock();;
    }
    
}
