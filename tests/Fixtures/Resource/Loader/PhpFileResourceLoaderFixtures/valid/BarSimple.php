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

return $extends("FooSimple", (new Resource("BarSimple"))->addPermission("bar")->addPermission("foo"));