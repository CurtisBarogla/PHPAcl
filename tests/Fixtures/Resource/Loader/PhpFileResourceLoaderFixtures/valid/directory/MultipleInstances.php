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

// Fixture only

use Ness\Component\Acl\Resource\Resource;

return [
    $this->extendsFromTo("MultipleFoo", (new Resource("MultipleBar"))->addPermission("barmultiple")),
    $this->extendsFromTo("FooSimple", (new Resource("MultipleFoo"))->addPermission("foomultiple")),
];