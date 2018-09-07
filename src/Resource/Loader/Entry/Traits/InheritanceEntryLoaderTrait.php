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

namespace Ness\Component\Acl\Resource\Loader\Entry\Traits;

use Ness\Component\Acl\Exception\EntryNotFoundException;
use Ness\Component\Acl\Resource\ExtendableResource;
use Ness\Component\Acl\Resource\ExtendableResourceInterface;
use Ness\Component\Acl\Resource\EntryInterface;
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderAwareInterface;

/**
 * Proceed a look up over parents resources for loading a parent entry
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
trait InheritanceEntryLoaderTrait
{
   
   /**
    * Loop over the parents resources of the given resource trying to find the most near parent entry
    * 
    * @param ExtendableResourceInterface $resource
    *   Extendable resource
    * @param EntryInterface $entry
    *   Current entry to load the permissions
    * @param string $parent
    *   Parent entry identifier
    * @param string|null $processor
    *   Processor identifier
    *   
    * @throws \LogicException
    *   If loader is not an instance of ResourceLoaderAwareInterface
    */
   protected function loadParentEntry(ExtendableResourceInterface $resource, EntryInterface $entry, string $parent, ?string $processor): void
   {
        if(!$this instanceof ResourceLoaderAwareInterface)
            throw new \LogicException("Resource loader MUST be an instance of ResourceLoaderAwareInterface to be able to load a parent entry from a parent resource");               

        foreach (ExtendableResource::generateParents($resource, $this->getLoader()) as $parentResource) {
            try {
                foreach ($this->load($parentResource, $parent, $processor) as $permission) {
                    $this->setPermissionIntoEntry($entry, $permission);
                }
                return;
            } catch (EntryNotFoundException $e) {
                continue;
            }
        }
        
        throw new EntryNotFoundException($parent, "This parent entry '{$parent}' for loading entry '{$entry->getName()}' cannot be loaded into resource '{$resource->getName()}' not into its parents");
   }
   
   /**
    * Set the procedure to apply to set a permission into an entry
    * 
    * @param EntryInterface $entry
    *   Entry which the permission is added
    * @param string $permission
    *   Permission to set
    */
   abstract protected function setPermissionIntoEntry(EntryInterface $entry, string $permission): void;
    
}
