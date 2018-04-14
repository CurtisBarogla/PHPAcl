<?php
//StrictType
declare(strict_types = 1);

/*
 * Zoe
 * Acl component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */

use Zoe\Component\Acl\Resource\Resource;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\Resource\ResourceCollection;

$resourceFoo = new Resource("Foo", ResourceInterface::BLACKLIST);
$resourceBar = new Resource("Bar", ResourceInterface::BLACKLIST);

return ResourceCollection::initializeCollection("FooCollection", [$resourceFoo, $resourceBar], ["foo", "bar"]);