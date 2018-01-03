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
use Zoe\Component\Acl\Mask\MaskCollection;
use Zoe\Component\Acl\Mask\Mask;
use Zoe\Component\Internal\GeneratorTrait;

/**
 * MaskCollection testcase
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class MaskCollectionTest extends TestCase
{
    
    use GeneratorTrait;
    
    /**
     * @see \Zoe\Component\Acl\Mask\MaskCollection::getIterator()
     */
    public function testGetIterator(): void
    {
        $maskFoo = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $maskBar = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        
        $maskFoo->expects($this->exactly(2))->method("getIdentifier")->will($this->returnValue("Foo"));
        $maskBar->expects($this->exactly(2))->method("getIdentifier")->will($this->returnValue("Bar"));
        
        $collection = new MaskCollection("Foo");
        $collection->add($maskFoo);
        $collection->add($maskBar);
        
        $expected = $this->getGenerator(["Foo" => $maskFoo, "Bar" => $maskBar]);
        
        $this->assertTrue($this->assertGeneratorEquals($expected, $collection->getIterator()));
    }
    
    /**
     * @see \Zoe\Component\Acl\Mask\MaskCollection::getIdentifier()
     */
    public function testGetIdentifier(): void
    {
        $collection = new MaskCollection("Foo");
        
        $this->assertSame("Foo", $collection->getIdentifier());
    }
    
    /**
     * @see \Zoe\Component\Acl\Mask\MaskCollection::total()
     */
    public function testTotal(): void
    {
        $maskFoo = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $maskBar = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        
        $maskFoo->expects($this->once())->method("getIdentifier")->will($this->returnValue("Foo"));
        // called 2 times for testing naming on final mask generated
        $maskFoo->expects($this->exactly(2))->method("getValue")->will($this->returnValue(1));
        $maskBar->expects($this->once())->method("getIdentifier")->will($this->returnValue("Bar"));
        // called 2 times for testing naming on final mask generated
        $maskBar->expects($this->exactly(2))->method("getValue")->will($this->returnValue(2));
        
        $collection = new MaskCollection("Foo");
        $collection->add($maskFoo);
        $collection->add($maskBar);
        
        $total = $collection->total();
        
        $this->assertSame("TOTAL_COLLECTION_Foo", $total->getIdentifier());
        $this->assertSame(3, $total->getValue());
        
        $total = $collection->total("Foo");
        
        $this->assertSame("Foo", $total->getIdentifier());
    }
    
    /**
     * @see \Zoe\Component\Acl\Mask\MaskCollection::add()
     */
    public function testAdd(): void
    {
        $mask = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $mask->expects($this->once())->method("getIdentifier")->will($this->returnValue("Foo"));
        
        $collection = new MaskCollection("Foo");
        
        $this->assertNull($collection->add($mask));
        $this->assertSame($mask, $collection->get("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Mask\MaskCollection::get()
     */
    public function testGet(): void
    {
        $mask = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $mask->expects($this->once())->method("getIdentifier")->will($this->returnValue("Foo"));
        
        $collection = new MaskCollection("Foo");
        
        $collection->add($mask);
        $this->assertSame($mask, $collection->get("Foo"));
    }
    
    /**
     * @see \Zoe\Component\Acl\Mask\MaskCollection::count()
     */
    public function testCount(): void
    {
        $maskFoo = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $maskFoo->expects($this->once())->method("getIdentifier")->will($this->returnValue("Foo"));
        
        $maskBar = $this->getMockBuilder(Mask::class)->disableOriginalConstructor()->getMock();
        $maskBar->expects($this->once())->method("getIdentifier")->will($this->returnValue("Bar"));
        
        $collection = new MaskCollection("Foo");
        
        $collection->add($maskFoo);
        $collection->add($maskBar);
        
        $this->assertSame(2, \count($collection));
        
        $collection = new MaskCollection("Foo");
        
        $this->assertSame(0, \count($collection));
    }
    
    /**
     * @see \Zoe\Component\Acl\Mask\MaskCollection::jsonSerialize()
     */
    public function testJsonSerialize(): void
    {
        $maskFoo = new Mask("Foo", 1);
        $maskBar = new Mask("Bar", 2);
        
        $collection = new MaskCollection("Foo");
        $collection->add($maskFoo);
        $collection->add($maskBar);
        
        $this->assertNotFalse(\json_encode($collection));
    }
    
    /**
     * @see \Zoe\Component\Acl\Mask\MaskCollection::restore()
     */
    public function testRestore(): void
    {
        $maskFoo = new Mask("Foo", 1);
        $maskBar = new Mask("Bar", 2);
        
        $collection = new MaskCollection("Foo");
        $collection->add($maskFoo);
        $collection->add($maskBar);
        
        $json = \json_encode($collection);
        
        $this->assertEquals($collection, MaskCollection::restore($json));
        
        $json = \json_decode($json, true);
        
        $this->assertEquals($collection, MaskCollection::restore($json));
    }
    
}
