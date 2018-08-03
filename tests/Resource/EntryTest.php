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

namespace NessTest\Component\Acl\Resource;

use NessTest\Component\Acl\AclTestCase;
use Ness\Component\Acl\Resource\Entry;

/**
 * Entry testcase
 * 
 * @see \Ness\Component\Acl\Resource\Entry
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class EntryTest extends AclTestCase
{
    
    /**
     * @see \Ness\Component\Acl\Resource\Entry::getName()
     */
    public function testGetName(): void
    {
        $entry = new Entry("Foo");
        
        $this->assertSame("Foo", $entry->getName());
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Entry::getPermissions()
     */
    public function testGetPermissions(): void
    {
        $entry = new Entry("Foo");
        
        $this->assertSame([], $entry->getPermissions());
        
        $entry->addPermission("foo")->addPermission("bar");
        
        $this->assertSame(["foo", "bar"], $entry->getPermissions());
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Entry::addPermission()
     */
    public function testAddPermission(): void
    {
        $entry = new Entry("Foo");
        
        $this->assertSame($entry, $entry->addPermission("foo"));
    }
    
}
