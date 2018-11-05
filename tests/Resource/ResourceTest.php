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

namespace NessTest\Component\Acl\Resource;

use NessTest\Component\Acl\AclTestCase;
use Ness\Component\Acl\Resource\Resource;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\User\AclUser;
use Ness\Component\Acl\Exception\PermissionNotFoundException;
use Ness\Component\Acl\Exception\InvalidArgumentException;

/**
 * Resource testcase
 * 
 * @see \Ness\Component\Acl\Resource\Resource
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ResourceTest extends AclTestCase
{
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::getName()
     */
    public function testGetName(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertSame("Foo", $resource->getName());
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::getBehaviour()
     */
    public function testGetBehaviour(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertSame(ResourceInterface::BLACKLIST, $resource->getBehaviour());
        
        $resource = new Resource("Foo", ResourceInterface::WHITELIST);
        
        $this->assertSame(ResourceInterface::WHITELIST, $resource->getBehaviour());
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::grant()
     */
    public function testGrant(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        
        $this->assertSame($resource, $resource->grant("foo"));
        $this->assertSame($resource, $resource->grant(["foo", "bar"]));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::grantRoot()
     */
    public function testGrantRoot(): void
    {
        $resource = new Resource("Foo");
        
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        
        $this->assertSame($resource, $resource->grantRoot());
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::deny()
     */
    public function testDeny(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        
        $this->assertSame($resource, $resource->deny("foo"));
        $this->assertSame($resource, $resource->deny(["foo", "bar"]));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::to()
     */
    public function testToWhenNoActions(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        $resource->addPermission("moz");
        
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        $user->expects($this->never())->method("getPermission");
        
        $this->assertNull($resource->to($user));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::to()
     */
    public function testToWhenLocked(): void        
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        $resource->addPermission("moz");
        
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        $user->expects($this->once())->method("isLocked")->with($resource)->will($this->returnValue(true));
        $user->expects($this->never())->method("getPermission");
        
        $this->assertNull($resource->grant("foo")->to($user));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::to()
     */
    public function testToWhenNoPermissionSetted(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        $resource->addPermission("moz");
        
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        $user->expects($this->once())->method("isLocked")->with($resource)->will($this->returnValue(false));
        $user->expects($this->once())->method("getPermission")->with($resource)->will($this->returnValue(null));
        $user->expects($this->once())->method("setPermission")->with($resource, 3);
        
        $this->assertNull($resource->deny("moz")->to($user));
        
        $resource = new Resource("Foo");
        
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        $resource->addPermission("moz");
        
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        $user->expects($this->exactly(2))->method("isLocked")->with($resource)->will($this->returnValue(false));
        $user->expects($this->exactly(2))->method("getPermission")->with($resource)->will($this->returnValue(null));
        $user->expects($this->exactly(2))->method("setPermission")->withConsecutive([$resource, 3], [$resource, 7]);
        
        $this->assertNull($resource->grant("foo")->grant("bar")->to($user));
        $this->assertNull($resource->grantRoot()->to($user));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::to()
     */
    public function testToWhenAPermissionIsSetted(): void
    {
        $resource = new Resource("Foo");
        
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        $resource->addPermission("moz");
        
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        $user->expects($this->once())->method("isLocked")->with($resource)->will($this->returnValue(false));
        $user->expects($this->once())->method("getPermission")->with($resource)->will($this->returnValue(7));
        $user->expects($this->once())->method("setPermission")->withConsecutive([$resource, 3]);
        
        $this->assertNull($resource->deny("moz")->to($user));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::getPermissions()
     */
    public function testGetPermissions(): void
    {
        $resource = new Resource("Foo");
        
        $this->assertSame([], $resource->getPermissions());
        
        $resource->addPermission("foo")->addPermission("bar");
        
        $this->assertSame(["foo", "bar"], $resource->getPermissions());
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::getPermission()
     */
    public function testGetPermission(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        
        $this->assertSame(1, $resource->getPermission("foo"));
        $this->assertSame(2, $resource->getPermission("bar"));
        $this->assertSame(3, $resource->getPermission(["foo", "bar"]));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::serialize()
     */
    public function testSerialize(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resource->addPermission("foo");
        
        $this->assertNotFalse(\serialize($resource));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::unserialize()
     */
    public function testUnserialize(): void
    {
        $parent = new Resource("Foo", ResourceInterface::BLACKLIST);
        $parent->addPermission("foo")->addPermission("bar");
        
        $resource = new Resource("Bar", ResourceInterface::BLACKLIST);
        $resource->addPermission("moz")->addPermission("poz");
        
        $serialize = \serialize($resource);
        
        $this->assertEquals($resource, \unserialize($serialize));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::addPermission()
     */
    public function testAddPermission(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertSame($resource, $resource->addPermission("foo"));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::__construct()
     */
    public function testException__constructWhenResourceBehaviourIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Resource behaviour given for resource 'Foo' is invalid. Use one defined into the interface");
        
        $resource = new Resource("Foo", 3);
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::getPermission()
     */
    public function testExceptionGetPermissionWhenPermissionIsNotRegistered(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'foo' into resource 'Foo' is not setted");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->getPermission("foo");
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::getPermission()
     */
    public function testExceptionSetAndGetNotFoundedPermission(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        try {
            $resource->getPermission("foo");
        } catch (PermissionNotFoundException $e) {
            $this->assertSame("foo", $e->getPermission());
        }
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::getPermission()
     */
    public function testExceptionGetPermissionTypeError(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Permission MUST be a string or an array. 'NULL' given");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->getPermission(null);
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::addPermission()
     */
    public function testExceptionAddPermissionWhenPermissionIsAlreadyRegistered(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This permission 'foo' is already registered for resource 'Foo'");
                
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addPermission("foo");
        $resource->addPermission("foo");
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::addPermission()
     */
    public function testExceptionAddPermissionWhenMaxIsReached(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot add more permission for resource 'Foo'");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        for ($i = 'aa'; $i < 'zz'; $i++) {
            $resource->addPermission($i);
        }
    }
    
}
