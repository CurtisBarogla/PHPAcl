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

namespace Ness\Component\Acl\Normalizer;

/**
 * Simply apply a <{resource}> pattern on the resource name
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NativeLockPatternNormalizer implements LockPatternNormalizerInterface
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Normalizer\LockPatternNormalizerInterface::apply()
     */
    public function apply(string $resource): string
    {
        return "<{$resource}>";
    }
    
}
