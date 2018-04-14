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

namespace ZoeTest\Component\Acl\Resource\Loader;

use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Zoe\Component\Acl\Resource\Loader\CacheWrapperResourceLoader;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\Resource\Loader\ResourceLoaderInterface;
use Zoe\Component\Acl\Resource\ResourceCollectionInterface;

/**
 * CacheWrapperResourceLoader testcase
 * 
 * @see \Zoe\Component\Acl\Resource\Loader\CacheWrapperResourceLoader
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheWrapperResourceLoaderTest extends TestCase
{
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\CacheWrapperResourceLoader::load()
     * @see \Zoe\Component\Acl\Resource\Loader\CacheWrapperResourceLoader::loadCollection()
     */
    public function testLoadFromCache(): void
    {
        $cachedResource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $cachedResourceCollection = $this->getMockBuilder(ResourceCollectionInterface::class)->getMock();
        
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->exactly(2))->method("get")->withConsecutive(
                [CacheWrapperResourceLoader::ACL_CACHE_WRAPPER_RESOURCE_KEY."_Foo"],
                [CacheWrapperResourceLoader::ACL_CACHE_WRAPPER_RESOURCE_KEY."_FooCollection"])
            ->will($this->onConsecutiveCalls($cachedResource, $cachedResourceCollection));
        $cache->expects($this->never())->method("set");
        
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->never())->method("load");
        $loader->expects($this->never())->method("loadCollection");
        
        $wrapper = new CacheWrapperResourceLoader($loader, $cache);
        
        $this->assertSame($cachedResource, $wrapper->load("Foo"));
        $this->assertSame($cachedResourceCollection, $wrapper->loadCollection("FooCollection"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\CacheWrapperResourceLoader::load()
     * @see \Zoe\Component\Acl\Resource\Loader\CacheWrapperResourceLoader::loadCollection()
     */
    public function testLoadNotFromCache(): void
    {
        $resourceLoaded = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resourceCollectionLoaded = $this->getMockBuilder(ResourceCollectionInterface::class)->getMock();
        $cacheKey = [
            CacheWrapperResourceLoader::ACL_CACHE_WRAPPER_RESOURCE_KEY."_Foo",
            CacheWrapperResourceLoader::ACL_CACHE_WRAPPER_RESOURCE_KEY."_FooCollection"
        ];
        
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache
            ->expects($this->exactly(2))
            ->method("get")
            ->withConsecutive([$cacheKey[0]], [$cacheKey[1]])
            ->will($this->onConsecutiveCalls(null, null));
        $cache
            ->expects($this->exactly(2))
            ->method("set")
            ->withConsecutive(
                [$cacheKey[0], $resourceLoaded],
                [$cacheKey[1], $resourceCollectionLoaded])
            ->will($this->onConsecutiveCalls(true, true));
        
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with("Foo")->will($this->returnValue($resourceLoaded));
        $loader->expects($this->once())->method("loadCollection")->with("FooCollection")->will($this->returnValue($resourceCollectionLoaded));
        
        $wrapper = new CacheWrapperResourceLoader($loader, $cache);
        
        $this->assertSame($resourceLoaded, $wrapper->load("Foo"));
        $this->assertSame($resourceCollectionLoaded, $wrapper->loadCollection("FooCollection"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\CacheWrapperResourceLoader::invalidate()
     */
    public function testInvalidate(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->once())->method("delete")->with(CacheWrapperResourceLoader::ACL_CACHE_WRAPPER_RESOURCE_KEY."_Foo")->will($this->returnValue(true));
        
        $wrapper = new CacheWrapperResourceLoader($this->getMockBuilder(ResourceLoaderInterface::class)->getMock(), $cache);
        
        $this->assertTrue($wrapper->invalidate("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\CacheWrapperResourceLoader::getCache()
     */
    public function testGetCache(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        
        $wrapper = new CacheWrapperResourceLoader($this->getMockBuilder(ResourceLoaderInterface::class)->getMock(), $cache);
        
        $this->assertSame($cache, $wrapper->getCache());
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\CacheWrapperResourceLoader::__construct()
     */
    public function testExceptionConstructWhenTryingToWrappedACacheWrapperResourceLoader(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot wrap a CacheWrappedResourceLoader into an another one");
        
        $loader = $this->getMockBuilder(CacheWrapperResourceLoader::class)->disableOriginalConstructor()->getMock();
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        
        $wrapper = new CacheWrapperResourceLoader($loader, $cache);
    }
    
}
