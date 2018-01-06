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

use Zoe\Component\Acl\User\AclUserInterface;

/**
 * Process over the username
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class UsernameAclProcessor extends UniqueIdAclProcessor
{
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Processor\AclProcessorInterface::getIdentifier()
     */
    public function getIdentifier(): string
    {
        return "UsernameProcessor";
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Acl\Processor\UniqueIdAclProcessor::getUniqueId()
     */
    protected function getUniqueId(AclUserInterface $user): string
    {
        return $user->getName();
    }
    
}
