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

namespace NessTest\Component\Acl\Normalizer;

use NessTest\Component\Acl\AclTestCase;
use Ness\Component\Acl\Normalizer\NativeLockPatternNormalizer;

/**
 * NativeLockPatternNormalizer tescase
 * 
 * @see \Ness\Component\Acl\Normalizer\NativeLockPatternNormalizer
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NativeLockPatternNormalizerTest extends AclTestCase
{
    
    /**
     * @see \Ness\Component\Acl\Normalizer\NativeLockPatternNormalizer::apply()
     */
    public function testApply(): void
    {
        $normalizer = new NativeLockPatternNormalizer();
        
        $this->assertSame("<FooResource>", $normalizer->apply("FooResource"));
    }
    
}
