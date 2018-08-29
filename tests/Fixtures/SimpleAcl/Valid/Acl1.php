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

use Ness\Component\Acl\SimpleAcl;

$this->changeBehaviour(SimpleAcl::BLACKLIST);
$this
    ->addResource("MozResource")
    ->addPermission("foo")->addPermission("bar");
