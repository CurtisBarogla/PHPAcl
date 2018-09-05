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
use Ness\Component\Acl\Resource\ExtendableResourceInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface;
use Ness\Component\Acl\Resource\ExtendableResource;

/**
 * ExtendableResource testcase
 * 
 * @see \Ness\Component\Acl\Resource\ExtendableResource
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ExtendableResourceTest extends AclTestCase
{

    /**
     * @see \Ness\Component\Acl\Resource\ExtendableResource::extendsFrom()
     */
    public function testExtendsFrom(): void
    {
        $parent = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $parent->expects($this->exactly(4))->method("getName")->will($this->returnValue("Foo"));
        $parent->expects($this->exactly(2))->method("getBehaviour")->will($this->returnValue(ResourceInterface::WHITELIST));
        $parent->expects($this->exactly(2))->method("getPermissions")->will($this->returnValue(["foo", "bar"]));
        
        foreach ([new ExtendableResource("Bar"), new ExtendableResource("Moz", $parent)] as $resource) {
            if($resource->getName() === "Bar") {
                $resource->addPermission("foo");
                $resource->extendsFrom($parent);
            }
            $resource->addPermission("poz");
            
            $this->assertSame(ResourceInterface::WHITELIST, $resource->getBehaviour());
            $this->assertSame(1, $resource->getPermission("foo"));
            $this->assertSame(2, $resource->getPermission("bar"));
            $this->assertSame(4, $resource->getPermission("poz"));
            
            $this->assertSame("Foo", $resource->getParent());
        }
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\ExtendableResource::getParent()
     */
    public function testGetParent(): void
    {
        $parent = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $parent->expects($this->exactly(2))->method("getName")->will($this->returnValue("Foo"));
        $parent->permissions = [];
        
        $resource = (new ExtendableResource("Bar"))->extendsFrom($parent);
        
        $this->assertSame("Foo", $resource->getParent());
        
        $resource = new ExtendableResource("Foo");
        
        $this->assertNull($resource->getParent());
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\ExtendableResource::serialize()
     */
    public function testSerialize(): void
    {
        $parent = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $parent->permissions = [];
        
        $resource = new ExtendableResource("Foo");
        $resource->extendsFrom($parent);
        
        $this->assertNotFalse(\serialize($resource));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\ExtendableResource::unserialize()
     */
    public function testUnserialize(): void
    {
        $parent = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $parent->name = "Foo";
        $parent->permissions = ["foo" => 1, "bar" => 2];
        
        $resource = new ExtendableResource("Bar");
        $resource->extendsFrom($parent);
        
        $serialize = \serialize($resource);
        
        $this->assertEquals($resource, \unserialize($serialize));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\ExtendableResource::generateParents()
     */
    public function testGenerateParents(): void
    {
        // No parent
        
        $base = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $base->expects($this->once())->method("getParent")->will($this->returnValue(null));
        
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->never())->method("load");
        
        $this->assertSame([null], \iterator_to_array(ExtendableResource::generateParents($base, $loader)));
        
        // No ExtendableResourceInterface as parent
        
        $base = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $base->expects($this->exactly(2))->method("getParent")->will($this->returnValue("Foo"));
        $parent = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $parent->expects($this->once())->method("getParent")->will($this->returnValue("Bar"));
        $parentParent = $this->getMockBuilder(ResourceInterface::class)->getMock();
        
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->exactly(2))->method("load")->withConsecutive(["Foo"], ["Bar"])->will($this->onConsecutiveCalls($parent, $parentParent));
        
        $this->assertSame([
            $parent,
            $parentParent
        ], \iterator_to_array(ExtendableResource::generateParents($base, $loader)));
        
        // Null as parent
        
        $base = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $base->expects($this->exactly(2))->method("getParent")->will($this->returnValue("Foo"));
        $parent = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $parent->expects($this->once())->method("getParent")->will($this->returnValue("Bar"));
        $parentParent = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $parent->expects($this->once())->method("getParent")->will($this->returnValue(null));
        
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->exactly(2))->method("load")->withConsecutive(["Foo"], ["Bar"])->will($this->onConsecutiveCalls($parent, $parentParent));
        
        $this->assertSame([
            $parent,
            $parentParent
        ], \iterator_to_array(ExtendableResource::generateParents($base, $loader)));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\ExtendableResource::buildFromBasicResource()
     */
    public function testBuildFromBasicResource(): void
    {
        $extendable = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        
        $this->assertSame($extendable, ExtendableResource::buildFromBasicResource($extendable));
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();;
        $resource->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::BLACKLIST));
        $resource->expects($this->once())->method("getPermissions")->will($this->returnValue(["foo", "bar"]));  
        
        $extendable = ExtendableResource::buildFromBasicResource($resource);
        
        $this->assertSame("Foo", $extendable->getName());
        $this->assertSame(ResourceInterface::BLACKLIST, $extendable->getBehaviour());
        $this->assertSame(1, $extendable->getPermission("foo"));
        $this->assertSame(2, $extendable->getPermission("bar"));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Acl\Resource\ExtendableResource::extendsFrom()
     */
    public function testExceptionExtendsFromWhenResourceNameAreSame(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Resource 'Foo' cannot have the same parent's one name");
        
        $parent = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $parent->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        
        $resource = new ExtendableResource("Foo");
        $resource->extendsFrom($parent);
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\ExtendableResource::extendsFrom()
     */
    public function testExceptionExtendsFromWhenResourceHasAlreadyAParent(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Resource 'Bar' cannot have more than one parent");
        
        $parent = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $parent->expects($this->exactly(2))->method("getName")->will($this->returnValue("Foo"));
        $parent->permissions = [];
        
        $resource = new ExtendableResource("Bar");
        $resource->extendsFrom($parent)->extendsFrom($parent);
    }
    
}
