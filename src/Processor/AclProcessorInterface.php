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

namespace Zoe\Component\Acl\Processor;

use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Resource\EntityAwareInterface;

/**
 * Process resource over an acl user
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AclProcessorInterface extends EntityAwareInterface
{
    
    /**
     * Process the user over the resource.
     * Responsible to build user permissions over values defined into the resource. <br />
     * At this state of the process, the processor is aware of the entity handled by this processor and is accessible via the interface 
     * 
     * @param ResourceInterface $resource
     *   Resource to process
     * @param AclUserInterface $user
     *   User to process
     */
    public function process(ResourceInterface $resource, AclUserInterface $user): void;
    
    /**
     * Get processor identifier.
     * Basically name which is setted into entity as processor
     * 
     * @return string
     *   Processor identifier
     */
    public function getIdentifier(): string;
    
}
