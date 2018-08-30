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
use Ness\Component\Acl\Exception\EntryNotFoundException;
use Ness\Component\User\UserInterface;

/**
 * Grant or deny permissions (depending the resource behaviour) over an unique identifier
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
abstract class UniqueIdentifierResourceProcessor extends AbstractResourceProcessor
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface::process()
     */
    public function process(ResourceInterface $resource, EntryLoaderInterface $loader): void
    {
        if(null === $identifier = $this->getUniqueIdentifier($this->getUser()))
            return;
        
        try {
            $entry = $loader->load($resource, $identifier, $this->getIdentifier());
            switch ($resource->getBehaviour()) {
                case ResourceInterface::WHITELIST:
                    $resource->grant($entry->getPermissions())->to($this->getUser()); 
                    break;
                case ResourceInterface::BLACKLIST:
                    $resource->deny($entry->getPermissions())->to($this->getUser());
                    break;
            }
        } catch (EntryNotFoundException $e) {
            return;
        }  
    }
    
    /**
     * Return the identifier referring an entry (loadable/or not) from the user
     * 
     * @param UserInterface $user
     *   User processed
     * 
     * @return string|null
     *   Unique identifier. If this method returns null, the processor will be skipped
     */
    abstract protected function getUniqueIdentifier(UserInterface $user): ?string;
    
}
