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
use Ness\Component\Acl\Resource\Entry;
use Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface;

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
    * @param string $name
    *   Entry name to load
    * @param string|null $processor
    *   Processor identifier
    * @param EntryLoaderInterface|null $loader
    *   A specific entry loader to use to load an entry
    *   
    * @throws EntryNotFoundException
    *   When entry not found or the current loader is not a valid one
    */
   protected function loadParentEntry(
       ExtendableResourceInterface $resource, 
       string $name, 
       ?string $processor,
       ?EntryLoaderInterface $loader = null): EntryInterface
   {
        $loader = $loader ?? $this;
        if(!$loader instanceof ResourceLoaderAwareInterface)
            throw new EntryNotFoundException("Resource loader MUST be an instance of ResourceLoaderAwareInterface to be able to load a parent entry from a parent resource");               

        $entry = new Entry($name);
        $visited = [];
        foreach (ExtendableResource::generateParents($resource, $this->getLoader()) as $parentResource) {
            try {
                foreach ($loader->load($parentResource, $name, $processor) as $permission) {
                    $entry->addPermission($permission);
                }
                
                return $entry;
            } catch (EntryNotFoundException $e) {
                $visited[] = $parentResource->getName();
                continue;
            }
        }
        
        throw new EntryNotFoundException($name, \sprintf("This entry '%s' cannot be loaded into resource '%s' nor into its parents '%s'",
            $name,
            $resource->getName(),
            \implode(', ', $visited)));
   }
    
}
