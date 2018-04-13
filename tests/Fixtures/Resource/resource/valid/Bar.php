<?php
//StrictType
declare(strict_types = 1);

// Valid resource

use Zoe\Component\Acl\Resource\Resource;
use Zoe\Component\Acl\Resource\ResourceInterface;

$resource = new Resource("Bar", ResourceInterface::BLACKLIST);
$resource->add("foo")->add("bar");

return $resource;