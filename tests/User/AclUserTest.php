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

namespace Zoe\Component\Acl\User;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Zoe\Component\User\UserInterface;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;

/**
 * AclUser testcase
 * 
 * @see \Zoe\Component\Acl\User\AclUser
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class AclUserTest extends TestCase
{
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::getName()
     */
    public function testGetName(): void
    {
        $user = new AclUser($this->initMockedUserInterface($this->once(), "getName", "Foo"));
        
        $this->assertSame("Foo", $user->getName());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::isRoot()
     */
    public function testIsRoot(): void
    {
        $user = new AclUser($this->initMockedUserInterface($this->once(), "isRoot", false));
        
        $this->assertFalse($user->isRoot());
        
        $user = new AclUser($this->initMockedUserInterface($this->once(), "isRoot", true));
        
        $this->assertTrue($user->isRoot());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::addAttribute()
     */
    public function testAddAttribute(): void
    {
        $user = new AclUser($this->initMockedUserInterface($this->once(), "addAttribute", null, "Foo", "Bar"));
        
        $this->assertNull($user->addAttribute("Foo", "Bar"));
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::getAttribute()
     */
    public function testGetAttribute(): void
    {
        $user = new AclUser($this->initMockedUserInterface($this->once(), "getAttribute", "Bar", "Foo"));
        
        $this->assertSame("Bar", $user->getAttribute("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::getAttributes()
     */
    public function testGetAttributes(): void
    {
        $user = new AclUser($this->initMockedUserInterface($this->once(), "getAttributes", ["Foo" => "Bar", "Bar" => "Foo"]));
        
        $this->assertSame(["Foo" => "Bar", "Bar" => "Foo"], $user->getAttributes());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::hasAttribute()
     */
    public function testHasAttribute(): void
    {
        $user = new AclUser($this->initMockedUserInterface($this->once(), "hasAttribute", false, "Foo"));
        
        $this->assertFalse($user->hasAttribute("Foo"));
        
        $user = new AclUser($this->initMockedUserInterface($this->once(), "hasAttribute", true, "Foo"));
        
        $this->assertTrue($user->hasAttribute("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::getRoles()
     */
    public function testGetRoles(): void
    {
        $user = new AclUser($this->initMockedUserInterface($this->once(), "getRoles", ["Foo" => "Foo", "Bar" => "Bar"]));
        
        $this->assertSame(["Foo" => "Foo", "Bar" => "Bar"], $user->getRoles());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::hasRole()
     */
    public function testHasRole(): void
    {
        $user = new AclUser($this->initMockedUserInterface($this->once(), "hasRole", false, "Foo"));
        
        $this->assertFalse($user->hasRole("Foo"));
        
        $user = new AclUser($this->initMockedUserInterface($this->once(), "hasRole", true, "Foo"));
        
        $this->assertTrue($user->hasRole("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::getPermission()
     */
    public function testGetPermission(): void
    {
        $user = new AclUser($this->getMockBuilder(UserInterface::class)->getMock());
        $user->setPermission(10);
        
        $this->assertSame(10, $user->getPermission());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::setPermission()
     */
    public function testSetPermission(): void
    {
        $user = new AclUser($this->getMockBuilder(UserInterface::class)->getMock());
        $this->assertNull($user->setPermission(10));
    }
        
    /**
     * Initialize a mocked user wrapped into an acl one
     * 
     * @param InvokedCount $count
     *   Count method calls
     * @param string $method
     *   Method to mock
     * @param mixed $return
     *   Return value
     * @param mixed ...$args
     *   Args passed to the method
     * 
     * @return MockObject
     *   Mocked user
     */
    private function initMockedUserInterface(InvokedCount $count, string $method, $return, ...$args): MockObject
    {
        $mock = $this->getMockBuilder(UserInterface::class)->getMock();
        if(empty($args))
            $mock->expects($count)->method($method)->will($this->returnValue($return));
        else
            $mock->expects($count)->method($method)->with(...$args)->will($this->returnValue($return));
        
        return $mock;
    }
    
}
