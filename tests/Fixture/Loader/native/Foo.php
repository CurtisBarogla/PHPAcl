<?php

use Zoe\Component\Acl\Resource\Resource;
use Zoe\Component\Acl\Resource\ResourceInterface;

$resource = new Resource("Foo", ResourceInterface::BLACKLIST);
$resource->addPermission("Foo");

return $resource;
