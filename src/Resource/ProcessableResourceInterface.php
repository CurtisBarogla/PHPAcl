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

namespace Zoe\Component\Acl\Resource;

use Zoe\Component\Acl\User\AclUserInterface;
use Zoe\Component\Acl\Exception\InvalidResourceProcessorException;
use Zoe\Component\Acl\Resource\Processor\ResourceProcessorInterface;

/**
 * Make the resource interactive with a set of resource processors
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ProcessableResourceInterface
{
    
    /**
     * Check if the resource should be processed given user informations
     * 
     * @param AclUserInterface $user
     *   User processed over the resource
     * 
     * @return bool
     *   True if the user must be processed over the resource. False otherwise
     */
    public function shouldBeProcessed(AclUserInterface $user): bool;
    
    /**
     * Process the resource values over a set of registered resource processors over an acl user.
     * 
     * @param ResourceProcessorInterface[] $processors
     *   A set of ResourceProcessorInterface indexed by an identifier
     * @param AclUserInterface $user
     *   User currently processed
     *   
     * @throws InvalidResourceProcessorException
     *   When a processor setted for a value is not listed into given processors
     */
    public function process(array $processors, AclUserInterface $user): void;
    
}
