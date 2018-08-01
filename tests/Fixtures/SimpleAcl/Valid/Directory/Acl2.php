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
    ->addResource("BarResource", "FooResource")
    ->addPermission("loz")
    ->addPermission("kek")
    ->addEntry("FooEntry", ["loz", "kek"])
    ->wrapProcessor("FooProcessor")
        ->addEntry("FooEntry", ["FooEntry", "kek"])
        ->addEntry("BarEntry", ["BarEntry", "loz"]);