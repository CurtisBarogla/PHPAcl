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
use Zoe\Component\Acl\Processor\UsernameAclProcessor;
use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Resource\EntityInterface;
use Zoe\Component\Acl\Resource\ResourceInterface;

/**
 * UsernameAclProcess testcase
 * 
 * @see \Zoe\Component\Acl\Processor\UsernameAclProcessor
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class UsernameAclProcessorTest extends TestCase
{
    
    /**
     * @see \Zoe\Component\Acl\Processor\UsernameAclProcessor::process()
     */
    public function testProcess(): void
    {
        // blacklist resource
        $resourceBlacklist = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resourceBlacklist->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::BLACKLIST));
        // whitelist resource
        $resourceWhitelist = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resourceWhitelist->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::WHITELIST));
        
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getName")->will($this->returnValue("Foo"));
        $user->expects($this->once())->method("grant")->with($resourceWhitelist, ["FooPermission", "BarPermission"])->will($this->returnValue(null));
        $user->expects($this->once())->method("deny")->with($resourceBlacklist, ["FooPermission", "BarPermission"])->will($this->returnValue(null));
        
        
        $entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $entity->expects($this->exactly(2))->method("has")->with("Foo")->will($this->returnValue(true));
        $entity->expects($this->exactly(2))->method("get")->with("Foo")->will($this->returnValue(["FooPermission", "BarPermission"]));
        
        $processor = new UsernameAclProcessor();
        $processor->setEntity($entity);
        
        $this->assertNull($processor->process($resourceWhitelist, $user));
        $this->assertNull($processor->process($resourceBlacklist, $user));
    }
    
    /**
     * @see \Zoe\Component\Acl\Processor\UsernameAclProcessor::process()
     */
    public function testProcessWhenUsernameIsNotRegistered(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        $entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $entity->expects($this->once())->method("has")->with("Foo")->will($this->returnValue(false));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->never())->method("getBehaviour");
        
        $processor = new UsernameAclProcessor();
        $processor->setEntity($entity);
        
        $this->assertNull($processor->process($resource, $user));
    }
    
    /**
     * @see \Zoe\Component\Acl\Processor\UsernameAclProcessor::getIdentifier()
     */
    public function testGetIdentifier(): void
    {
        $processor = new UsernameAclProcessor();
        
        $this->assertSame("UsernameProcessor", $processor->getIdentifier());
    }
    
}
