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
        $user
            ->expects($this
            ->exactly(4))
            ->method("getAttribute")
            ->withConsecutive([AclUser::ACL_ATTRIBUTE_IDENTIFIER])
            ->will($this->onConsecutiveCalls(null, ["FooResource" => 42], ["BarResource" => 24], ["<FooResource>" => 42]));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(5))->method("getName")->will($this->returnValue("FooResource"));
            
        $user = new AclUser($user);
        
        $this->assertNull($user->getPermission($resource));
        $this->assertSame(42, $user->getPermission($resource));
        $this->assertNull($user->getPermission($resource));
        $this->assertSame(42, $user->getPermission($resource));
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::setPermission()
     */
    public function testSetPermission(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(3))
            ->method("getAttribute")
            ->withConsecutive([AclUser::ACL_ATTRIBUTE_IDENTIFIER])
            ->will($this->onConsecutiveCalls(null, [], ["BarResource" => 42]));
        $user
            ->expects($this->exactly(3))
            ->method("addAttribute")
            ->withConsecutive(
                [AclUser::ACL_ATTRIBUTE_IDENTIFIER, ["FooResource" => 24]],
                [AclUser::ACL_ATTRIBUTE_IDENTIFIER, ["FooResource" => 24]],
                [AclUser::ACL_ATTRIBUTE_IDENTIFIER, ["BarResource" => 42, "FooResource" => 24]]);
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(3))->method("getName")->will($this->returnValue("FooResource"));
        
        $user = new AclUser($user);
        
        $this->assertNull($user->setPermission($resource, 24));
        $this->assertNull($user->setPermission($resource, 24));
        $this->assertNull($user->setPermission($resource, 24));
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
     * @see \Ness\Component\Acl\User\AclUser::grantRoot()
     */
    public function testGrantRoot(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        
        $user = new AclUser($user);
        
        $this->assertSame($user, $user->grantRoot());
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
        $user->expects($this->once())->method("getAttribute")->will($this->returnValue(null));
        
        $user = new AclUser($user);
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("grantRoot");
        $resource->expects($this->once())->method("deny")->with("foo");
        $resource->expects($this->once())->method("grant")->with("bar");
        $resource->expects($this->once())->method("to")->with($user);
        
        $this->assertNull($user->grantRoot()->deny("foo")->grant("bar")->on($resource));
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::on()
     */
    public function testOnWhenPermissionsQueueEmpty(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->never())->method("getAttribute");
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->never())->method("getName");
        
        $user = new AclUser($user);
        
        $this->assertNull($user->on($resource));
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::on()
     */
    public function testOnWhenLocked(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->once())->method("getAttribute")->with(AclUser::ACL_ATTRIBUTE_IDENTIFIER)->will($this->returnValue(["<FooResource>" => 42]));
            
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getName")->will($this->returnValue("FooResource"));
        $resource->expects($this->never())->method("grant");
        $resource->expects($this->never())->method("deny");
        $resource->expects($this->never())->method("grantRoot");

        $user = new AclUser($user);
        
        $this->assertNull($user->grant("foo")->on($resource));
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::lock()
     */
    public function testLockWhenLocked(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->once())->method("getAttribute")->with(AclUser::ACL_ATTRIBUTE_IDENTIFIER)->will($this->returnValue(["<FooResource>" => 42]));
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getName")->will($this->returnValue("FooResource"));
        
        $user = new AclUser($user);
        
        $this->assertNull($user->grant("foo")->lock($resource));
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::lock()
     */
    public function testLockWithNoPreviousResource(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(3))
            ->method("getAttribute")
            ->with(AclUser::ACL_ATTRIBUTE_IDENTIFIER)
            ->will($this->returnValue(null));
        $user->expects($this->once())->method("addAttribute")->with(AclUser::ACL_ATTRIBUTE_IDENTIFIER, ["<FooResource>" => 0]);
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getName")->will($this->returnValue("FooResource"));
        
        $user = new AclUser($user);
        
        $this->assertNull($user->lock($resource));
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::lock()
     */
    public function testLockWithPreviousResource(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(3))
            ->method("getAttribute")
            ->with(AclUser::ACL_ATTRIBUTE_IDENTIFIER)
            ->will($this->returnValue(["BarResource" => 20, "FooResource" => 42], ["BarResource" => 20, "FooResource" => 42], ["BarResource" => 20, "FooResource" => 42]));
        $user->expects($this->once())->method("addAttribute")->with(AclUser::ACL_ATTRIBUTE_IDENTIFIER, ["BarResource" => 20, "<FooResource>" => 42]);
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(4))->method("getName")->will($this->returnValue("FooResource"));
        
        $user = new AclUser($user);
        
        $this->assertNull($user->lock($resource));
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
            ->withConsecutive([AclUser::ACL_ATTRIBUTE_IDENTIFIER])
            ->will($this->onConsecutiveCalls(null, ["FooResource" => 42], ["<FooResource>" => 42]));
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getName")->will($this->returnValue("FooResource"));
        
        $user = new AclUser($user);
        
        $this->assertFalse($user->isLocked($resource));
        $this->assertFalse($user->isLocked($resource));
        $this->assertTrue($user->isLocked($resource));
    }
    
    /**
     * @see \Ness\Component\Acl\User\AclUser::isLocked()
     */
    public function testGetUser(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        
        $aclUser = new AclUser($user);
        
        $this->assertSame($user, $aclUser->getUser());
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
        $user->expects($this->once())->method("getAttribute")->with(AclUser::ACL_ATTRIBUTE_IDENTIFIER)->will($this->returnValue(null));
        $user->expects($this->once())->method("getName")->will($this->returnValue("FooUser"));
        $user = new AclUser($user);
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("grant")->with("foo")->will($this->throwException($exception));
        $resource->expects($this->exactly(1))->method("getName")->will($this->returnValue("FooResource"));
        
        $user->grant("foo")->on($resource);
    }
    
}
