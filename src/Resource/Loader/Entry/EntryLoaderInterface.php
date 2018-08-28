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

namespace Ness\Component\Acl\Resource\Loader\Entry;

use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Resource\EntryInterface;
use Ness\Component\Acl\Exception\EntryNotFoundException;

/**
 * Responsible to load acl entries from multiple sources over a resource
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface EntryLoaderInterface
{
    
    /**
     * Load an acl entry over a resource
     * 
     * @param ResourceInterface $resource
     *   Resource to get the entry
     * @param string $entry
     *   Entry name to get
     * @param string|null $processor
     *   A processor identifier
     * 
     * @return EntryInterface
     *   Entry found for this resource and name
     * 
     * @throws EntryNotFoundException
     *   When given entry does not correspond to a valid one for the given resource name
     */
    public function load(ResourceInterface $resource, string $entry, ?string $processor = null): EntryInterface;
    
}
