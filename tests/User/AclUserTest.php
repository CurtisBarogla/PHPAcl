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

namespace ZoeTest\Component\Acl\User;

use PHPUnit\Framework\TestCase;
use Zoe\Component\User\UserInterface;
use Zoe\Component\Acl\Mask\Mask;
use Zoe\Component\Acl\User\AclUser;
use Zoe\Component\Internal\ReflectionTrait;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\Exception\PermissionNotFoundException;
use Zoe\Component\Acl\Resource\EntityInterface;
use Zoe\Component\Acl\Exception\EntityValueNotFoundException;
use Zoe\Component\Acl\Mask\MaskCollection;

/**
 * AclUser testcase
 * 
 * @see \Zoe\Component\Acl\User\AclUser
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class AclUserTest extends TestCase
{
    
    use ReflectionTrait;
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::getName()
     */
    public function testGetName(): void
    {
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $wrapped->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        
        $user = new AclUser($permission, $wrapped);
        
        $this->assertSame("Foo", $user->getName());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::isRoot()
     */
    public function testIsRoot(): void
    {
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $wrapped->expects($this->once())->method("isRoot")->will($this->returnValue(false));
        
        $user = new AclUser($permission, $wrapped);
        
        $this->assertFalse($user->isRoot());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::addAttribute()
     */
    public function testAddAttribute(): void
    {
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $wrapped->expects($this->once())->method("addAttribute")->with("Foo", "Bar")->will($this->returnValue(null));
        
        $user = new AclUser($permission, $wrapped);
        
        $this->assertNull($user->addAttribute("Foo", "Bar"));
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::getAttributes()
     */
    public function testGetAttributes(): void
    {
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $wrapped->expects($this->once())->method("getAttributes")->will($this->returnValue(["Foo" => "Bar", "Bar" => "Foo"]));
        
        $user = new AclUser($permission, $wrapped);

        $this->assertSame(["Foo" => "Bar", "Bar" => "Foo"], $user->getAttributes());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::getAttribute()
     */
    public function testGetAttribute(): void
    {
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $wrapped->expects($this->once())->method("getAttribute")->with("Foo")->will($this->returnValue("Bar"));
        
        $user = new AclUser($permission, $wrapped);
        
        $this->assertSame("Bar", $user->getAttribute("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::hasAttribute()
     */
    public function testHasAttribute(): void
    {
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $wrapped->expects($this->once())->method("hasAttribute")->with("Foo")->will($this->returnValue(true));
        
        $user = new AclUser($permission, $wrapped);
        
        $this->assertTrue($user->hasAttribute("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::getRoles()
     */
    public function testGetRoles(): void
    {
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $wrapped->expects($this->once())->method("getRoles")->will($this->returnValue(["Foo" => "Foo", "Bar" => "Bar"]));
        
        $user = new AclUser($permission, $wrapped);
        
        $this->assertSame(["Foo" => "Foo", "Bar" => "Bar"], $user->getRoles());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::hasRole()
     */
    public function testHasRole(): void
    {
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $wrapped->expects($this->once())->method("hasRole")->with("Foo")->will($this->returnValue(true));
        
        $user = new AclUser($permission, $wrapped);
        
        $this->assertTrue($user->hasRole("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::grant()
     */
    public function testGrant(): void
    {
        // mask permissions
        $maskFooPermission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $maskBarValueTotal = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $maskUserPermission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $maskUserPermission->expects($this->exactly(2))->method("add")->withConsecutive([$maskFooPermission], [$maskBarValueTotal])->will($this->returnValue(null));
        
        // collection
        $collection = $this->getMockBuilder(MaskCollection::class)->disableOriginalConstructor()->getMock();
        $collection->expects($this->once())->method("total")->will($this->returnValue($maskBarValueTotal));
        
        // entities
        $fooEntity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $fooEntity->expects($this->once())->method("get")->with("BarValue")->willThrowException(new EntityValueNotFoundException());
        $barEntity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $barEntity->expects($this->once())->method("get")->with("BarValue")->will($this->returnValue(["BarPermission", "MozPermission"]));
        
        // resource
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getPermission")->withConsecutive(["FooPermission"], ["BarValue"])->will($this->onConsecutiveCalls($maskFooPermission, $this->throwException(new PermissionNotFoundException())));
        $resource->expects($this->once())->method("getEntities")->will($this->returnValue(["FooEntity" => $fooEntity, "BarEntity" => $barEntity]));
        $resource->expects($this->once())->method("getPermissions")->with(["BarPermission", "MozPermission"])->will($this->returnValue($collection));
        
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        
        $user = new AclUser($maskUserPermission, $wrapped);
        
        $this->assertNull($user->grant($resource, ["FooPermission", "BarValue"]));
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::deny()
     */
    public function testDeny(): void
    {
        // mask permissions
        $maskFooPermission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $maskBarValueTotal = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $maskUserPermission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $maskUserPermission->expects($this->exactly(2))->method("sub")->withConsecutive([$maskFooPermission], [$maskBarValueTotal])->will($this->returnValue(null));
        
        // collection
        $collection = $this->getMockBuilder(MaskCollection::class)->disableOriginalConstructor()->getMock();
        $collection->expects($this->once())->method("total")->will($this->returnValue($maskBarValueTotal));
        
        // entities
        $fooEntity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $fooEntity->expects($this->once())->method("get")->with("BarValue")->willThrowException(new EntityValueNotFoundException());
        $barEntity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $barEntity->expects($this->once())->method("get")->with("BarValue")->will($this->returnValue(["BarPermission", "MozPermission"]));
        
        // resource
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getPermission")->withConsecutive(["FooPermission"], ["BarValue"])->will($this->onConsecutiveCalls($maskFooPermission, $this->throwException(new PermissionNotFoundException())));
        $resource->expects($this->once())->method("getEntities")->will($this->returnValue(["FooEntity" => $fooEntity, "BarEntity" => $barEntity]));
        $resource->expects($this->once())->method("getPermissions")->with(["BarPermission", "MozPermission"])->will($this->returnValue($collection));
        
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        
        $user = new AclUser($maskUserPermission, $wrapped);
        
        $this->assertNull($user->deny($resource, ["FooPermission", "BarValue"]));
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::getPermission()
     */
    public function testGetPermission(): void
    {
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        
        $user = new AclUser($permission, $wrapped);
        
        $this->assertSame($permission, $user->getPermission());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::__clone()
     */
    public function testClone(): void
    {
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        
        $user = new AclUser($permission, $wrapped);
        $property = $this->reflection_getPropertyValue($user, new \ReflectionClass($user), "permissions");
        
        $this->assertSame($permission, $property);
        
        $user = clone $user;
        $property = $this->reflection_getPropertyValue($user, new \ReflectionClass($user), "permissions");
        
        $this->assertNotSame($permission, $property);
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::grant()
     */
    public function testExceptionGrantWhenPermissionCannotBeResolvedIntoResourceAndEntity(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'FooValue' cannot be 'granted' as it is not defined as a raw permission nor an entity value into resource 'FooResource'");
        
        $entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $entity->expects($this->once())->method("get")->with("FooValue")->willThrowException(new EntityValueNotFoundException());
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermission")->with("FooValue")->willThrowException(new PermissionNotFoundException());
        $resource->expects($this->once())->method("getEntities")->will($this->returnValue(["FooEntity" => $entity]));
        $resource->expects($this->once())->method("getName")->will($this->returnValue("FooResource"));
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        
        $user = new AclUser($permission, $wrapped);
        $user->grant($resource, ["FooValue"]);
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::deny()
     */
    public function testExceptionDenyWhenPermissionCannotBeResolvedIntoResourceAndEntity(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'FooValue' cannot be 'denied' as it is not defined as a raw permission nor an entity value into resource 'FooResource'");
        
        $entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $entity->expects($this->once())->method("get")->with("FooValue")->willThrowException(new EntityValueNotFoundException());
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermission")->with("FooValue")->willThrowException(new PermissionNotFoundException());
        $resource->expects($this->once())->method("getEntities")->will($this->returnValue(["FooEntity" => $entity]));
        $resource->expects($this->once())->method("getName")->will($this->returnValue("FooResource"));
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        
        $user = new AclUser($permission, $wrapped);
        $user->deny($resource, ["FooValue"]);
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::grant()
     */
    public function testExceptionGrantWhenPermissionIsNotDefinedAndNoEntityRegistered(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'FooPermission' cannot be 'granted' as it is not defined as a raw permission into resource 'FooResource'");
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermission")->with("FooPermission")->willThrowException(new PermissionNotFoundException());
        $resource->expects($this->once())->method("getEntities")->will($this->returnValue(null));
        $resource->expects($this->once())->method("getName")->will($this->returnValue("FooResource"));
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        
        $user = new AclUser($permission, $wrapped);
        $user->grant($resource, ["FooPermission"]);
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::deny()
     */
    public function testExceptionDenyWhenPermissionIsNotDefinedAndNoEntityRegistered(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'FooPermission' cannot be 'denied' as it is not defined as a raw permission into resource 'FooResource'");
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermission")->with("FooPermission")->willThrowException(new PermissionNotFoundException());
        $resource->expects($this->once())->method("getEntities")->will($this->returnValue(null));
        $resource->expects($this->once())->method("getName")->will($this->returnValue("FooResource"));
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        
        $user = new AclUser($permission, $wrapped);
        $user->deny($resource, ["FooPermission"]);
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::grant()
     */
    public function testExceptionGrantWhenAnEntityValueContainsAnInvalidPermission(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'FooPermission' for value 'FooValue' into entity 'FooEntity' setted into 'FooResource' resource is not valid");
        
        $entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $entity->expects($this->once())->method("getName")->will($this->returnValue("FooEntity"));
        $entity->expects($this->once())->method("get")->with("FooValue")->will($this->returnValue(["FooPermission"]));
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getEntities")->will($this->returnValue(["FooEntity" => $entity]));
        $resource->expects($this->once())->method("getPermission")->with("FooValue")->willThrowException(new PermissionNotFoundException());
        $resource->expects($this->once())->method("getName")->will($this->returnValue("FooResource"));
        $exception = new PermissionNotFoundException();
        $exception->setInvalidPermission("FooPermission");
        $resource->expects($this->once())->method("getPermissions")->with(["FooPermission"])->willThrowException($exception);
        
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        
        $user = new AclUser($permission, $wrapped);
        $user->grant($resource, ["FooValue"]);
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::deny()
     */
    public function testExceptionDenyWhenAnEntityValueContainsAnInvalidPermission(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'FooPermission' for value 'FooValue' into entity 'FooEntity' setted into 'FooResource' resource is not valid");
        
        $entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $entity->expects($this->once())->method("getName")->will($this->returnValue("FooEntity"));
        $entity->expects($this->once())->method("get")->with("FooValue")->will($this->returnValue(["FooPermission"]));
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getEntities")->will($this->returnValue(["FooEntity" => $entity]));
        $resource->expects($this->once())->method("getPermission")->with("FooValue")->willThrowException(new PermissionNotFoundException());
        $resource->expects($this->once())->method("getName")->will($this->returnValue("FooResource"));
        $exception = new PermissionNotFoundException();
        $exception->setInvalidPermission("FooPermission");
        $resource->expects($this->once())->method("getPermissions")->with(["FooPermission"])->willThrowException($exception);
        
        $wrapped = $this->getMockBuilder(UserInterface::class)->getMock();
        $permission = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        
        $user = new AclUser($permission, $wrapped);
        $user->deny($resource, ["FooValue"]);
    }
   
    
}
