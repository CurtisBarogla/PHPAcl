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

namespace NessTest\Component\Acl\Resource\Loader\Entry;

use NessTest\Component\Acl\AclTestCase;
use Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface;
use Ness\Component\Acl\Resource\Loader\Entry\ResourceInheritanceEntryLoaderWrapper;
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Exception\EntryNotFoundException;
use Ness\Component\Acl\Resource\ExtendableResourceInterface;
use Ness\Component\Acl\Resource\EntryInterface;
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderAwareInterface;

/**
 * ResourceInheritanceEntryLoaderWrapper testcase
 * 
 * @see \Ness\Component\Acl\Resource\Loader\Entry\InheritanceEntryLoaderWrapper
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ResourceInheritanceEntryLoaderWrapperTest extends AclTestCase
{
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\ResourceInheritanceEntryLoaderWrapper::load()
     */
    public function testLoadWhenDirectlyLoaded(): void
    {
        $entry = $this->getMockBuilder(EntryInterface::class)->getMock();
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->never())->method("getName");
        
        $resourceLoader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $resourceLoader->expects($this->never())->method("load");
        
        $wrapped = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $wrapped
            ->expects($this->once())
            ->method("load")->with($resource, "FooEntry", "FooProcessor")
            ->will($this->onConsecutiveCalls($this->returnValue($entry)));
        
        $loader = new ResourceInheritanceEntryLoaderWrapper($wrapped);
        $loader->setLoader($resourceLoader);
        
        $this->assertSame($entry, $loader->load($resource, "FooEntry", "FooProcessor"));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\ResourceInheritanceEntryLoaderWrapper::load()
     */
    public function testLoadWhenLoadIntoParent(): void
    {
        $entry = $this->getMockBuilder(EntryInterface::class)->getMock();
        $entry->expects($this->once())->method("getIterator")->will($this->returnValue(new \ArrayIterator(["foo", "bar"])));
        
        $resource = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getParent")->will($this->returnValue("FooResource"));
        $resource->expects($this->never())->method("getName");
        
        $parentResource = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $parentResource->expects($this->never())->method("getParent");
        $parentResource->expects($this->never())->method("getName");
        
        $resourceLoader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $resourceLoader->expects($this->once())->method("load")->withConsecutive(["FooResource"])->will($this->onConsecutiveCalls($parentResource));
        
        $wrapped = $this->getMockBuilder([EntryLoaderInterface::class, ResourceLoaderAwareInterface::class])->getMock();
        $wrapped
            ->expects($this->exactly(2))
            ->method("load")->withConsecutive([$resource, "FooEntry", "FooProcessor"], [$parentResource, "FooEntry", "FooProcessor"])
            ->will($this->onConsecutiveCalls($this->throwException(new EntryNotFoundException("FooEntry")), $this->returnValue($entry)));
        
        $loader = new ResourceInheritanceEntryLoaderWrapper($wrapped);
        $loader->setLoader($resourceLoader);
        
        $entry = $loader->load($resource, "FooEntry", "FooProcessor");
        $this->assertSame("FooEntry", $entry->getName());
        $this->assertSame(["foo", "bar"], $entry->getPermissions());
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\ResourceInheritanceEntryLoaderWrapper::getLoader()
     */
    public function testGetLoader(): void
    {
        $wrapped = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $resourceLoader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        
        $loader = new ResourceInheritanceEntryLoaderWrapper($wrapped);
        $loader->setLoader($resourceLoader);
        
        $this->assertSame($resourceLoader, $loader->getLoader());
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\ResourceInheritanceEntryLoaderWrapper::setLoader()
     */
    public function testSetLoader(): void
    {
        $wrapped = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $resourceLoader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        
        $loader = new ResourceInheritanceEntryLoaderWrapper($wrapped);
        
        $this->assertNull($loader->setLoader($resourceLoader));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\ResourceInheritanceEntryLoaderWrapper::load()
     */
    public function testExceptionLoadWhenNotExtendableAndNotFound(): void
    {
        $this->expectException(EntryNotFoundException::class);
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $wrapped = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $wrapped->expects($this->once())->method("load")->with($resource, "FooEntry", null)->will($this->throwException(new EntryNotFoundException("FooEntry")));
        $resourceLoader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $resourceLoader->expects($this->never())->method("load");
        
        $loader = new ResourceInheritanceEntryLoaderWrapper($wrapped);
        $loader->setLoader($resourceLoader);
        
        $loader->load($resource, "FooEntry");
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\ResourceInheritanceEntryLoaderWrapper::load()
     */
    public function testExceptionLoadWhenParentsResourcesNotFoundEntry(): void
    {
        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage("This entry 'FooEntry' cannot be loaded into resource 'BarResource' nor into its parents 'FooResource, ParentFooResource'");
        
        $resource = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $resource->expects($this->any())->method("getParent")->will($this->returnValue("FooResource"));
        $resource->expects($this->any())->method("getName")->will($this->returnValue("BarResource"));
        
        $parentResource = $this->getMockBuilder(ExtendableResourceInterface::class)->getMock();
        $parentResource->expects($this->any())->method("getParent")->will($this->returnValue("ParentFooResource"));
        $parentResource->expects($this->any())->method("getName")->will($this->returnValue("FooResource"));
        
        $parentParentResource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $parentParentResource->expects($this->once())->method("getName")->will($this->returnValue("ParentFooResource"));
        
        $resourceLoader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $resourceLoader->expects($this->exactly(2))->method("load")->withConsecutive(["FooResource"], ["ParentFooResource"])->will($this->onConsecutiveCalls($parentResource, $parentParentResource));
        
        $exception = new EntryNotFoundException("FooEntry");
        
        $wrapped = $this->getMockBuilder([EntryLoaderInterface::class, ResourceLoaderAwareInterface::class])->getMock();
        $wrapped
            ->expects($this->exactly(3))
            ->method("load")->withConsecutive([$resource, "FooEntry", "FooProcessor"], [$parentResource, "FooEntry", "FooProcessor"], [$parentParentResource, "FooEntry", "FooProcessor"])
            ->will($this->onConsecutiveCalls($this->throwException($exception), $this->throwException($exception), $this->throwException($exception)));
        
        $loader = new ResourceInheritanceEntryLoaderWrapper($wrapped);
        $loader->setLoader($resourceLoader);
        
        $loader->load($resource, "FooEntry", "FooProcessor");
    }
       
}
