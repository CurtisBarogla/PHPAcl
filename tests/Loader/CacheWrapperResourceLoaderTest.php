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

namespace ZoeTest\Component\Acl\Loader;

use PHPUnit\Framework\TestCase;
use Zoe\Component\Acl\Loader\ResourceLoaderInterface;
use Zoe\Component\Acl\Loader\Cache\CacheFormatStrategyInterface;
use Psr\SimpleCache\CacheInterface;
use Zoe\Component\Acl\Loader\CacheWrapperResourceLoader;
use Zoe\Component\Acl\Resource\ResourceInterface;

/**
 * CacheWrapperResourceLoader testcase
 * 
 * @see \Zoe\Component\Acl\Loader\CacheWrapperResourceLoader
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheWrapperResourceLoaderTest extends TestCase
{
    
    /**
     * @see \Zoe\Component\Acl\Loader\CacheWrapperResourceLoader::load()
     */
    public function testLoadWhenCached(): void
    {
        $resourceFromCache = $this->getMockBuilder(ResourceInterface::class)->getMock();
        
        $wrapped = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $wrapped->expects($this->never())->method("load");
        
        $format = $this->getMockBuilder(CacheFormatStrategyInterface::class)->getMock();
        $format->expects($this->never())->method("processSetting");
        $format->expects($this->once())->method("processGetting")->with("FooNormalizedResource")->will($this->returnValue($resourceFromCache));
        
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->never())->method("set");
        $cache->expects($this->once())->method("get")->with(CacheWrapperResourceLoader::CACHE_LOADER_PREFIX."Foo")->will($this->returnValue("FooNormalizedResource"));
        
        $loader = new CacheWrapperResourceLoader($wrapped, $cache, $format);
        
        $this->assertSame($resourceFromCache, $loader->load("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Loader\CacheWrapperResourceLoader::load()
     */
    public function testLoadWhenNotCached(): void
    {
        $resourceFromWrappedLoaded = $this->getMockBuilder(ResourceInterface::class)->getMock();
        
        $wrapped = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $wrapped->expects($this->once())->method("load")->with("Foo")->will($this->returnValue($resourceFromWrappedLoaded));
        
        $format = $this->getMockBuilder(CacheFormatStrategyInterface::class)->getMock();
        $format->expects($this->never())->method("processGetting");
        $format->expects($this->once())->method("processSetting")->with($resourceFromWrappedLoaded)->will($this->returnValue("FooNormalizedResource"));
        
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->once())->method("get")->will($this->returnValue(null));
        $cache->expects($this->once())->method("set")->with(CacheWrapperResourceLoader::CACHE_LOADER_PREFIX."Foo", "FooNormalizedResource")->will($this->returnValue(true));
        
        $loader = new CacheWrapperResourceLoader($wrapped, $cache, $format);
        
        $this->assertSame($resourceFromWrappedLoaded, $loader->load("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Loader\CacheWrapperResourceLoader::getCache()
     */
    public function testGetCache(): void
    {
        $wrapped = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $format = $this->getMockBuilder(CacheFormatStrategyInterface::class)->getMock();
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();

        $loader = new CacheWrapperResourceLoader($wrapped, $cache, $format);
        
        $this->assertSame($cache, $loader->getCache());
    }
    
}
