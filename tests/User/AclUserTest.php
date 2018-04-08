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

namespace ZoeTest\Component\Acl\User;

use PHPUnit\Framework\TestCase;
use Zoe\Component\User\UserInterface;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
use Zoe\Component\Acl\User\AclUser;
use Zoe\Component\Acl\User\AclUserInterface;

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
        $this->assertSame("Foo", $this->initializeAclUser($this->once(), "getName", "Foo")->getName());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::isRoot()
     */
    public function testIsRoot(): void
    {
        $this->assertSame(true, $this->initializeAclUser($this->once(), "isRoot", true)->isRoot());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::addAttribute()
     */
    public function testAddAttribute(): void
    {
        $this->assertSame(null, $this->initializeAclUser($this->once(), "addAttribute", null, "Foo", "Bar")->addAttribute("Foo", "Bar"));
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::getAttributes()
     */
    public function testGetAttributes(): void
    {
        $return = ["Foo" => "Bar", "Bar" => "Foo"];
        
        $this->assertSame($return, $this->initializeAclUser($this->once(), "getAttributes", $return)->getAttributes());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::getAttribute()
     */
    public function testGetAttribute(): void
    {
        $this->assertSame("Bar", $this->initializeAclUser($this->once(), "getAttribute", "Bar", "Foo")->getAttribute("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::hasAttribute()
     */
    public function testHasAttribute(): void
    {
        $this->assertSame(true, $this->initializeAclUser($this->once(), "hasAttribute", true, "Foo")->hasAttribute("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::getRoles()
     */
    public function testGetRoles(): void
    {
        $return = ["Foo" => "Foo", "Bar" => "Bar"];
        
        $this->assertSame($return, $this->initializeAclUser($this->once(), "getRoles", $return)->getRoles());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::hasRole()
     */
    public function testHasRole(): void
    {
        $this->assertSame(true, $this->initializeAclUser($this->once(), "hasRole", true, "Foo")->hasRole("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::getPermission()
     */
    public function testGetPermission(): void
    {
        $user = new AclUser($this->getMockBuilder(UserInterface::class)->getMock());
        
        $this->assertSame(0, $user->getPermission());
    }
    
    /**
     * @see \Zoe\Component\Acl\User\AclUser::setPermission()
     */
    public function testSetPermission(): void
    {
        $user = new AclUser($this->getMockBuilder(UserInterface::class)->getMock());
        
        $this->assertNull($user->setPermission(5));
        
        $this->assertSame(5, $user->getPermission());
    }
    
    /**
     * Initialize an AclUserInterface with a mocked UserInterface wrapped
     * 
     * @param InvokedCount $count
     *   Call count
     * @param string $method
     *   Method to call
     * @param mixed $return
     *   Value returned
     * @param mixed ...$args
     *   Args passed to the method
     * 
     * @return AclUserInterface
     *   AclUser with a wrapped mocked user
     */
    private function initializeAclUser(InvokedCount $count, string $method, $return, ...$args): AclUserInterface
    {
        $mock = $this->getMockBuilder(UserInterface::class)->getMock();
        
        if(empty($args))
            $mock->expects($count)->method($method)->will($this->returnValue($return));
        else 
            $mock->expects($count)->method($method)->with(...$args)->will($this->returnValue($return));
            
        return new AclUser($mock);
    }
    
}
