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

$this
    ->addResource("FooResource")
    ->addPermission("foo")
    ->addPermission("bar")
    ->addPermission("moz")
    ->addPermission("poz")
    ->wrapProcessor("FooProcessor")
        ->addEntry("FooEntry", ["foo"])
        ->addEntry("BarEntry", ["FooEntry", "bar"]);