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
use Ness\Component\Acl\Resource\ExtendableResourceInterface;
use Ness\Component\Acl\Exception\EntryNotFoundException;
use Ness\Component\Acl\Resource\Loader\Entry\Traits\InheritanceEntryLoaderTrait;

/**
 * Try to load an entry from a resource and its parents if possible
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ResourceInheritanceEntryLoaderWrapper implements EntryLoaderInterface, ResourceLoaderAwareInterface
{
    
    use ResourceLoaderAwareTrait;
    use InheritanceEntryLoaderTrait;
    
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
        if($this->wrapped instanceof ResourceLoaderAwareInterface)
            $this->wrapped->setLoader($this->getLoader());
        
        if(!$resource instanceof ExtendableResourceInterface)
            return $this->wrapped->load($resource, $entry, $processor);

        try {
            return $this->wrapped->load($resource, $entry, $processor);
        } catch (EntryNotFoundException $e) {
            return $this->loadParentEntry($resource, $e->getEntry(), $processor, $this->wrapped);
        }
        
    }

}
