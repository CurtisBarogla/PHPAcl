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
use Ness\Component\Acl\Resource\Processor\RoleUserResourceProcessor;
use Ness\Component\Acl\User\AclUser;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface;
use Ness\Component\Acl\Resource\EntryInterface;
use Ness\Component\Acl\Exception\EntryNotFoundException;
use PHPUnit\Framework\MockObject\Matcher\Invocation;

function generateEntry(array $permissions, AclTestCase $unit, Invocation $count): EntryInterface
{
    $entry = $unit->getMockBuilder(EntryInterface::class)->getMock();
    $entry->expects($count)->method("getPermissions")->will($unit->returnValue($permissions));
    
    return $entry;
}

/**
 * RoleUserResourceProcessor testcase
 * 
 * @see \Ness\Component\Acl\Resource\Processor\RoleUserResourceProcessor
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RoleUserResourceProcessorTest extends AclTestCase
{
    
    /**
     * @see \Ness\Component\Acl\Resource\Processor\RoleUserResourceProcessor::process()
     */
    public function testProcessResourceWhitelist(): void
    {
        $entryFoo = generateEntry(["foo", "bar"], $this, $this->once());
        $entryBar = generateEntry(["moz", "bar"], $this, $this->once());
        $entryMoz = generateEntry(["poz", "boz"], $this, $this->once());
        
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        $user->expects($this->once())->method("getRoles")->will($this->returnValue(["Foo", "Bar", "Moz", "Poz"]));
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::WHITELIST));
        $resource->expects($this->exactly(3))->method("grant")->withConsecutive(
            [ ["foo", "bar"] ],
            [ ["moz", "bar"] ],
            [ ["poz", "boz"] ]
        );
        $resource->expects($this->once())->method("to")->with($user);
        
        $loader = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $loader
            ->expects($this->exactly(4))
            ->method("load")
            ->withConsecutive(
                [$resource, "Foo", "AclRoleUserProcessor"], 
                [$resource, "Bar", "AclRoleUserProcessor"], 
                [$resource, "Moz", "AclRoleUserProcessor"], 
                [$resource, "Poz", "AclRoleUserProcessor"])
            ->will($this->onConsecutiveCalls($entryFoo, $entryBar, $this->throwException(new EntryNotFoundException("FooEntry")), $entryMoz));
        
        $processor = new RoleUserResourceProcessor();
        $processor->setUser($user);
        $this->assertNull($processor->process($resource, $loader));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Processor\RoleUserResourceProcessor::process()
     */
    public function testProcessorResourceBlacklist(): void
    {
        $entryFoo = generateEntry(["foo", "bar"], $this, $this->exactly(2));
        $entryBar = generateEntry(["moz", "bar"], $this, $this->once());
        $entryMoz = generateEntry(["poz", "boz"], $this, $this->once());
        
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        $user->expects($this->once())->method("getRoles")->will($this->returnValue(["Foo", "Bar", "Moz", "Poz"]));
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::BLACKLIST));
        $resource->expects($this->once())->method("deny")->with(["foo", "bar"]);
        $resource->expects($this->once())->method("to")->with($user);
        $resource
            ->expects($this->exactly(3))
            ->method("getPermission")
            ->withConsecutive(
                [ ["foo", "bar"] ],
                [ ["moz", "bar"] ],
                [ ["poz", "boz"] ]
            )
            ->will($this->onConsecutiveCalls(1, 3, 15));
        
        $loader = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $loader
            ->expects($this->exactly(4))
            ->method("load")
            ->withConsecutive(
                [$resource, "Foo", "AclRoleUserProcessor"],
                [$resource, "Bar", "AclRoleUserProcessor"],
                [$resource, "Moz", "AclRoleUserProcessor"],
                [$resource, "Poz", "AclRoleUserProcessor"])
            ->will($this->onConsecutiveCalls($entryFoo, $entryBar, $this->throwException(new EntryNotFoundException("FooEntry")), $entryMoz));
            
        $processor = new RoleUserResourceProcessor();
        $processor->setUser($user);
        $this->assertNull($processor->process($resource, $loader));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Processor\RoleUserResourceProcessor::process()
     */
    public function testProcessWhenUserHasNoRoles(): void
    {
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        $user->expects($this->once())->method("getRoles")->will($this->returnValue(null));
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->never())->method("getBehaviour");
        
        $loader = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $loader->expects($this->never())->method("load");
        
        $processor = new RoleUserResourceProcessor();
        $processor->setUser($user);
        $this->assertNull($processor->process($resource, $loader));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Processor\RoleUserResourceProcessor::process()
     */
    public function testGetIdentifier(): void
    {
        $processor = new RoleUserResourceProcessor();
        
        $this->assertSame("AclRoleUserProcessor", $processor->getIdentifier());
    }
    
}
    