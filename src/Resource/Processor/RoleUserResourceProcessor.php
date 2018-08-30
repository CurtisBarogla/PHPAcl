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

/**
 * Grant permissions over all roles setted into the user depending the resource behaviour
 * If resource behaviour is setted to whitelist, all permissions from loaded entry will be granted.
 * If resource behaviour is setted to blacklist, only the entry with the lowest permission value will be denied
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RoleUserResourceProcessor extends AbstractResourceProcessor
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface::process()
     */
    public function process(ResourceInterface $resource, EntryLoaderInterface $loader): void
    {
        if(null !== $roles = $this->getUser()->getRoles()) {
            $behaviour = $resource->getBehaviour();
            foreach ($roles as $role) {
                try {
                    switch ($behaviour) {
                        case ResourceInterface::WHITELIST:
                            $permissions = $loader->load($resource, $role, $this->getIdentifier())->getPermissions();
                            $resource->grant($permissions);
                            break;
                        case ResourceInterface::BLACKLIST:
                            $permissions = $loader->load($resource, $role, $this->getIdentifier());
                            $ref[$resource->getPermission($permissions->getPermissions())] = $permissions;
                            \ksort($ref);
                            break;
                    }
                } catch (EntryNotFoundException $e) {
                    // skip not registered roles
                    continue;
                }
            }

            if(isset($ref))
                $resource->deny(\current($ref)->getPermissions());
            
            $resource->to($this->getUser());
        }
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface::getIdentifier()
     */
    public function getIdentifier(): string
    {
        return "AclRoleUserProcessor";
    }
    
}
