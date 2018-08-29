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

use Ness\Component\Acl\Resource\Resource;

return [
    "CombinedFoo"   =>  [
        "extends"       =>  "MultiplePoz",
        "permissions"   =>  ["foocombined"]
    ],
    
    (new Resource("CombinedBar"))->addPermission("barcombined"),
    $this->extendsFromTo("CombinedFoo", (new Resource("CombinedMoz"))->addPermission("mozcombined"))
];
