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

use NessTest\Component\Acl\AclTestCase;
use Psr\Cache\CacheItemPoolInterface;
use Ness\Component\Acl\Signal\Storage\CacheItemPoolResetSignalStore;
use Psr\Cache\CacheItemInterface;
use Ness\Component\Acl\Signal\Storage\ResetSignalStoreInterface;

/**
 * CacheItemPoolResetSignalStore testcase
 * 
 * @see \Ness\Component\Acl\Signal\Storage\CacheItemPoolResetSignalStore
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItemPoolResetSignalStoreTest extends AclTestCase
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
    
    /**
     * @see \Ness\Component\Acl\Signal\Storage\CacheItemPoolResetSignalStore::has()
     */
    public function testHas(): void
    {
        $pool = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $pool->expects($this->exactly(2))->method("hasItem")->with("Foo")->will($this->onConsecutiveCalls(true, false));
        
        $store = new CacheItemPoolResetSignalStore($pool);
        
        $this->assertTrue($store->has("Foo"));
        $this->assertFalse($store->has("Foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\Signal\Storage\CacheItemPoolResetSignalStore::add()
     */
    public function testAdd(): void
    {
        $item = $this->getMockBuilder(CacheItemInterface::class)->getMock();
        $item->expects($this->once())->method("set")->with(ResetSignalStoreInterface::RESET_VALUE)->will($this->returnValue($item));
        
        $pool = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $pool->expects($this->once())->method("getItem")->will($this->returnValue($item));
        $pool->expects($this->once())->method("saveDeferred")->with($item)->will($this->returnValue(true));
        
        $store = new CacheItemPoolResetSignalStore($pool);
        
        $this->assertTrue($store->add("Foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\Signal\Storage\CacheItemPoolResetSignalStore::remove()
     */
    public function testRemove(): void
    {
        $pool = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $pool->expects($this->exactly(2))->method("deleteItem")->with("Foo")->will($this->onConsecutiveCalls(true, false));
        
        $store = new CacheItemPoolResetSignalStore($pool);
        
        $this->assertTrue($store->remove("Foo"));
        $this->assertFalse($store->remove("Foo"));
    }
    
}
