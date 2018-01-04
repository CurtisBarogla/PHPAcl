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

namespace ZoeTest\Component\Acl\Resource;

use PHPUnit\Framework\TestCase;
use Zoe\Component\Acl\Resource\EntityAwareTrait;
use Zoe\Component\Acl\Resource\EntityInterface;

/**
 * EntityAwareTrait testcase
 * 
 * @see \Zoe\Component\Acl\Resource\EntityAwareTrait
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class EntityAwareTraitTest extends TestCase
{
    
    /**
     * @see \Zoe\Component\Acl\Resource\EntityAwareTrait::getEntity()
     */
    public function testGetEntity(): void
    {
        $entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $trait = $this->getMockForTrait(EntityAwareTrait::class);
        $trait->setEntity($entity);
        
        $this->assertSame($entity, $trait->getEntity());
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\EntityAwareTrait::setEntity()
     */
    public function testSetEntity(): void
    {
        $entity = $this->getMockBuilder(EntityInterface::class)->getMock();
        $trait = $this->getMockForTrait(EntityAwareTrait::class);
        
        $this->assertNull($trait->setEntity($entity));
    }
    
}
