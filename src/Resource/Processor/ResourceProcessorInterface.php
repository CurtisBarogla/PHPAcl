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

namespace Zoe\Component\Acl\Resource\Processor;

use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\User\AclUserInterface;

/**
 * Process the values registered into resource over the user
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResourceProcessorInterface
{
    
    /**
     * Process the resource over the user
     * 
     * @param ResourceInterface $resource
     *   Resource to process
     * @param AclUserInterface $user
     *   User to process
     */
    public function process(ResourceInterface $resource, AclUserInterface $user): void;
    
    /**
     * Refer the identifier of the processor
     * 
     * @return string
     *   Processor identifier
     */
    public function getIdentifier(): string;
    
}
