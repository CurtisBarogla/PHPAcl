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
use Ness\Component\Acl\Resource\EntryInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Resource\Loader\Entry\FallbackProcessorEntryLoaderWrapper;
use Ness\Component\Acl\Exception\EntryNotFoundException;

/**
 * FallbackProcessorEntryLoaderWrapper testcase
 * 
 * @see \Ness\Component\Acl\Resource\Loader\Entry\FallbackProcessorEntryLoaderWrapper
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class FallbackProcessorEntryLoaderWrapperTest extends AclTestCase
{
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\FallbackProcessorEntryLoaderWrapper::load()
     */
    public function testLoad(): void
    {
        $entryFound = $this->getMockBuilder(EntryInterface::class)->getMock();
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();

        $wrapped = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $wrapped->expects($this->once())->method("load")->with($resource, "FooEntry", null)->will($this->returnValue($entryFound));
        
        $loader = new FallbackProcessorEntryLoaderWrapper($wrapped);
        
        $this->assertSame($entryFound, $loader->load($resource, "FooEntry", null));
        
        $wrapped = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $wrapped->expects($this->once())->method("load")->with($resource, "FooEntry", "FooProcessor")->will($this->returnValue($entryFound));
        
        $loader = new FallbackProcessorEntryLoaderWrapper($wrapped);
        
        $this->assertSame($entryFound, $loader->load($resource, "FooEntry", "FooProcessor"));
        
        $wrapped = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $wrapped
            ->expects($this->exactly(2))
            ->method("load")
            ->withConsecutive([$resource, "FooEntry", "FooProcessor"], [$resource, "FooEntry", null])
            ->will($this->onConsecutiveCalls($this->throwException(new EntryNotFoundException("FooEntry")), $entryFound));
        
        $loader = new FallbackProcessorEntryLoaderWrapper($wrapped);
        
        $this->assertSame($entryFound, $loader->load($resource, "FooEntry", "FooProcessor"));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\FallbackProcessorEntryLoaderWrapper::load()
     */
    public function testExceptionLoadWhenEntryCannotBeLoadedAtAll(): void
    {
        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage("This entry 'FooEntry' cannot be loaded for resource 'FooResource' through processor 'FooProcessor' nor global (null) processor");
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getName")->will($this->returnValue("FooResource"));
        
        $wrapped = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $wrapped
            ->expects($this->exactly(2))
            ->method("load")
            ->withConsecutive([$resource, "FooEntry", "FooProcessor"], [$resource, "FooEntry", null])
            ->will($this->throwException(new EntryNotFoundException("FooEntry")));
        
        $loader = new FallbackProcessorEntryLoaderWrapper($wrapped);
        
        $loader->load($resource, "FooEntry", "FooProcessor");
    }
    
}
