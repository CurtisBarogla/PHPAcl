<?php
//StrictType
declare(strict_types = 1);

// Return a different resource name from file name

use Zoe\Component\Acl\Resource\Resource;
use Zoe\Component\Acl\Resource\ResourceInterface;

$resource = new Resource("Foo", ResourceInterface::BLACKLIST);

return $resource;