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

/**
 * Grant root permission on root user.
 * Permission are locked
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RootUserResourceProcessor extends AbstractResourceProcessor
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface::process()
     */
    public function process(ResourceInterface $resource, EntryLoaderInterface $loader): void
    {
        $base = $this->getUser()->getUser();
        if($base instanceof AuthenticatedUserInterface && $base->isRoot()) {
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

}
