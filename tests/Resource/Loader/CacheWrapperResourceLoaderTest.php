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
     */
    public function testLoadFromCache(): void
    {
        $cachedResource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->once())->method("get")->with(CacheWrapperResourceLoader::ACL_CACHE_WRAPPER_RESOURCE_KEY."_Foo")->will($this->returnValue($cachedResource));
        $cache->expects($this->never())->method("set");
        
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->never())->method("load");
        
        $wrapper = new CacheWrapperResourceLoader($loader, $cache);
        
        $this->assertSame($cachedResource, $wrapper->load("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\CacheWrapperResourceLoader::load()
     */
    public function testLoadNotFromCache(): void
    {
        $resourceLoaded = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $cacheKey = CacheWrapperResourceLoader::ACL_CACHE_WRAPPER_RESOURCE_KEY."_Foo";
        
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->once())->method("get")->with($cacheKey)->will($this->returnValue(null));
        $cache->expects($this->once())->method("set")->with($cacheKey, $resourceLoaded)->will($this->returnValue(true));
        
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with("Foo")->will($this->returnValue($resourceLoaded));
        
        $wrapper = new CacheWrapperResourceLoader($loader, $cache);
        
        $this->assertSame($resourceLoaded, $wrapper->load("Foo"));
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
