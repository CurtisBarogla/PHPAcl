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

namespace ZoeTest\Component\Acl;

use PHPUnit\Framework\TestCase;
use Zoe\Component\Acl\Acl;
use Zoe\Component\Acl\AclBindableInterface;
use Zoe\Component\Acl\Loader\ResourceLoaderInterface;
use Zoe\Component\Acl\Mask\Mask;
use Zoe\Component\Acl\Mask\MaskCollection;
use Zoe\Component\Acl\Processor\AclProcessorInterface;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\User\UserInterface;
use Zoe\Component\User\Exception\InvalidUserAttributeException;

/**
 * Acl testcase
 * 
 * @see \Zoe\Component\Acl\Acl
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class AclTest extends TestCase
{
    
    /**
     * @see \Zoe\Component\Acl\Acl::getResource()
     */
    public function testGetResource(): void
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with("testGetResource")->will($this->returnValue($resource));
        
        $acl = new Acl($loader);
        
        $this->assertSame($resource, $acl->getResource("testGetResource"));

        // one call of load
        $acl->getResource("testGetResource");
    }
    
    /**
     * @see \Zoe\Component\Acl\Acl::registerProcessor()
     */
    public function testRegisterProcessor(): void
    {
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $processor = $this->getMockBuilder(AclProcessorInterface::class)->getMock();
        $processor->expects($this->once())->method("getIdentifier")->will($this->returnValue("Foo"));
        
        $acl = new Acl($loader);
        
        $this->assertNull($acl->registerProcessor($processor));
    }
    
    /**
     * @see \Zoe\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWhenUserAttributeIsSetted(): void
    {
        $maskUserPermission = $this->generateMask(11, 3);
        $fooPermission = $this->generateMask(2, 1);
        $collectionBarPoz = $this->generateCollection($this->generateMask(13, 2), 1);
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(3))->method("getAttribute")->will($this->returnValue($maskUserPermission));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermission")->with("FooPermission")->will($this->returnValue($fooPermission));
        $resource->expects($this->once())->method("getPermissions")->with(["BarPermission", "PozPermission"])->will($this->returnValue($collectionBarPoz));
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with("Foo_1")->will($this->returnValue($resource));
        
        $acl = new Acl($loader);
        
        $this->assertTrue($acl->isAllowed($user, "Foo_1", ["FooPermission"]));
        $this->assertFalse($acl->isAllowed($user, "Foo_1", ["BarPermission", "PozPermission"]));
        // lazy load
        $acl->isAllowed($user, "Foo_1", ["BarPermission", "PozPermission"]);
    }
    
    /**
     * @see \Zoe\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWithoutUserAttributeBlacklistResource(): void
    {
        $total = $this->generateCollection($this->generateMask(255, 1), 1);
        $fooPermission = $this->generateMask(8, 1);
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(1))->method("getAttribute")->with("_PERMISSION_Foo_2")->will($this->throwException(new InvalidUserAttributeException()));
        $user->expects($this->once())->method("addAttribute")->with("_PERMISSION_Foo_2", new Mask("_PERMISSION_Foo_2", 255));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::BLACKLIST));
        $resource->expects($this->once())->method("getPermission")->with("FooPermission")->will($this->returnValue($fooPermission));
        $resource->expects($this->once())->method("getPermissions")->with(null)->will($this->returnValue($total));
        $resource->expects($this->once())->method("isProcessed")->will($this->returnValue(false));
        $resource->expects($this->once())->method("process");
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with("Foo_2")->will($this->returnValue($resource));
        
        $acl = new Acl($loader);

        $this->assertTrue($acl->isAllowed($user, "Foo_2", ["FooPermission"]));
    }
    
    /**
     * @see \Zoe\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWithoutUserAttributeWhitelistResource(): void
    {
        $fooPermission = $this->generateMask(8, 1);
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(1))->method("getAttribute")->with("_PERMISSION_Foo_3")->will($this->throwException(new InvalidUserAttributeException()));
        $user->expects($this->once())->method("addAttribute")->with("_PERMISSION_Foo_3", new Mask("_PERMISSION_Foo_3", 0));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::WHITELIST));
        $resource->expects($this->once())->method("getPermission")->with("FooPermission")->will($this->returnValue($fooPermission));
        $resource->expects($this->once())->method("isProcessed")->will($this->returnValue(false));
        $resource->expects($this->once())->method("process");
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with("Foo_3")->will($this->returnValue($resource));
        
        $acl = new Acl($loader);
        
        $this->assertFalse($acl->isAllowed($user, "Foo_3", ["FooPermission"]));
    }
    
    /**
     * @see \Zoe\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWithBindReturnsNull(): void
    {
        $maskUserPermission = $this->generateMask(11, 1);
        $fooPermission = $this->generateMask(2, 1);
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(1))->method("getAttribute")->will($this->returnValue($maskUserPermission));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermission")->with("FooPermission")->will($this->returnValue($fooPermission));
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with("Foo_4")->will($this->returnValue($resource));
        $binded = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $binded->expects($this->once())->method("_getResourceName")->will($this->returnValue("Foo_4"));
        $binded->expects($this->once())->method("_onBind")->will($this->returnValue(null));
        
        $acl = new Acl($loader);
        $acl->bind($binded);
        
        $this->assertTrue($acl->isAllowed($user, "Foo_4", ["FooPermission"]));
    }
    
    /**
     * @see \Zoe\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWithBindReturnsCallableOrNull(): void
    {
        $maskUserPermission = $this->generateMask(11, 2);
        $fooPermission = $this->generateMask(2, 1);
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(1))->method("getAttribute")->will($this->returnValue($maskUserPermission));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermission")->with("FooPermission")->will($this->returnValue($fooPermission));
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with("Foo_5")->will($this->returnValue($resource));
        $binded = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $binded->expects($this->once())->method("_getResourceName")->will($this->returnValue("Foo_5"));
        $binded->expects($this->once())->method("_onBind")->will($this->returnValue([null, function(AclUserInterface $user, ResourceInterface $resource) {}]));
        
        $acl = new Acl($loader);
        $acl->bind($binded);
        
        $this->assertTrue($acl->isAllowed($user, "Foo_5", ["FooPermission"]));
    }
    
    /**
     * @see \Zoe\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWithCallback(): void
    {
        $maskUserPermission = $this->generateMask(11, 3);
        $fooPermission = $this->generateMask(2, 2);
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getAttribute")->will($this->returnValue($maskUserPermission));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getPermission")->with("FooPermission")->will($this->returnValue($fooPermission));
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with("Foo_6")->will($this->returnValue($resource));

        $acl = new Acl($loader);
        
        $this->assertTrue($acl->isAllowed($user, "Foo_6", ["FooPermission"], function(AclUserInterface $user, bool $granted) {
            return function(AclUserInterface $user, ResourceInterface $resource): void {};
        }));
        $this->assertTrue($acl->isAllowed($user, "Foo_6", ["FooPermission"], function(AclUserInterface $user, bool $granted) {
            return null;
        }));
    }
    
    /**
     * @see \Zoe\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWhenPermissionsEmpty(): void
    {
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->never())->method("load");
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        
        $acl = new Acl($loader);
        
        $this->assertTrue($acl->isAllowed($user, "Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Acl::bind()
     */
    public function testBind(): void
    {
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $bindable = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $bindable->expects($this->once())->method("_getResourceName")->will($this->returnValue("Foo"));
        
        $acl = new Acl($loader);
        
        $this->assertNull($acl->bind($bindable));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\Acl::isAllowed
     */
    public function testExceptionIsAllowedWhenAclUserIsGiven(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("User given cannot be an instance of AclUserInterface as the acl handle its creation");
        
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        
        $acl = new Acl($loader);
        
        $acl->isAllowed($user, "Foo");
    }
    
    /**
     * Generate a mocked mask permission.
     * Linked to a resource permission or a user attribute
     * 
     * @param int $value
     *   Mask value
     * @param int $calls
     *   Number of calls to getValue
     * 
     * @return Mask
     *   Mocked mask
     */
    private function generateMask(int $value, int $calls): Mask
    {
        $mask = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $mask->expects($this->exactly($calls))->method("getValue")->will($this->returnValue($value));
        
        return $mask;
    }
    
    /**
     * Generate a mask collection with a total setted
     * 
     * @param Mask $total
     *   Total to set
     * @param int $calls
     *   Number of calls to total
     * 
     * @return MaskCollection
     *   Mocked mask collection
     */
    private function generateCollection(Mask $total, int $calls): MaskCollection
    {
        $collection = $this->getMockBuilder(MaskCollection::class)->disableOriginalConstructor()->getMock();
        $collection->expects($this->exactly($calls))->method("total")->will($this->returnValue($total));
        
        return $collection;
    }
    
}
