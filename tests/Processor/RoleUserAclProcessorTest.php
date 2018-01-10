<?php
//StrictType
declare(strict_types = 1);

/*
 * Zoe
 * Acl component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */

namespace ZoeTest\Component\Acl\Processor;

use PHPUnit\Framework\TestCase;
use Zoe\Component\Acl\Processor\RoleUserAclProcessor;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Resource\EntityInterface;

/**
 * RoleUserAclProcessor testcase
 * 
 * @see \Zoe\Component\Acl\Processor\RoleUserAclProcessor
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RoleUserAclProcessorTest extends TestCase
{
    
    /**
     * @see \Zoe\Component\Acl\Processor\RoleUserAclProcessor::process()
     */
    public function testProcessWhenUserHasNoRoleDefined(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->once())->method("getRoles")->will($this->returnValue(null));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getBehaviour");
        
        $processor = new RoleUserAclProcessor();
        
        $this->assertNull($processor->process($resource, $user));
    }
    
    /**
     * @see \Zoe\Component\Acl\Processor\RoleUserAclProcessor::process()
     */
    public function testProcessOnWhitelistResource(): void
    {
        // no role founded into the entity
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getRoles")->will($this->returnValue(["Foo" => "Foo", "Bar" => "Bar"]));
        $user->expects($this->never())->method("grant");
        $entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $entity->expects($this->exactly(2))->method("has")->withConsecutive(["Foo"], ["Bar"])->will($this->onConsecutiveCalls(false, false));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::WHITELIST));
        
        $processor = new RoleUserAclProcessor();
        $processor->setEntity($entity);
        
        $this->assertNull($processor->process($resource, $user));
        
        // role founded
        $entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $entity->expects($this->exactly(2))->method("has")->withConsecutive(["Foo"], ["Bar"])->will($this->onConsecutiveCalls(true, false));
        $entity->expects($this->exactly(1))->method("get")->with("Foo")->will($this->returnValue(["FooPermission", "BarPermission"]));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::WHITELIST));
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getRoles")->will($this->returnValue(["Foo" => "Foo", "Bar" => "Bar"]));
        $user->expects($this->once())->method("grant")->with($resource, ["FooPermission", "BarPermission"]);
        
        $processor = new RoleUserAclProcessor();
        $processor->setEntity($entity);
        
        $this->assertNull($processor->process($resource, $user));
    }
    
    /**
     * @see \Zoe\Component\Acl\Processor\RoleUserAclProcessor::process()
     */
    public function testProcessOnBlacklistResource(): void
    {
        $entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $entity->expects($this->exactly(2))->method("has")->withConsecutive(["Foo"], ["Bar"])->will($this->onConsecutiveCalls(true, true));
        $entity->expects($this->exactly(2))->method("get")->withConsecutive(["Foo"], ["Bar"])->will($this->onConsecutiveCalls(["FooPermission", "BarPermission"], ["MozPermission", "PozPermission", "LozPermission"]));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::BLACKLIST));
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getRoles")->will($this->returnValue(["Foo" => "Foo", "Bar" => "Bar"]));
        $user->expects($this->once())->method("deny")->with($resource, ["FooPermission", "BarPermission"]);
        
        $processor = new RoleUserAclProcessor(false);
        $processor->setEntity($entity);
        
        $this->assertNull($processor->process($resource, $user));
    }
    
    /**
     * @see \Zoe\Component\Acl\Processor\RoleUserAclProcessor::process()
     */
    public function testProcessOnBlacklistResourceWhenARoleHasNoPermissionDenied(): void
    {
        $entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $entity->expects($this->once())->method("has")->with("Foo")->will($this->returnValue(true));
        $entity->expects($this->once())->method("get")->with("Foo")->will($this->returnValue([]));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::BLACKLIST));
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getRoles")->will($this->returnValue(["Foo" => "Foo", "Bar" => "Bar"]));
        $user->expects($this->never())->method("deny");
        
        $processor = new RoleUserAclProcessor(false);
        $processor->setEntity($entity);
        
        $this->assertNull($processor->process($resource, $user));
    }
    
    /**
     * @see \Zoe\Component\Acl\Processor\RoleUserAclProcessor::process()
     */
    public function testProcessOnBlacklistResourceWhenSkipped(): void
    {
        $entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $entity->expects($this->never())->method("has");
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::BLACKLIST));
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->once())->method("getRoles")->will($this->returnValue(["Foo" => "Foo", "Bar" => "Bar"]));
        $user->expects($this->never())->method("grant");
        
        $processor = new RoleUserAclProcessor();
        $processor->setEntity($entity);
        
        $this->assertNull($processor->process($resource, $user));
    }
    
    /**
     * @see \Zoe\Component\Acl\Processor\RoleUserAclProcessor
     */
    public function testGetIdentifier(): void
    {
        $processor = new RoleUserAclProcessor();
        
        $this->assertSame("RoleUserProcessor", $processor->getIdentifier());
    }
    
}
