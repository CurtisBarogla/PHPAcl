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

namespace NessTest\Component\Acl\Fixtures\Processor;

use Ness\Component\Acl\Resource\Processor\UniqueIdentifierResourceProcessor;
use Ness\Component\User\UserInterface;

/**
 * Fixture only
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NullUniqueIdentifierProcessor extends UniqueIdentifierResourceProcessor
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface::getIdentifier()
     */
    public function getIdentifier(): string
    {
        return "FixtureNullUniqueProccesor";
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Processor\UniqueIdentifierResourceProcessor::getUniqueIdentifier()
     */
    protected function getUniqueIdentifier(UserInterface $user): ?string
    {
        return null;
    }

}
