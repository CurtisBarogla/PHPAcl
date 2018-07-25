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

$this
    ->addResource("BarResource", "FooResource")
    ->addPermission("moz")
    ->addPermission("poz")
    ->addEntry("FooEntry", ["FooEntry", "moz", "poz"]);