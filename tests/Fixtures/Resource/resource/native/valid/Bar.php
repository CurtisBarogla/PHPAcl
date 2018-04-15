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

// Valid resource

use Zoe\Component\Acl\Resource\Resource;
use Zoe\Component\Acl\Resource\ResourceInterface;

$resource = new Resource("Bar", ResourceInterface::BLACKLIST);
$resource->addPermission("foo")->addPermission("bar");

return $resource;