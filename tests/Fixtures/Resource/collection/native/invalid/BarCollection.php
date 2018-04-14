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

// Return a different collection name from file name

use Zoe\Component\Acl\Resource\ResourceCollection;

return ResourceCollection::initializeCollection("FooCollection", [], ["foo", "bar"]);