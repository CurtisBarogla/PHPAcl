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
use Ness\Component\Acl\Resource\Processor\UsernameResourceProcessor;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\User\AclUserInterface;
use Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface;
use Ness\Component\Acl\Exception\EntryNotFoundException;
use Ness\Component\Acl\Resource\EntryInterface;

/**
 * UsernameResourceProcessor testcase
 * 
 * @see \Ness\Component\Acl\Resource\Processor\UsernameResourceProcessor
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class UsernameResourceProcessorTest extends AclTestCase
{
    
    /**
     * @see \Ness\Component\Acl\Resource\Processor\UsernameResourceProcessor::process()
     */
    public function testProcessWhenResourceWhitelist(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->once())->method("getName")->will($this->returnValue("FooUser"));
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::WHITELIST));
        $resource->expects($this->once())->method("grant")->with(["foo", "bar"])->will($this->returnValue($resource));
        $resource->expects($this->once())->method("to")->with($user);
        
        $entry = $this->getMockBuilder(EntryInterface::class)->getMock();
        $entry->expects($this->once())->method("getPermissions")->will($this->returnValue(["foo", "bar"]));
        
        $loader = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with($resource, "FooUser", "AclUsernameProcessor")->will($this->returnValue($entry));
        
        $processor = new UsernameResourceProcessor();
        $processor->setUser($user);
        
        $this->assertNull($processor->process($resource, $loader));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Processor\UsernameResourceProcessor::process()
     */
    public function testProcessWhenResourceBlacklist(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->once())->method("getName")->will($this->returnValue("FooUser"));
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::BLACKLIST));
        $resource->expects($this->once())->method("deny")->with(["foo", "bar"])->will($this->returnValue($resource));
        $resource->expects($this->once())->method("to")->with($user);
        
        $entry = $this->getMockBuilder(EntryInterface::class)->getMock();
        $entry->expects($this->once())->method("getPermissions")->will($this->returnValue(["foo", "bar"]));
        
        $loader = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with($resource, "FooUser", "AclUsernameProcessor")->will($this->returnValue($entry));
        
        $processor = new UsernameResourceProcessor();
        $processor->setUser($user);
        
        $this->assertNull($processor->process($resource, $loader));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Processor\UsernameResourceProcessor::process()
     */
    public function testProcessWhenIdentifierNotLoadable(): void
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->never())->method("getBehaviour");
        
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->once())->method("getName")->will($this->returnValue("FooUser"));
        
        $loader = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $loader->expects($this->once())->method("load")->with($resource, "FooUser", "AclUsernameProcessor")->will($this->throwException(new EntryNotFoundException("FooUser")));
        
        $processor = new UsernameResourceProcessor();
        $processor->setUser($user);
        
        $this->assertNull($processor->process($resource, $loader));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Processor\UsernameResourceProcessor::getIdentifier()
     */
    public function testGetIdentifier(): void
    {
        $processor = new UsernameResourceProcessor();
        
        $this->assertSame("AclUsernameProcessor", $processor->getIdentifier());
    }
    
}
