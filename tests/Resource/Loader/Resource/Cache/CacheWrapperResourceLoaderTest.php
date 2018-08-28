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
use Psr\SimpleCache\CacheInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Resource\Loader\Resource\Cache\CacheWrapperResourceLoader;
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface;

/**
 * CacheWrapperResourceLoader testcase
 * 
 * @see \Ness\Component\Acl\Resource\Loader\Resource\Cache\CacheWrapperResourceLoader
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheWrapperResourceLoaderTest extends AclTestCase
{
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        if(!\interface_exists("Psr\SimpleCache\CacheInterface"))
            self::markTestSkipped("PSR-16 Not found. Tests skipped");
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Resource\Cache\CacheWrapperResourceLoader::load()
     */
    public function testLoad(): void
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->exactly(2))->method("get")->withConsecutive([CacheWrapperResourceLoader::CACHE_KEY."_FooResource"])->will($this->onConsecutiveCalls($resource, null));
        $cache->expects($this->once())->method("set")->with(CacheWrapperResourceLoader::CACHE_KEY."_FooResource", $resource);
        
        $loader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with("FooResource")->will($this->returnValue($resource));
        
        $wrapper = new CacheWrapperResourceLoader($loader, $cache);
        
        $this->assertSame($resource, $wrapper->load("FooResource"));
        $this->assertSame($resource, $wrapper->load("FooResource"));
    }
    
}
