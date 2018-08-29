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

return [
    "MultiplePoz"   =>  [
        "extends"       =>  "MultipleMoz",
        "permissions"   =>  ["pozmultiple"]
    ],
    
    "MultipleMoz"    =>  [
        "extends"       =>  "MultipleFoo",
        "permissions"   =>  ["mozmultiple"]
    ]
];
