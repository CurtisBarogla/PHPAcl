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
use Ness\Component\Acl\Exception\EntryNotFoundException;

/**
 * Fallback to a null processor if current processor for current entry does not return a valid entry.
 * Should not be used for keeping entries consistency and lisibility, but it exists.
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class FallbackProcessorEntryLoaderWrapper implements EntryLoaderInterface
{
    
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
     *   Entry loader wrapped
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
        if(null === $processor)
            return $this->wrapped->load($resource, $entry, $processor);
        
        try {
            return $this->wrapped->load($resource, $entry, $processor);
        } catch (EntryNotFoundException $e) {
            try {
                return $this->wrapped->load($resource, $entry, null);                                
            } catch (EntryNotFoundException $e) {
                $exception = new EntryNotFoundException($entry, "This entry '{$entry}' cannot be loaded for resource '{$resource->getName()}' through processor '{$processor}' nor global (null) processor");
                
                throw $exception;
            }
        }
    }

}
