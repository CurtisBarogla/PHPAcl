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

namespace Zoe\Component\Acl;

use PHPUnit\Framework\TestCase;
use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Internal\ReflectionTrait;

/**
 * AclInteraction testcase
 * 
 * @see \Zoe\Component\Acl\AclInteraction
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class AclInteractionTest extends TestCase
{
    
    use ReflectionTrait;
    
    /**
     * @see \Zoe\Component\Acl\AclInteraction::grant()
     */
    public function testGrant(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("allow")->with(["foo", "bar"])->will($this->returnValue($resource));
        $resource->expects($this->once())->method("to")->with($user);
        
        $interaction = $this->initializeAclInteraction($user, $resource);
        
        $this->assertNull($interaction->grant(["foo", "bar"]));
    }
    
    /**
     * @see \Zoe\Component\Acl\AclInteraction::deny()
     */
    public function testDeny(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("deny")->with(["foo", "bar"])->will($this->returnValue($resource));
        $resource->expects($this->once())->method("to")->with($user);
        
        $interaction = $this->initializeAclInteraction($user, $resource);
        
        $this->assertNull($interaction->deny(["foo", "bar"]));
    }
    
    /**
     * @see \Zoe\Component\Acl\AclInteraction::isAllowed()
     * @see \Zoe\Component\Acl\AclInteraction::grant()
     */
    public function testIsAllowedWithGrantThatShouldNotBeCalled(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->once())->method("getPermission")->will($this->returnValue(0b1111));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermissions")->with(["foo", "bar"])->will($this->returnValue(0b0110));
        $resource->expects($this->never())->method("allow");
        
        $interaction = $this->initializeAclInteraction($user, $resource);
        
        $reflection = new \ReflectionClass($interaction);
        
        $current = $interaction->isAllowed(["foo", "bar"]);
        $this->assertSame($interaction, $current);
        
        $properties = $this->extractPropertiesForTesting($reflection, $interaction);
        
        $this->assertSame(true, $properties["allowed"]);
        $this->assertSame(["foo", "bar"], $properties["current"]);
        
        $current->grant();
    }
    
    /**
     * @see \Zoe\Component\Acl\AclInteraction::isAllowed()
     * @see \Zoe\Component\Acl\AclInteraction::grant()
     */
    public function testIsAllowedWithGrantThatShouldBeCalled(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->once())->method("getPermission")->will($this->returnValue(0b0000));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermissions")->with(["foo", "bar"])->will($this->returnValue(0b0110));
        $resource->expects($this->once())->method("allow")->with(["foo", "bar"])->will($this->returnValue($resource));
        $resource->expects($this->once())->method("to")->with($user);
        
        $interaction = $this->initializeAclInteraction($user, $resource);
        
        $this->assertNull($interaction->isAllowed(["foo", "bar"])->grant());
    }
    
    /**
     * @see \Zoe\Component\Acl\AclInteraction::isAllowed()
     * @see \Zoe\Component\Acl\AclInteraction::deny()
     */
    public function testIsAllowedWithDenyThatShouldBeCalled(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->once())->method("getPermission")->will($this->returnValue(0b1111));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermissions")->with(["foo", "bar"])->will($this->returnValue(0b0110));
        $resource->expects($this->once())->method("deny")->with(["foo", "bar"])->will($this->returnValue($resource));
        $resource->expects($this->once())->method("to")->with($user);
        
        $interaction = $this->initializeAclInteraction($user, $resource);
        
        $this->assertNull($interaction->isAllowed(["foo", "bar"])->deny());
    }
    
    /**
     * @see \Zoe\Component\Acl\AclInteraction::isAllowed()
     * @see \Zoe\Component\Acl\AclInteraction::deny()
     */
    public function testIsAllowedWithDenyThatShouldNotBeCalled(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->once())->method("getPermission")->will($this->returnValue(0b1001));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermissions")->with(["foo", "bar"])->will($this->returnValue(0b0110));
        $resource->expects($this->never())->method("deny");
        
        $interaction = $this->initializeAclInteraction($user, $resource);
        
        $this->assertNull($interaction->isAllowed(["foo", "bar"])->deny());
    }
    
    /**
     * @see \Zoe\Component\Acl\AclInteraction::isNotAllowed()
     * @see \Zoe\Component\Acl\AclInteraction::grant()
     */
    public function testIsNotAllowedWithDenyThatShouldNotBeCalled(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->once())->method("getPermission")->will($this->returnValue(0b0000));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermissions")->with(["foo", "bar"])->will($this->returnValue(0b0110));
        $resource->expects($this->never())->method("deny");
        
        $interaction = $this->initializeAclInteraction($user, $resource);
        
        $reflection = new \ReflectionClass($interaction);
        
        $current = $interaction->isNotAllowed(["foo", "bar"]);
        $this->assertSame($interaction, $current);
        
        $properties = $this->extractPropertiesForTesting($reflection, $interaction);
        
        $this->assertSame(false, $properties["allowed"]);
        $this->assertSame(["foo", "bar"], $properties["current"]);
        
        $current->deny();
    }
    
    /**
     * @see \Zoe\Component\Acl\AclInteraction::isAllowed()
     * @see \Zoe\Component\Acl\AclInteraction::isNotAllowed()
     */
    public function testGetPermissionIsCalledInsteadOfGetPermissions(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        $user->expects($this->exactly(2))->method("getPermission")->will($this->returnValue(0b0000));
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getPermission")->with("foo")->will($this->returnValue(0b0110));
        $resource->expects($this->never())->method("getPermissions");

        $interaction = $this->initializeAclInteraction($user, $resource);
        
        $interaction->isAllowed(["foo"]);
        $interaction->isNotAllowed(["foo"]);
    }
    
    /**
     * @see \Zoe\Component\Acl\AclInteraction::getUser()
     */
    public function testGetUser(): void
    {
        $user = $this->getMockBuilder(AclUserInterface::class)->getMock();
        
        $interaction = new AclInteraction();
        $interaction->setUser($user);
        
        $this->assertSame($user, $interaction->getUser());
    }
    
    /**
     * Initialize a new AclInteraction for testing purpose with user and resource setted
     * 
     * @param AclUserInterface $user
     *   User to set
     * @param ResourceInterface $resource
     *   Resource to set
     * 
     * @return AclInteraction
     *   AclInteraction initialized
     */
    private function initializeAclInteraction(AclUserInterface $user, ResourceInterface $resource): AclInteraction
    {
        $interaction = new AclInteraction();
        $interaction->setUser($user);
        $interaction->setResource($resource);
        
        return $interaction;
    }
    
    /**
     * Extract private properties from interaction
     * 
     * @param \ReflectionClass $reflection
     *   Reflection instance with object interaction setted
     * @param AclInteractionInterface $interaction
     *   Interaction object
     * 
     * @return array
     *   Array indexed with properties setted. Access with property name (allowed & current)
     */
    private function extractPropertiesForTesting(\ReflectionClass $reflection, AclInteractionInterface $interaction): array
    {
        return [
            "allowed"   =>  $this->reflection_getPropertyValue($interaction, $reflection, "allowed"),
            "current"   =>  $this->reflection_getPropertyValue($interaction, $reflection, "current")
        ];
    }
    
}
