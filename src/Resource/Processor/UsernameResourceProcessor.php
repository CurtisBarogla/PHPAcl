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

namespace Ness\Component\Acl\Resource\Processor;

use Ness\Component\User\UserInterface;

/**
 * Grant or deny permissions (depending the resource behaviour) over the username
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class UsernameResourceProcessor extends UniqueIdentifierResourceProcessor
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface::getIdentifier()
     */
    public function getIdentifier(): string
    {
        return "AclUsernameProcessor";
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Processor\UniqueIdentifierResourceProcessor::getUniqueIdentifier()
     */
    protected function getUniqueIdentifier(UserInterface $user): ?string
    {
        return $user->getName();
    }

}
