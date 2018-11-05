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
use Ness\Component\Acl\User\AclUser;

/**
 * Process a user over the resource to modify its permissions over datas setted into it
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface ResourceProcessorInterface
{
    
    /**
     * Process the local user over the given resource.
     * 
     * @param ResourceInterface $resource
     *   Resource to process
     * @param EntryLoaderInterface $loader
     *   An entry loader to fetch an entry
     */
    public function process(ResourceInterface $resource, EntryLoaderInterface $loader): void;
    
    /**
     * Set a user processed by this processor
     * 
     * @param AclUser $user
     *   Acl user
     */
    public function setUser(AclUser $user): void;
    
    /**
     * Get the user processed
     * 
     * @return AclUser
     *   Acl user
     */
    public function getUser(): AclUser;
    
    /**
     * Identify the processor
     * 
     * @return string
     *   Processor identifier
     */
    public function getIdentifier(): string;
    
}
