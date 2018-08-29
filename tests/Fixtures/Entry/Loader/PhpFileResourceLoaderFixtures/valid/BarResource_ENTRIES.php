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
    "MozEntry"  =>  ["{FooEntry}", "{BarEntry}", "kek"],
    (new Entry("BarEntry"))->addPermission("moz")->addPermission("poz"),
    "FooEntry"  =>  ["foo", "bar"]
];