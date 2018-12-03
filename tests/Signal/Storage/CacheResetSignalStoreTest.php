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

namespace NessTest\Component\Acl\Signal\Storage;

use Psr\SimpleCache\CacheInterface;
use Ness\Component\Acl\Signal\Storage\CacheResetSignalStore;
use Ness\Component\Acl\Signal\Storage\ResetSignalStoreInterface;
use NessTest\Component\Acl\AclTestCase;

/**
 * CacheResetSignalStore testcase
 * 
 * @see \Ness\Component\Acl\Signal\Storage\CacheResetSignalStore
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheResetSignalStoreTest extends AclTestCase
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
     * @see \Ness\Component\Acl\Signal\Storage\CacheResetSignalStore::has()
     */
    public function testHas(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->exactly(2))->method("has")->with("Foo")->will($this->onConsecutiveCalls(true, false));
        
        $store = new CacheResetSignalStore($cache);
        
        $this->assertTrue($store->has("Foo"));
        $this->assertFalse($store->has("Foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\Signal\Storage\CacheResetSignalStore::add()
     */
    public function testAdd(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->exactly(2))->method("set")->with("Foo", ResetSignalStoreInterface::RESET_VALUE, null)->will($this->onConsecutiveCalls(true, false));
        
        $store = new CacheResetSignalStore($cache);
        
        $this->assertTrue($store->add("Foo"));
        $this->assertFalse($store->add("Foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\Signal\Storage\CacheResetSignalStore::remove()
     */
    public function testRemove(): void
    {
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->exactly(2))->method("delete")->with("Foo")->will($this->onConsecutiveCalls(true, false));
        
        $store = new CacheResetSignalStore($cache);
        
        $this->assertTrue($store->remove("Foo"));
        $this->assertFalse($store->remove("Foo"));
    }
    
}
