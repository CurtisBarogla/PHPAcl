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
    "FooEntry"  =>  ["fooo", "barr"],
    (new Entry("BarEntry"))->addPermission("mozz")->addPermission("pozz")
];
