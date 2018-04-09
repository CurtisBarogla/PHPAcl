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
use Zoe\Component\Internal\ReflectionTrait;
use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Exception\InvalidPermissionException;
use Zoe\Component\Acl\Exception\PermissionNotFoundException;
use ZoeTest\Component\Acl\Fixtures\Resource\ResourceFixture;

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

    use ReflectionTrait;
    
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
        $resourceBlacklist = new Resource("Foo", ResourceInterface::BLACKLIST);
        $resourceWhitelist = new Resource("Foo", ResourceInterface::WHITELIST);
        
        $this->assertSame(ResourceInterface::BLACKLIST, $resourceBlacklist->getBehaviour());
        $this->assertSame(ResourceInterface::WHITELIST, $resourceWhitelist->getBehaviour());
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::grant()
     */
    public function testGrant(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $reflection = new \ReflectionClass($resource);
        $resource->add("foo");
        $resource->add("bar");
        $resource->add("moz");
        
        $this->assertSame($resource, $resource->grant("foo")->grant(["bar", "moz"]));
        
        $this->assertSame(7, $this->reflection_getPropertyValue($resource, $reflection, "grant"));
        $this->assertSame(0, $this->reflection_getPropertyValue($resource, $reflection, "deny"));
        
        // test for all
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $reflection = new \ReflectionClass($resource);
        $resource->add("foo");
        $resource->add("bar");
        $resource->add("moz");
        
        $this->assertSame($resource, $resource->grant("all"));
        
        $this->assertSame(7, $this->reflection_getPropertyValue($resource, $reflection, "grant"));
        $this->assertSame(0, $this->reflection_getPropertyValue($resource, $reflection, "deny"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::deny()
     */
    public function testDeny(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $reflection = new \ReflectionClass($resource);
        $resource->add("foo");
        $resource->add("bar");
        $resource->add("moz");
        
        $this->assertSame($resource, $resource->deny("foo")->deny(["bar", "moz"]));
        
        $this->assertSame(0, $this->reflection_getPropertyValue($resource, $reflection, "grant"));
        $this->assertSame(7, $this->reflection_getPropertyValue($resource, $reflection, "deny"));
        
        // test for all
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $reflection = new \ReflectionClass($resource);
        $resource->add("foo");
        $resource->add("bar");
        $resource->add("moz");
        
        $this->assertSame($resource, $resource->deny("all"));
        
        $this->assertSame(0, $this->reflection_getPropertyValue($resource, $reflection, "grant"));
        $this->assertSame(7, $this->reflection_getPropertyValue($resource, $reflection, "deny"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::to()
     */
    public function testTo(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->once())->method("getPermission");
        $user->expects($this->once())->method("setPermission")->with(3)->will($this->returnValue(null));
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $reflection = new \ReflectionClass($resource);
        
        $resource->add("foo");
        $resource->add("bar");
        $resource->add("moz");
        
        $resource->grant(["foo", "bar"])->deny("moz");
        
        $this->assertSame(3, $this->reflection_getPropertyValue($resource, $reflection, "grant"));
        $this->assertSame(4, $this->reflection_getPropertyValue($resource, $reflection, "deny"));
        
        $this->assertNull($resource->grant(["foo", "bar"])->to($user));
        
        $this->assertSame(0, $this->reflection_getPropertyValue($resource, $reflection, "grant"));
        $this->assertSame(0, $this->reflection_getPropertyValue($resource, $reflection, "deny"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getPermission()
     */
    public function testGetPermission(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->add("foo");
        $resource->add("bar");
        
        $this->assertSame(1, $resource->getPermission("foo"));
        $this->assertSame(3, $resource->getPermission("all"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getPermissions()
     */
    public function testGetPermissions(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->add("foo");
        $resource->add("bar");
        $resource->add("moz");
        
        $this->assertSame(3, $resource->getPermissions(["foo", "bar"]));
        $this->assertSame(7, $resource->getPermissions(["all", "foo"]));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::add()
     */
    public function testAdd(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertSame($resource, $resource->add("foo")->add("bar"));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::grant()
     */
    public function testExceptionGrantInvalidPermission(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'foo' is not registered into resource 'Foo'");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->grant("foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::deny()
     */
    public function testExceptionDenyInvalidPermission(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'foo' is not registered into resource 'Foo'");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->deny("foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::add()
     */    
    public function testExceptionGrantInvalidTypePermission(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Permissions MUST be either an array or a string. boolean given");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->grant(true);
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::deny()
     */
    public function testExceptionDenyInvalidTypePermission(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Permissions MUST be either an array or a string. integer given");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->deny(0);
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getPermission()
     */
    public function testExceptionGetPermissionWhenGivenPermissionIsNotRegistered(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'bar' is not registered into resource 'Foo'");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->add("foo");
        
        $resource->getPermission("bar");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getPermissions()
     */
    public function testExceptionGetPermissionsWhenAPermissionIsNotRegistered(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'moz' is not registered into resource 'Foo'");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->add("foo");
        $resource->add("bar");
        
        $resource->getPermissions(["bar", "moz"]);
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::add()
     */
    public function testExceptionAddWhenInvalidPermissionIsAdded(): void
    {
        $this->expectException(InvalidPermissionException::class);
        $this->expectExceptionMessage("This permission 'Foo' does not respest pattern [a-z_]");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->add("Foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::add()
     */
    public function testExceptionAddWhenReservedPermissionIsGiven(): void
    {
        $permission = \array_rand(ResourceInterface::RESERVED_PERMISSIONS, 1);
        $permission = ResourceInterface::RESERVED_PERMISSIONS[$permission];
        
        $this->expectException(InvalidPermissionException::class);
        $this->expectExceptionMessage("This permission '{$permission}' cannot be added as its name is reserved");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->add($permission);
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::add()
     */
    public function testExceptionAddWhenAlreadyRegisteredPermissionIsGiven(): void
    {
        $this->expectException(InvalidPermissionException::class);
        $this->expectExceptionMessage("This permission 'foo' cannot be added as it is already registered into resource 'Foo'");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->add("foo")->add("foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::add()
     */
    public function testExceptionAddWhenMaxPermissionCountIsReached(): void
    {
        $max = ResourceInterface::MAX_PERMISSIONS;
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot add more permissions into resource 'Foo'. Max permission allowed setted to {$max}");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        for ($i = 'aa'; $i < 'zz'; $i++) {
            $resource->add($i);
        }
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::add()
     */
    public function testExceptionAddWhenAReservedPermissionIsNotInitialized(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot set this permission 'foo' bit value. Did you forget to initialize a reserved permission ?");
        
        $resource = new ResourceFixture("Foo", ResourceInterface::BLACKLIST);
        
        $resource->add("foo");
    }
    
}
