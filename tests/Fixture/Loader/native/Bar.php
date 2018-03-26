<?php

use Zoe\Component\Acl\Resource\Resource;
use Zoe\Component\Acl\Resource\ResourceInterface;

$resource = new Resource("Bar", ResourceInterface::BLACKLIST);
$resource->addPermission("Bar");

return $resource;
