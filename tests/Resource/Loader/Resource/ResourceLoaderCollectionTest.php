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

namespace NessTest\Component\Acl\Resource\Loader\Resource;

use NessTest\Component\Acl\AclTestCase;
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Exception\ResourceNotFoundException;
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderCollection;

/**
 * ResourceLoaderCollection testcase
 * 
 * @see \Ness\Component\Acl\Resource\Loader\ResourceLoaderCollection
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ResourceLoaderCollectionTest extends AclTestCase
{

    /**
     * @see \Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderCollection::addLoader()
     */
    public function testAddLoader(): void
    {
        $loader = new ResourceLoaderCollection($this->getMockBuilder(ResourceLoaderInterface::class)->getMock());
        
        $this->assertNull($loader->addLoader($this->getMockBuilder(ResourceLoaderInterface::class)->getMock()));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderCollection::load()
     */
    public function testLoad(): void
    {
        $resourceFound = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $defaultLoader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $found = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $skipped = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        
        $defaultLoader->expects($this->once())->method("load")->with("Foo")->will($this->throwException(new ResourceNotFoundException()));
        $found->expects($this->once())->method("load")->with("Foo")->will($this->returnValue($resourceFound));
        $skipped->expects($this->never())->method("load");
        
        $loader = new ResourceLoaderCollection($defaultLoader);
        $loader->addLoader($found);
        $loader->addLoader($skipped);
        
        $this->assertSame($resourceFound, $loader->load("Foo"));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderCollection::load()
     */
    public function testExceptionLoadWhenNoResourceHasBeenFound(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource 'Foo' has been not found into all resource loaders registered into this collection");
        
        $defaultLoader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $stillNotFound = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        
        $defaultLoader->expects($this->once())->method("load")->with("Foo")->will($this->throwException(new ResourceNotFoundException()));
        $stillNotFound->expects($this->once())->method("load")->with("Foo")->will($this->throwException(new ResourceNotFoundException()));
        
        $loader = new ResourceLoaderCollection($defaultLoader);
        $loader->addLoader($stillNotFound);
        
        $loader->load("Foo");
    }
    
}
