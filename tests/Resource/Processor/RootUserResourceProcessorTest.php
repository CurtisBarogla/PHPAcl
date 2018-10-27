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

namespace NessTest\Component\Acl\Resource\Processor;

use NessTest\Component\Acl\AclTestCase;
use Ness\Component\User\UserInterface;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\User\AclUser;
use Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface;
use Ness\Component\Authentication\User\AuthenticatedUserInterface;
use Ness\Component\Acl\Normalizer\LockPatternNormalizerInterface;
use NessTest\Component\Acl\Fixtures\Processor\MockAwareUserRootUserResourceProcessor;

/**
 * RootUserResourceProcessor testcase
 * 
 * @see \Ness\Component\Acl\Resource\Processor\RootUserResourceProcessor
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RootUserResourceProcessorTest extends AclTestCase
{
    
    /**
     * @see \Ness\Component\Acl\Resource\Processor\AbstractRootUserResourceProcessor::process()
     */
    public function testProcess(): void
    {
        // Simple user
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->never())->method("grantRoot");
        $aclUser = new AclUser($user, $this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        
        $processor = new MockAwareUserRootUserResourceProcessor();
        $processor->setUser($aclUser);
        $processor->setBaseUser($user);
        
        $this->assertNull($processor->process($resource, $this->getMockBuilder(EntryLoaderInterface::class)->getMock()));
        
        if(\interface_exists("Ness\Component\Authentication\User\AuthenticatedUserInterface")) {
            // Authenticated but NOT root
            
            $user = $this->getMockBuilder(AuthenticatedUserInterface::class)->getMock();
            $user->expects($this->once())->method("isRoot")->will($this->returnValue(false));
            $processor->setBaseUser($user);
            
            $aclUser = new AclUser($user, $this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
            
            $processor->setUser($aclUser);
            
            $this->assertNull($processor->process($resource, $this->getMockBuilder(EntryLoaderInterface::class)->getMock()));
            
            // Root user
            
            $user = $this->getMockBuilder(AuthenticatedUserInterface::class)->getMock();
            $user->expects($this->once())->method("isRoot")->will($this->returnValue(true));
            $user->expects($this->once())->method("addAttribute");
            
            $processor->setBaseUser($user);
            
            $aclUser = new AclUser($user, $this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
            
            $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
            $resource->expects($this->once())->method("grantRoot")->will($this->returnValue($resource));
            $resource->expects($this->once())->method("to")->with($aclUser);
            
            $processor->setUser($aclUser);
            
            $this->assertNull($processor->process($resource, $this->getMockBuilder(EntryLoaderInterface::class)->getMock()));
        }
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Processor\AbstractRootUserResourceProcessor::getIdentifier()
     */
    public function testGetIdentifier(): void
    {
        $processor = new MockAwareUserRootUserResourceProcessor();
        $this->assertSame("AclRootUserProcessor", $processor->getIdentifier());
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Processor\AbstractRootUserResourceProcessor::setUser()
     */
    public function testSetUser(): void
    {
        $aclUser = new AclUser($this->getMockBuilder(UserInterface::class)->getMock(), $this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        
        $processor = new MockAwareUserRootUserResourceProcessor();
        
        $this->assertNull($processor->setUser($aclUser));
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Processor\AbstractRootUserResourceProcessor::getUser()
     */
    public function testGetUser(): void
    {
        $aclUser = new AclUser($this->getMockBuilder(UserInterface::class)->getMock(), $this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        
        $processor = new MockAwareUserRootUserResourceProcessor();
        $processor->setUser($aclUser);
        
        $this->assertSame($aclUser, $processor->getUser());
    }
    
}
