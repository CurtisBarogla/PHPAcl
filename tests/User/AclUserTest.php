<?php
//StrictType
declare(strict_types = 1);

/*
 * Ness
 * Acl component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */

namespace NessTest\Component\Acl\User;

use NessTest\Component\Acl\AclTestCase;
use Ness\Component\Acl\User\AclUser;
use Ness\Component\User\UserInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Exception\PermissionNotFoundException;
use Ness\Component\Acl\User\AclUserInterface;

/**
 * AclUser testcase
 * 
 * @see \Ness\Component\Acl\User\AclUser
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class AclUserTest extends AclTestCase
{
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::getName()
     * @see \Ness\Component\Acl\User\AclUser::addAttribute()
     * @see \Ness\Component\Acl\User\AclUser::getAttribute()
     * @see \Ness\Component\Acl\User\AclUser::getAttributes()
     * @see \Ness\Component\Acl\User\AclUser::deleteAttribute()
     * @see \Ness\Component\Acl\User\AclUser::getRoles()
     * @see \Ness\Component\Acl\User\AclUser::hasRole()
     */
    public function testBase(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        $user->expects($this->once())->method("addAttribute")->with("foo", "bar");
        $user->expects($this->once())->method("getAttribute")->with("foo")->will($this->returnValue("bar"));
        $user->expects($this->once())->method("getAttributes")->will($this->returnValue(["foo" => "bar", "bar" => "foo"]));
        $user->expects($this->once())->method("deleteAttribute")->with("foo")->will($this->returnValue(null));
        $user->expects($this->once())->method("getRoles")->will($this->returnValue(["foo", "bar"]));
        $user->expects($this->once())->method("hasRole")->with("foo")->will($this->returnValue(true));

        $user = new AclUser($user);
        
        $this->assertSame("Foo", $user->getName());
        $this->assertInstanceOf(UserInterface::class, $user->addAttribute("foo", "bar"));
        $this->assertSame("bar", $user->getAttribute("foo"));
        $this->assertSame(["foo" => "bar", "bar" => "foo"], $user->getAttributes());
        $this->assertNull($user->deleteAttribute("foo"));
        $this->assertSame(["foo", "bar"], $user->getRoles());
        $this->assertTrue($user->hasRole("foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::getPermission()
     */
    public function testGetPermission(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        
        $user = new AclUser($user);
        
        $this->assertSame(0, $user->getPermission());
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::setPermission()
     */
    public function testSetPermission(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        
        $user = new AclUser($user);
        
        $this->assertNull($user->setPermission(2));
        $this->assertSame(2, $user->getPermission());
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::grant()
     */
    public function testGrant(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        
        $user = new AclUser($user);
        
        $this->assertSame($user, $user->grant("foo"));
        $this->assertSame($user, $user->grant(["foo", "bar"]));
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::deny()
     */
    public function testDeny(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        
        $user = new AclUser($user);
        
        $this->assertSame($user, $user->deny("foo"));
        $this->assertSame($user, $user->deny(["foo", "bar"]));
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::on()
     */
    public function testOn(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(3))
            ->method("getAttribute")
            ->withConsecutive([AclUserInterface::ACL_ATTRIBUTE_IDENTIFIER])
            ->will($this->onConsecutiveCalls(
                ["Foo" => 0],
                ["Foo" => 0],
                ["<Foo>" => 0]));
        $user = new AclUser($user);
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(3))->method("getName")->will($this->returnValue("Foo"));
        $resource->expects($this->exactly(2))->method("grant")->withConsecutive([ ["foo", "bar"] ], ["bar"]);
        $resource->expects($this->exactly(2))->method("deny")->withConsecutive(["foo"], [ ["bar", "foo"] ]);
        $resource->expects($this->exactly(2))->method("to")->with($user);
        
        $this->assertNull($user->grant(["foo", "bar"])->deny("foo")->on($resource));
        $this->assertNull($user->grant("bar")->deny(["bar", "foo"])->on($resource));
        $this->assertNull($user->grant("bar")->on($resource));
        $this->assertNull($user->on($resource));
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::lock()
     */
    public function testLock(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(3))
            ->method("getAttribute")
            ->withConsecutive([AclUserInterface::ACL_ATTRIBUTE_IDENTIFIER])
            ->will($this->onConsecutiveCalls(
                null,
                ["FooResource" => 5, "BarResource" => 6],
                ["BarResource" => 5]
            ));
        $user
            ->expects($this->exactly(3))
            ->method("addAttribute")
            ->withConsecutive(
                [AclUserInterface::ACL_ATTRIBUTE_IDENTIFIER, ["<FooResource>" => 10]],
                [AclUserInterface::ACL_ATTRIBUTE_IDENTIFIER, ["BarResource" => 6, "<FooResource>" => 10]],
                [AclUserInterface::ACL_ATTRIBUTE_IDENTIFIER, ["<BarResource>" => 10]]
            );
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource
            ->expects($this->exactly(3))
            ->method("getName")
            ->will($this->onConsecutiveCalls("FooResource", "FooResource", "BarResource"));
        
        $user = new AclUser($user);
        $user->setPermission(10);
        $user->lock($resource);
        $user->lock($resource);
        $user->lock($resource);
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::isLocked()
     */
    public function testIsLocked(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(3))
            ->method("getAttribute")
            ->withConsecutive([AclUserInterface::ACL_ATTRIBUTE_IDENTIFIER])
            ->will($this->onConsecutiveCalls(
                ["<FooResource>" => 4],
                ["FooResource" => 5],
                null
            ));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getName")->will($this->returnValue("FooResource"));
        
        $user = new AclUser($user);
        
        $this->assertTrue($user->isLocked($resource));
        $this->assertFalse($user->isLocked($resource));
        $this->assertFalse($user->isLocked($resource));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::grant()
     */
    public function testExceptionGrantWhenPermissionIsNeitherAnArrayOrAString(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Permissions MUST be an array or a string. 'stdClass' given");
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        
        $user = new AclUser($user);
        
        $user->grant(new \stdClass());
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::deny()
     */
    public function testExceptionDenyWhenPermissionIsNeitherAnArrayOrAString(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Permissions MUST be an array or a string. 'stdClass' given");
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        
        $user = new AclUser($user);
        
        $user->deny(new \stdClass());
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::on()
     */
    public function testExceptionOnWhenAPermissionIsNotSettedIntoTheResource(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'foo' cannot be attributed to user 'FooUser' as it is not defined into resource 'FooResource'");
        
        $exception = new PermissionNotFoundException();
        $exception->setPermission("foo");
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->once())->method("getName")->will($this->returnValue("FooUser"));
        $user = new AclUser($user);
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("grant")->with("foo")->will($this->throwException($exception));
        $resource->expects($this->once())->method("getName")->will($this->returnValue("FooResource"));
        
        $user->grant("foo")->on($resource);
    }
    
}
