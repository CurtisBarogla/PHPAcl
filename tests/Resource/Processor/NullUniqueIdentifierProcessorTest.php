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

namespace NessTest\Component\Acl\Resource\Processor;

use NessTest\Component\Acl\AclTestCase;
use Ness\Component\Acl\Resource\EntryInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface;
use NessTest\Component\Acl\Fixtures\Processor\NullUniqueIdentifierProcessor;
use Ness\Component\Acl\User\AclUser;

/**
 * Fixture Only
 * NullUniqueIdentifierProcessor testcase
 * 
 * @see \NessTest\Component\Acl\Fixtures\Processor\NullUniqueIdentifierProcessor
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NullUniqueIdentifierProcessorTest extends AclTestCase
{
    
    /**
     * @see \NessTest\Component\Acl\Fixtures\Processor\NullUniqueIdentifierProcessor::process()
     */
    public function testProcess(): void
    {
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();

        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->never())->method("getBehaviour")->will($this->returnValue(ResourceInterface::WHITELIST));
        
        $entry = $this->getMockBuilder(EntryInterface::class)->getMock();
        
        $loader = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $loader->expects($this->never())->method("load");
        
        $processor = new NullUniqueIdentifierProcessor();
        $processor->setUser($user);
        
        $this->assertNull($processor->process($resource, $loader));
    }
    
}
