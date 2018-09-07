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

namespace NessTest\Component\Acl\Fixtures\Entry\Loader;

use NessTest\Component\Acl\AclTestCase;
use Ness\Component\Acl\Resource\ExtendableResourceInterface;
use Ness\Component\Acl\Resource\ResourceInterface;

/**
 * Test asserting that an invalid entry loader supporting InheritanceEntryLoaderTrait throws an exception when loading a parent entry
 * 
 * @see \Ness\Component\Acl\Resource\Loader\Entry\Traits\InheritanceEntryLoaderTrait
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class InvalidInheritanceEntryLoaderImplementationTest extends AclTestCase
{
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\Traits\InheritanceEntryLoaderTrait::loadParentEntry()
     */
    public function testExceptionWhenLoaderImplementationIsInvalid(): void
    {
        $this->expectException(\LogicException::class);
        
        $loader = new UnsupportedInheritanceEntryLoader($this->getMockBuilder(ExtendableResourceInterface::class)->getMock());
        $loader->load($this->getMockBuilder(ResourceInterface::class)->getMock(), "FooEntry");
    }
    
}
