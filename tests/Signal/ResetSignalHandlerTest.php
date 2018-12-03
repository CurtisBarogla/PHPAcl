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

namespace NessTest\Component\Acl\Signal;

use NessTest\Component\Acl\AclTestCase;
use Ness\Component\Acl\Signal\Storage\ResetSignalStoreInterface;
use Ness\Component\Acl\Signal\ResetSignalHandler;
use Ness\Component\User\UserInterface;

/**
 * ResetSignalHandler testcase
 * 
 * @see \Ness\Component\Acl\Signal\ResetSignalHandler
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ResetSignalHandlerTest extends AclTestCase
{
    
    /**
     * @see \Ness\Component\Acl\Signal\ResetSignalHandler::send()
     */
    public function testSend(): void
    {
        $store = $this->getMockBuilder(ResetSignalStoreInterface::class)->getMock();
        $store->expects($this->exactly(2))->method("add")->with("ness_reset_signal".\sha1("Foo"))->will($this->onConsecutiveCalls(true, false));
        
        $user = $this->getMockBuilder(UserInterface::class)->disableOriginalConstructor()->getMock();
        $user->expects($this->exactly(4))->method("getName")->will($this->returnValue("Foo"));
        
        $handler = new ResetSignalHandler($store);
        
        $this->assertTrue($handler->send($user));
        $this->assertFalse($handler->send($user));
    }
    
    /**
     * @see \Ness\Component\Acl\Signal\ResetSignalHandler::handle()
     */
    public function testHandle(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->atLeastOnce())->method("getName")->will($this->returnValue("Foo"));
        $user->expects($this->exactly(2))->method("deleteAttribute")->with("FooAttribute");
        
        $store = $this->getMockBuilder(ResetSignalStoreInterface::class)->getMock();
        $store
            ->expects($this->exactly(4))
            ->method("has")
            ->with("ness_reset_signal".\sha1("Foo"))
            ->will($this->onConsecutiveCalls(true, false, true, false));
        $store
            ->expects($this->exactly(2))
            ->method("remove")
            ->with("ness_reset_signal".\sha1("Foo"))
            ->will($this->returnValue(true, true));
        
        $handler = new ResetSignalHandler($store);
        
        $this->assertNull($handler->handle($user, "FooAttribute"));
        $handler->send($user);
        $this->assertNull($handler->handle($user, "FooAttribute"));
        $this->assertNull($handler->handle($user, "FooAttribute"));
        $this->assertNull($handler->handle($user, "FooAttribute"));
    }
    
}
