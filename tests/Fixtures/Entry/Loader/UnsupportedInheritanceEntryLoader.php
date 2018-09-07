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

namespace NessTest\Component\Acl\Fixtures\Entry\Loader;

use Ness\Component\Acl\Resource\EntryInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface;
use Ness\Component\Acl\Resource\Loader\Entry\Traits\InheritanceEntryLoaderTrait;
use Ness\Component\Acl\Resource\ExtendableResourceInterface;
use Ness\Component\Acl\Resource\Entry;

/**
 * Fixture only
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class UnsupportedInheritanceEntryLoader implements EntryLoaderInterface
{
    
    use InheritanceEntryLoaderTrait;

    /**
     * Resource for testing purpose
     * 
     * @var ExtendableResourceInterface
     */
    private $resource;
    
    /**
     * Initialize fixture loader
     * 
     * @param ExtendableResourceInterface $resource
     *   Entendable resource
     */
    public function __construct(ExtendableResourceInterface $resource)
    {
        $this->resource = $resource;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface::load()
     */
    public function load(ResourceInterface $resource, string $entry, ?string $processor = null): EntryInterface
    {
        $entry = new Entry("Foo");
        $this->loadParentEntry($this->resource, $entry, "FooEntry", $processor);
    }

    /**
     * {@inheritdoc}
     * @see \Ness\Component\Acl\Resource\Loader\Entry\Traits\InheritanceEntryLoaderTrait::setPermissionIntoEntry()
     */
    protected function setPermissionIntoEntry(EntryInterface $entry, string $permission): void
    {
        return;
    }
    
}
