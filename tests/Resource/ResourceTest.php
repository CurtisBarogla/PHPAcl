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
use Zoe\Component\Internal\ReflectionTrait;
use Zoe\Component\Acl\Exception\InvalidPermissionException;
use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Exception\PermissionNotFoundException;
use Zoe\Component\Acl\Resource\ResourceInterface;

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
     * @see \Zoe\Component\Acl\Resource\Resource::allow()
     */
    public function testAllow(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST, ["perm_one", "perm_two", "perm_three"]);
        
        $this->assertSame($resource, $resource->allow(["perm_two"])->allow(["perm_three"]));
        $property = $this->reflection_getPropertyValue($resource, new \ReflectionClass($resource), "allow");
        
        $this->assertSame(6, $property);
        
        $resource->allow([ResourceInterface::ALL]);
        
        $property = $this->reflection_getPropertyValue($resource, new \ReflectionClass($resource), "allow");
        
        $this->assertSame(7, $property);
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::deny()
     */
    public function testDeny(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST, ["perm_one", "perm_two", "perm_three"]);
        $resource->allow([ResourceInterface::ALL]);
        
        $this->assertSame($resource, $resource->deny(["perm_two"])->deny(["perm_three"]));
        $property = $this->reflection_getPropertyValue($resource, new \ReflectionClass($resource), "deny");
        
        $this->assertSame(6, $property);
        
        $resource->deny([ResourceInterface::ALL]);
        
        $property = $this->reflection_getPropertyValue($resource, new \ReflectionClass($resource), "deny");
        
        $this->assertSame(7, $property);
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::to()
     */
    public function testTo(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("setPermission")->withConsecutive([7], [5])->will($this->returnValue(null));
        $user->expects($this->exactly(2))->method("getPermission")->will($this->onConsecutiveCalls(0, 7));
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST, ["perm_one", "perm_two", "perm_three"]);
        
        $this->assertNull($resource->allow([ResourceInterface::ALL])->to($user));
        $this->assertNull($resource->allow([ResourceInterface::ALL])->deny(["perm_two"])->to($user));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getPermission()
     */
    public function testGetPermission(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST, ["perm_one"]);
        
        $this->assertSame(1, $resource->getPermission("perm_one"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::getPermissions()
     */
    public function testGetPermissions(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST, ["perm_one", "perm_two", "perm_three"]);
        
        $this->assertSame(3, $resource->getPermissions(["perm_one", "perm_two"]));
        $this->assertSame(7, $resource->getPermissions());
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
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::add()
     */
    public function testAdd(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $this->assertSame($resource, $resource->add("perm_one"));
        $this->assertSame($resource, $resource->add("perm_two"));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::allow()
     */
    public function testExceptionAllowWhenNotFoundPermissionIsGiven(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'perm_one' is not registered into resource 'Foo'");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->allow(["perm_one"]);
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::deny()
     */
    public function testExceptionDenyWhenNotFoundPermissionIsGiven(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'perm_one' is not registered into resource 'Foo'");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        $resource->deny(["perm_one"]);
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::add()
     */
    public function testExceptionAddWhenMaxPermissionAllowedIsReached(): void
    {
        $this->expectException(InvalidPermissionException::class);
        $this->expectExceptionMessage("Cannot add more permission on resource 'Foo'");
        
        $resource = new Resource("Foo",  ResourceInterface::BLACKLIST);
        
        for ($i = 'aa'; $i <= 'zz'; $i++) {
            $resource->add($i);
        }
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::add()
     */
    public function testExceptionAddInvalidPermission(): void
    {
        $this->expectException(InvalidPermissionException::class);
        $this->expectExceptionMessage("This permission 'perm1' does not respect allowed pattern [a-z_]");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST, ["perm1"]);
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::add()
     */
    public function testExceptionAddAlreadyRegisteredPermission(): void
    {
        $this->expectException(InvalidPermissionException::class);
        $this->expectExceptionMessage("This permission 'perm' is already registered into resource 'Foo'");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST, ["perm"]);
        $resource->add("perm");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource::add()
     */
    public function testExceptionAddReservedPermission(): void
    {
        $this->expectException(InvalidPermissionException::class);
        $this->expectExceptionMessage("Cannot add this permission 'all' on resource 'Foo'. It is reserved");
        
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        foreach ($resource::PERMISSIONS_RESERVED as $permission) {
            $resource->add($permission);
        }
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Resource()
     */
    public function testExceptionMessage(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        
        try {
            $resource->allow(["perm_one"]);
        } catch (PermissionNotFoundException $e) {
            $this->assertSame("perm_one", $e->getNotFoundValue());
        }
    }
    
}
