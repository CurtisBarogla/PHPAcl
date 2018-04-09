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

namespace ZoeTest\Component\Acl\Fixtures\Resource;

use Zoe\Component\Acl\Resource\Resource as BaseResource;

/**
 * For testing purpose
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ResourceFixture extends BaseResource
{

    /**
     * Do nothing, just for testing add exception on non initialized reserved
     * 
     * @param string $name
     *   Resource name
     * @param int $behaviour
     *   Resource behaviour
     */
    public function __construct(string $name, int $behaviour)
    {
        $this->permissions = [];
    }
    
}
