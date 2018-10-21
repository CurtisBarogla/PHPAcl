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
 * Responsible to apply a pattern on a resource name representing a lock on a resource
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface LockPatternNormalizerInterface
{
    
    /**
     * Apply a lock pattern on the given resource
     * 
     * @param string $resource
     *   Resource to apply the pattern
     * 
     * @return string
     *   Resource name with the pattern applied
     */
    public function apply(string $resource): string;
    
}
