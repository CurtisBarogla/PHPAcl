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

namespace NessTest\Component\Acl\Resource\Loader\Resource\Cache;

use NessTest\Component\Acl\AclTestCase;
use Psr\Cache\CacheItemInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Psr\Cache\CacheItemPoolInterface;
use Ness\Component\Acl\Resource\Loader\Resource\Cache\CacheItemPoolWrapperResourceLoader;
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface;
use Cache\TagInterop\TaggableCacheItemInterface;
use Cache\TagInterop\TaggableCacheItemPoolInterface;

/**
 * CacheItemPoolWrapperResourceLoader testcase
 * 
 * @see \Ness\Component\Acl\Resource\Loader\Resource\Cache\CacheItemPoolWrapperResourceLoader
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItemPoolWrapperResourceLoaderTest extends AclTestCase
{
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        if(!\interface_exists("Psr\Cache\CacheItemPoolInterface"))
            self::markTestSkipped("PSR-6 Not found. Tests skipped");
    }
    
    public function testLoad(): void
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
                
        $item = $this->getMockBuilder(CacheItemInterface::class)->getMock();
        $item->expects($this->once())->method("get")->will($this->returnValue($resource));
        $item->expects($this->exactly(2))->method("isHit")->will($this->onConsecutiveCalls(false, true));
        
        $pool = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $pool->expects($this->exactly(2))->method("getItem")->with(CacheItemPoolWrapperResourceLoader::CACHE_KEY."_FooResource")->will($this->returnValue($item));
        $pool->expects($this->once())->method("saveDeferred")->with($item);
        
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with("FooResource")->will($this->returnValue($resource));
        
        $wrapper = new CacheItemPoolWrapperResourceLoader($loader, $pool);
        
        $this->assertSame($resource, $wrapper->load("FooResource"));
        $this->assertSame($resource, $wrapper->load("FooResource"));
    }
    
    public function testlLoadWithPoolSupportingTags(): void
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        
        $item = $this->getMockBuilder(TaggableCacheItemInterface::class)->getMock();
        $item->expects($this->once())->method("get")->will($this->returnValue($resource));
        $item->expects($this->exactly(2))->method("isHit")->will($this->onConsecutiveCalls(false, true));
        $item->expects($this->once())->method("setTags")->with([CacheItemPoolWrapperResourceLoader::CACHE_TAG]);
        
        $pool = $this->getMockBuilder(TaggableCacheItemPoolInterface::class)->getMock();
        $pool->expects($this->exactly(2))->method("getItem")->with(CacheItemPoolWrapperResourceLoader::CACHE_KEY."_FooResource")->will($this->returnValue($item));
        $pool->expects($this->once())->method("saveDeferred")->with($item);
        
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with("FooResource")->will($this->returnValue($resource));
        
        $wrapper = new CacheItemPoolWrapperResourceLoader($loader, $pool);
        
        $this->assertSame($resource, $wrapper->load("FooResource"));
        $this->assertSame($resource, $wrapper->load("FooResource"));
    }
    
}
