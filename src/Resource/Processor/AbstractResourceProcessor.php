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

namespace Ness\Component\Acl\Resource\Processor;

use Ness\Component\Acl\User\AclUser;

/**
 * Common to all resource processors
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
abstract class AbstractResourceProcessor implements ResourceProcessorInterface
{
    
    /**
     * Acl user
     * 
     * @var AclUser
     */
    private $user;
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface::setUser()
     */
    public function setUser(AclUser $user): void
    {
        $this->user = $user;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface::getUser()
     */
    public function getUser(): AclUser
    {
        return $this->user;
    }
    
}
