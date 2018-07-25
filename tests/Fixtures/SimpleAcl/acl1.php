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
    ->addResource("FooResource")
    ->addPermission("foo")
    ->addPermission("bar")
    ->wrapProcessor("FooProcessor")
        ->addEntry("FooEntry", ["foo", "bar"])
    ->endWrapProcessor();