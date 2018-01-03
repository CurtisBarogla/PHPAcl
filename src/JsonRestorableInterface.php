<?php
//StrictType
declare(strict_types = 1);

/*
 * Zoe
 * Acl component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */

namespace Zoe\Component\Acl;

/**
 * Object restorable from his json representation
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface JsonRestorableInterface extends \JsonSerializable
{
    
    /**
     * Restore an object from his json representation.
     * Json representation can be either an already dejsonified (via json_decode with arg 2 to true) or a raw string version of the object
     * 
     * @param array|string $json
     *   Json representation of the object to restore
     *   
     * @return mixed
     *   Restored object
     */
    public static function restoreFromJson($json);
    
}
