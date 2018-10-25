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
use Ness\Component\Acl\Resource\Loader\Entry\ChainEntryLoader;
use Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Exception\EntryNotFoundException;
use Ness\Component\Acl\Resource\EntryInterface;

/**
 * EntryLoaderCollection testcase
 * 
 * @see \Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderCollection
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ChainEntryLoaderTest extends AclTestCase
{
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\ChainEntryLoader::addLoader()
     */
    public function testAddLoader(): void
    {
        $loader = new ChainEntryLoader($this->getMockBuilder(EntryLoaderInterface::class)->getMock());
        
        $this->assertNull($loader->addLoader($this->getMockBuilder(EntryLoaderInterface::class)->getMock()));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\ChainEntryLoader::load()
     */
    public function testLoad(): void
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $entry = $this->getMockBuilder(EntryInterface::class)->getMock();
        
        $loaderFoo = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $loaderBar = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $loaderMoz = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        
        $loaderFoo->expects($this->once())->method("load")->with($resource, "FooEntry", null)->will($this->throwException(new EntryNotFoundException("FooEntry")));
        $loaderBar->expects($this->once())->method("load")->with($resource, "FooEntry", null)->will($this->returnValue($entry));
        $loaderMoz->expects($this->never())->method("load");
        
        $loader = new ChainEntryLoader($loaderFoo);
        $loader->addLoader($loaderBar);
        $loader->addLoader($loaderMoz);
        
        $this->assertSame($entry, $loader->load($resource, "FooEntry"));
    }
    
                    /**_____EXCEPTIONS____**/
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\ChainEntryLoader::load()
     */
    public function testExceptionLoadWhenNotEntryFound(): void
    {
        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage("This entry 'FooEntry' cannot be found for resource 'FooResource' via all registered loaders");

        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getName")->will($this->returnValue("FooResource"));
        
        $loaderFoo = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $loaderBar = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        
        $loaderFoo->expects($this->once())->method("load")->with($resource, "FooEntry", null)->will($this->throwException(new EntryNotFoundException("FooEntry")));
        $loaderBar->expects($this->once())->method("load")->with($resource, "FooEntry", null)->will($this->throwException(new EntryNotFoundException("FooEntry")));
        
        $loader = new ChainEntryLoader($loaderFoo);
        $loader->addLoader($loaderBar);
        
        $loader->load($resource, "FooEntry");
    }
    
}
