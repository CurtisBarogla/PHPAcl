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

use Ness\Component\Acl\Resource\EntryInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderAwareInterface;
use Ness\Component\Acl\Traits\ResourceLoaderAwareTrait;
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface;
use Ness\Component\Acl\Resource\ExtendableResource;
use Ness\Component\Acl\Resource\ExtendableResourceInterface;
use Ness\Component\Acl\Exception\EntryNotFoundException;

/**
 * Try to load an entry from a resource and its parents if possible
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ResourceInheritanceEntryLoaderWrapper implements EntryLoaderInterface, ResourceLoaderAwareInterface
{
    
    use ResourceLoaderAwareTrait;
    
    /**
     * Resource loader
     * 
     * @var ResourceLoaderInterface
     */
    private $loader;
    
    /**
     * Entry loader wrapped
     * 
     * @var EntryLoaderInterface
     */
    private $wrapped;
    
    /**
     * Initialize loader
     * 
     * @param EntryLoaderInterface $wrapped
     *   Wrapper entry loader
     */
    public function __construct(EntryLoaderInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface::load()
     */
    public function load(ResourceInterface $resource, string $entry, ?string $processor = null): EntryInterface
    {
        if(!$resource instanceof ExtendableResourceInterface) {
            return $this->wrapped->load($resource, $entry, $processor);
        }
        
        try {
            return $this->wrapped->load($resource, $entry, $processor);
        } catch (EntryNotFoundException $e) {
            foreach (ExtendableResource::generateParents($resource, $this->getLoader()) as $parent) {
                try {
                    return $this->wrapped->load($parent, $entry, $processor);
                } catch (EntryNotFoundException $e) {
                    $visited[] = $parent->getName();
                    continue;
                }
            }
        }
        
        $exception = new EntryNotFoundException(\sprintf("This entry '%s' is not loadable for resource '%s' nor into its parents '%s'",
            $entry,
            $resource->getName(),
            \implode(", ", $visited)));
        $exception->setEntry($entry);
        
        throw $exception;
    }

}
