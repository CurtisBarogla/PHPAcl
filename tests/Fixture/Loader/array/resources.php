<?php

return [
    "Foo"   =>  [
        "behaviour"     =>  "blacklist"
    ],
    "Bar"   =>  [
        "behaviour"     =>  "whitelist"
    ],
    "Moz"   =>  [
        "behaviour"     =>  "blacklist",
        "permissions"   =>  ["FooPermission", "BarPermission"]
    ],
    "Poz"   =>  [
        "behaviour"     =>  "whitelist",
        "permissions"   =>  ["FooPermission", "BarPermission"],
        "entities"      =>  [
            "Foo"           =>  [
                "values"        =>  [
                    "FooValue"      =>  ["FooPermission", "BarPermission"],
                    "BarValue"      =>  ["FooPermission"]
                ]
            ],
            "Bar"           =>  [
                "processor"     =>  "FooProcessor"
            ]
        ]
    ],
];
