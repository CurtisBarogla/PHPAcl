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
use Ness\Component\Acl\Signal\Storage\ApcuResetSignalStore;

/**
 * ApcuResetSignalStore testcase
 * 
 * @see \Ness\Component\Acl\Signal\Storage\ApcuResetSignalStore
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ApcuResetSignalStoreTest extends AclTestCase
{
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        \apcu_clear_cache();
    }
    
    /**
     * @see \Ness\Component\Acl\Signal\Storage\ApcuResetSignalStore::has()
     */
    public function testHas(): void
    {
        $store = new ApcuResetSignalStore();
        
        $this->assertFalse($store->has("Foo"));
        \apcu_store("Foo", "Bar");
        $this->assertTrue($store->has("Foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\Signal\Storage\ApcuResetSignalStore::add()
     */
    public function testAdd(): void
    {
        $store = new ApcuResetSignalStore();
        
        $this->assertTrue($store->add("Foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\Signal\Storage\ApcuResetSignalStore::remove()
     */
    public function testRemove(): void
    {
        $store = new ApcuResetSignalStore();
        
        $this->assertFalse($store->remove("Foo"));
        \apcu_store("Foo", "Bar");
        $this->assertTrue($store->remove("Foo"));
    }
    
}
