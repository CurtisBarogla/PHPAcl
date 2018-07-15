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

namespace Ness\Component\Acl\Resource;

/**
 * Provides inheritance to a Resource
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ExtendableResourceInterface extends ResourceInterface
{
    
    /**
     * Extends all properties from given resource to this resource.
     * 
     * @param ResourceInterface $resource
     *   Resource to extends
     *   
     * @return ExtendableResourceInterface
     *   Resource extended
     *   
     * @throws \LogicException
     *   When resource name are same
     */
    public function extendsFrom(ResourceInterface $resource): ExtendableResourceInterface;
    
    /**
     * Get parent resource name
     * 
     * @return string|null
     *   Parent resource name. Null if resource has no parent
     */
    public function getParent(): ?string;
    
}
