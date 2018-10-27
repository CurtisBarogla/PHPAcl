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

use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface;
use Ness\Component\Authentication\User\AuthenticatedUserInterface;
use Ness\Component\User\UserInterface;

/**
 * Grant root permission on root user.
 * Permission are locked
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
abstract class AbstractRootUserResourceProcessor extends AbstractResourceProcessor
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface::process()
     */
    public function process(ResourceInterface $resource, EntryLoaderInterface $loader): void
    {
        if($this->getBaseUser() instanceof AuthenticatedUserInterface && $this->getBaseUser()->isRoot()) {
            $resource->grantRoot()->to($this->getUser());
            $this->getUser()->lock($resource);
        }
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface::getIdentifier()
     */
    public function getIdentifier(): string
    {
        return "AclRootUserProcessor";
    }
    
    /**
     * Get a reference to the base user handled by the acl component
     * 
     * @return UserInterface
     *   Base user
     */
    abstract protected function getBaseUser(): UserInterface;

}
