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

namespace NessTest\Component\Acl\Fixtures\Processor;

use Ness\Component\Acl\Resource\Processor\AbstractRootUserResourceProcessor;
use Ness\Component\User\UserInterface;

/**
 * Fixture only
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class MockAwareUserRootUserResourceProcessor extends AbstractRootUserResourceProcessor
{
    
    /**
     * Base user
     * 
     * @var UserInterface 
     */
    private $baseUser;
    
    /**
     * Register the mock user returned by getBaseUser
     * 
     * @param UserInterface $baseUser
     *   Base user
     */
    public function setBaseUser(UserInterface $baseUser): void
    {
        $this->baseUser = $baseUser;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Acl\Resource\Processor\AbstractRootUserResourceProcessor::getBaseUser()
     */
    protected function getBaseUser(): UserInterface
    {
        return $this->baseUser;
    }
    
}
