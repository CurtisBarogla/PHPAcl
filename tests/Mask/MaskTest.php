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

namespace ZoeTest\Component\Acl\Mask;

use PHPUnit\Framework\TestCase;
use Zoe\Component\Acl\Mask\Mask;

/**
 * Mask testcase
 * 
 * @see \Zoe\Component\Acl\Mask\Mask
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class MaskTest extends TestCase
{
    
    /**
     * @see \Zoe\Component\Acl\Mask\Mask::getIdentifier()
     * @see \Zoe\Component\Acl\Mask\Mask::getValue()
     */
    public function testInitialize(): void
    {
        $mask = new Mask("Foo");
        $this->assertSame("Foo", $mask->getIdentifier());
        $this->assertSame(0, $mask->getValue());
        
        $mask = new Mask("Foo", 2);
        $this->assertSame(2, $mask->getValue());
    }
    
    /**
     * @see \Zoe\Component\Acl\Mask\Mask::add()
     */
    public function testAdd(): void
    {
        $add = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $add->expects($this->once())->method("getValue")->will($this->returnValue(2));
        
        $mask = new Mask("Foo", 1);
        $this->assertNull($mask->add($add));
        $this->assertSame(3, $mask->getValue());
    }
    
    /**
     * @see \Zoe\Component\Acl\Mask\Mask::sub)
     */
    public function testSub(): void
    {
        $sub = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $sub->expects($this->once())->method("getValue")->will($this->returnValue(2));
        
        $mask = new Mask("Foo", 3);
        $this->assertNull($mask->sub($sub));
        $this->assertSame(1, $mask->getValue());
    }
    
    /**
     * @see \Zoe\Component\Acl\Mask\Mask::lshift()
     */
    public function testLshift(): void
    {
        $mask = new Mask("Foo", 1);
        $this->assertNull($mask->lshift());
        $this->assertSame(2, $mask->getValue());
        
        $mask = new Mask("Foo", 1);
        $this->assertNull($mask->lshift(2));
        $this->assertSame(4, $mask->getValue());
    }
    
    /**
     * @see \Zoe\Component\Acl\Mask\Mask::rshift()
     */
    public function testRshift(): void
    {
        $mask = new Mask("Foo", 2);
        $this->assertNull($mask->rshift());
        $this->assertSame(1, $mask->getValue());
        
        $mask = new Mask("Foo", 4);
        $this->assertNull($mask->rshift(2));
        $this->assertSame(1, $mask->getValue());
    }
    
    /**
     * @see \Zoe\Component\Acl\Mask\Mask::jsonSerialize()
     */
    public function testJsonSerialize(): void
    {
        $mask = new Mask("Foo", 5);
        
        $this->assertNotFalse(\json_encode($mask));
    }
    
}
