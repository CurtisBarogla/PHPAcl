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
use Ness\Component\Acl\User\AclUserInterface;
use Ness\Component\Acl\Exception\PermissionNotFoundException;
use Ness\Component\Acl\Exception\InvalidArgumentException;
use Ness\Component\Acl\Resource\ExtendableResourceInterface;
use Ness\Component\Acl\Resource\Loader\ResourceLoaderInterface;

/**
 * Resource testcase
 * 
 * @see \ness
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
    public function testTo(): void        
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addPermission("foo");
        $resource->addPermission("bar");
        
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getPermission")->will($this->onConsecutiveCalls(0, 3));
        $user->expects($this->exactly(2))->method("setPermission")->withConsecutive([3], [0]);
        $user->expects($this->exactly(3))->method("isLocked")->withConsecutive([$resource])->will($this->onConsecutiveCalls(false, false, true));
        
        $resource->grant(["foo", "bar"])->deny("foo")->grant("foo")->to($user);
        $resource->deny(["foo", "bar"])->grant("foo")->deny("foo")->to($user);
        $this->assertNull($resource->to($user));
        $resource->deny(["foo", "bar"])->grant("foo")->to($user);
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
     * @see \Ness\Component\Acl\Resource\Resource::toExtend()
     */
    public function testToExtend(): void
    {
        $parent = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $parent->expects($this->exactly(2))->method("getName")->will($this->returnValue("Foo"));
        $parent->permissions = ["foo" => 1, "bar" => 2, "moz" => 4];
        
        $resource = new Resource("Bar", ResourceInterface::BLACKLIST);
        $resource->addPermission("poz")->addPermission("foo");
        $this->assertSame($resource, $resource->toExtend($parent));
        
        $this->assertSame(1, $resource->getPermission("foo"));
        $this->assertSame(2, $resource->getPermission("bar"));
        $this->assertSame(4, $resource->getPermission("moz"));
        $this->assertSame(8, $resource->getPermission("poz"));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::getParent()
     */
    public function testGetParent(): void
    {
        $parent = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $parent->expects($this->exactly(2))->method("getName")->will($this->returnValue("Foo"));
        $parent->permissions = [];
        
        $resource = new Resource("Bar", ResourceInterface::BLACKLIST);
        
        $this->assertNull($resource->getParent());
        $resource->toExtend($parent);
        $this->assertSame("Foo", $resource->getParent());
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
        $resource->toExtend($parent);
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
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::generateParentTree()
     */
    public function testGenerateParentTree(): void
    {
        // No parent
        
        $base = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $base->expects($this->once())->method("getParent")->will($this->returnValue(null));
        
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->never())->method("load");
        
        $this->assertSame(null, Resource::generateParentTree($base, $loader));
        
        // No ExtendableResourceInterface as parent
        
        $base = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $base->expects($this->exactly(2))->method("getParent")->will($this->returnValue("Foo"));
        $parent = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $parent->expects($this->exactly(2))->method("getParent")->will($this->returnValue("Bar"));
        $parentParent = $this->getMockBuilder(ResourceInterface::class)->getMock();
        
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->exactly(2))->method("load")->withConsecutive(["Foo"], ["Bar"])->will($this->onConsecutiveCalls($parent, $parentParent));
        
        $this->assertSame([
            $parent,
            $parentParent
        ], Resource::generateParentTree($base, $loader));
        
        // Null as parent
        
        $base = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $base->expects($this->exactly(2))->method("getParent")->will($this->returnValue("Foo"));
        $parent = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $parent->expects($this->exactly(2))->method("getParent")->will($this->returnValue("Bar"));
        $parentParent = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $parent->expects($this->exactly(2))->method("getParent")->will($this->returnValue(null));
        
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->exactly(2))->method("load")->withConsecutive(["Foo"], ["Bar"])->will($this->onConsecutiveCalls($parent, $parentParent));
        
        $this->assertSame([
            $parent,
            $parentParent
        ], Resource::generateParentTree($base, $loader));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::__construct()
     */
    public function testException__constructWhenResourceBehaviousIsInvalid(): void
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
     * @see \Ness\Component\Acl\Resource\Resource::toExtend()
     */
    public function testExceptionToExtendWhenResourceNameAreSame(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Resource 'Foo' cannot have the same parent's one name");
        
        $parent = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $parent->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->toExtend($parent);
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Resource::toExtend()
     */
    public function testExceptionToExtendBypassExceptionWhenAlreadyRegisteredAndNotOther(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This permission name 'foo@' for resource 'Bar' is invalid. MUST contains only [a-z_] characters");
        
        $parent = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $parent->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        $parent->permissions = ["foo" => 1, "bar" => 2, "foo@" => 4];
        
        $resource = new Resource("Bar", ResourceInterface::BLACKLIST);
        $resource->addPermission("foo")->addPermission("moz");
        $resource->toExtend($parent);
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
    public function testExceptionAddPermissionWhenNotValid(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This permission name 'foo@' for resource 'Foo' is invalid. MUST contains only [a-z_] characters");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->addPermission("foo@");
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
