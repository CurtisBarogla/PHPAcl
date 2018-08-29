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

// Fixtures only

use Ness\Component\Acl\Resource\Entry;

return [
    (new Entry("BarEntry"))->addPermission("moz")->addPermission("poz"),
    "MozEntry"  =>  ["{FooEntry}", "{BarEntry}", "kek"],
    "FooEntry"  =>  ["foo", "bar"]
];
