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

namespace NessTest\Component\Acl;

use Ness\Component\Acl\Acl;
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface;
use Ness\Component\Acl\Resource\Loader\Entry\EntryLoaderInterface;
use Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface;
use Ness\Component\User\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Ness\Component\Acl\Exception\ResourceNotFoundException;
use Ness\Component\Acl\Exception\PermissionNotFoundException;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\User\AclUser;
use Ness\Component\Acl\AclBindableInterface;
use Ness\Component\User\User;
use Ness\Component\Acl\Resource\Resource;
use Ness\Component\Acl\Resource\Processor\AbstractResourceProcessor;

/**
 * Acl testcase
 * 
 * @see \Ness\Component\Acl\Acl
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class AclTest extends AclTestCase
{
    
    public function testIsAllowedWithLockedProcessor(): void
    {
        $user = new User("Foo");
        $whitelist = new Resource("Whitelist");
        $whitelist->addPermission("foo")->addPermission("bar");
        
        $entryLoader = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $resourceLoader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $resourceLoader
            ->expects($this->once())
            ->method("load")
            ->with("Whitelist")
            ->will($this->returnValue($whitelist));
        $processorFoo = $this->getMockBuilder(ResourceProcessorInterface::class)->getMock();
        $processorFoo->expects($this->never())->method("process");
        $rootProcessor = new class extends AbstractResourceProcessor {
            /**
             * {@inheritDoc}
             * @see \Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface::process()
             */
            public function process(ResourceInterface $resource, EntryLoaderInterface $loader): void
            {
                $resource->grantRoot()->to($this->getUser());
                $this->getUser()->lock($resource);
            }
            
            /**
             * {@inheritDoc}
             * @see \Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface::getIdentifier()
             */
            public function getIdentifier(): string
            {
                return "FixtureProcessor";
            }
        };
        
        $acl = new Acl($resourceLoader, $entryLoader);
        $acl->registerProcessor($rootProcessor);
        $acl->registerProcessor($processorFoo);
        
        $this->assertTrue($acl->isAllowed($user, "Whitelist", "foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWithPreviousSettedResource(): void
    {
        $user = new User("Foo");
        $whitelist = new Resource("Whitelist");
        $blacklist = new Resource("Blacklist", ResourceInterface::BLACKLIST);
        $whitelist->addPermission("foo")->addPermission("bar");
        $blacklist->addPermission("foo")->addPermission("bar");
        $resourceLoader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $entryLoader = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $resourceLoader
            ->expects($this->exactly(2))
            ->method("load")
            ->withConsecutive(["Whitelist"], ["Blacklist"])
            ->will($this->onConsecutiveCalls($whitelist, $blacklist));
        $processorFoo = $this->getMockBuilder(ResourceProcessorInterface::class)->getMock();
        $processorFoo->expects($this->once())->method("getIdentifier")->will($this->returnValue("FooProcessor"));
        $processorBar = $this->getMockBuilder(ResourceProcessorInterface::class)->getMock();
        $processorFoo->expects($this->once())->method("getIdentifier")->will($this->returnValue("BarProcessor"));
        foreach ([$processorFoo, $processorBar] as $processor)
            $processor->expects($this->exactly(4))->method("process")->withConsecutive([$whitelist, $entryLoader], [$blacklist, $entryLoader]);
        
        $acl = new Acl($resourceLoader, $entryLoader);
        
        $this->assertFalse($acl->isAllowed($user, "Whitelist", "bar"));
        $this->assertTrue($acl->isAllowed($user, "Blacklist", "bar"));
        
        $user = new User("Foo");
        $user->addAttribute(AclUser::ACL_ATTRIBUTE_IDENTIFIER, ["MozResource" => 42]);
        
        $acl->registerProcessor($processorFoo);
        $acl->registerProcessor($processorBar);
        
        $this->assertFalse($acl->isAllowed($user, "Whitelist", "bar"));
        $this->assertTrue($acl->isAllowed($user, "Blacklist", "bar"));
        
        $this->assertSame(["MozResource" => 42, "Whitelist" => 0, "Blacklist" => 3], $user->getAttribute(AclUser::ACL_ATTRIBUTE_IDENTIFIER));
        
        $user = new User("Bar");
        
        $this->assertFalse($acl->isAllowed($user, "Whitelist", "bar"));
        $this->assertTrue($acl->isAllowed($user, "Blacklist", "bar"));
        
        $this->assertSame(["Whitelist" => 0, "Blacklist" => 3], $user->getAttribute(AclUser::ACL_ATTRIBUTE_IDENTIFIER));
    }
    
    /**
     * @see \Ness\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWithAlreadyAttributeSetted(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(5))->method("getAttribute")->with(AclUser::ACL_ATTRIBUTE_IDENTIFIER)->will($this->returnValue(["Whitelist" => 1, "Blacklist" => 2]));
        $bindable = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $bindable->expects($this->exactly(3))->method("getAclResourceName")->will($this->returnValue("Blacklist"));
        $bindable->expects($this->exactly(2))->method("updateAclPermission")->withConsecutive([$user, "bar", true])->will($this->onConsecutiveCalls(true, null));
        $action = function(MockObject $resourceLoader, MockObject $entryLoader): void {
            $whitelist = $this->getMockBuilder(ResourceInterface::class)->getMock();
            $blacklist = $this->getMockBuilder(ResourceInterface::class)->getMock();
            $whitelist->expects($this->once())->method("getPermission")->with("foo")->will($this->returnValue(1));
            $whitelist->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::WHITELIST));
            $blacklist->expects($this->once())->method("getPermission")->with("bar")->will($this->returnValue(2));
            $blacklist->expects($this->exactly(2))->method("getBehaviour")->will($this->returnValue(ResourceInterface::BLACKLIST));
            
            $resourceLoader->expects($this->exactly(2))->method("load")->withConsecutive(["Whitelist"], ["Blacklist"])->will($this->onConsecutiveCalls($whitelist, $blacklist));
        };
        
        $acl = $this->getAcl($action);
        
        $this->assertTrue($acl->isAllowed($user, "Whitelist", "foo"));
        $this->assertFalse($acl->isAllowed($user, "Whitelist", "foo", function(UserInterface $user): bool {
            return false;
        }));
        
        // updated via bindable
        $this->assertFalse($acl->isAllowed($user, $bindable, "bar"));
        // bindable returns null
        $this->assertTrue($acl->isAllowed($user, $bindable, "bar"));
        $this->assertFalse($acl->isAllowed($user, $bindable, "bar", function(UserInterface $user, MockObject $resource) use ($bindable): bool {
            $this->assertSame($resource, $bindable);
            return true;
        }));
    }
    
    
    /**
     * @see \Ness\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWithPermissionLocked(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(6))->method("getAttribute")->with(AclUser::ACL_ATTRIBUTE_IDENTIFIER)->will($this->returnValue(["<FooResource>" => 2, "<BarResource>" => 4]));
        $bindable = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $bindable->expects($this->exactly(3))->method("getAclResourceName")->will($this->returnValue("BarResource"));
        $bindable->expects($this->never())->method("updateAclPermission");
        $action = function(MockObject $resourceLoader, MockObject $entryLoader): void {
            $resourceFoo = $this->getMockBuilder(ResourceInterface::class)->getMock();
            $resourceBar = $this->getMockBuilder(ResourceInterface::class)->getMock();
            $resourceFoo->expects($this->exactly(2))->method("getPermission")->withConsecutive(["foo"], ["bar"])->will($this->onConsecutiveCalls(1, 2));
            $resourceBar->expects($this->exactly(2))->method("getPermission")->withConsecutive(["bar"], ["moz"])->will($this->onConsecutiveCalls(2, 4));
            
            $resourceLoader->expects($this->exactly(2))->method("load")->withConsecutive(["FooResource"], ["BarResource"])->will($this->onConsecutiveCalls($resourceFoo, $resourceBar));
        };
        
        $acl = $this->getAcl($action);
        
        $this->assertFalse($acl->isAllowed($user, "FooResource", "foo"));
        $this->assertTrue($acl->isAllowed($user, "FooResource", "bar"));
        $this->assertTrue($acl->isAllowed($user, "FooResource", "bar"));
        
        $this->assertFalse($acl->isAllowed($user, $bindable, "bar"));
        $this->assertTrue($acl->isAllowed($user, $bindable, "moz"));
        $this->assertTrue($acl->isAllowed($user, $bindable, "moz"));
    }
    
    /**
     * @see \Ness\Component\Acl\Acl::registerProcessor()
     */
    public function testRegisterProcessor(): void
    {
        $acl = new Acl($this->getMockBuilder(ResourceLoaderInterface::class)->getMock(), $this->getMockBuilder(EntryLoaderInterface::class)->getMock());
        
        $processor = $this->getMockBuilder(ResourceProcessorInterface::class)->getMock();
        $processor->expects($this->once())->method("getIdentifier")->will($this->returnValue("FooProcessor"));
        
        $this->assertNull($acl->registerProcessor($processor));
    }
    
    /**
     * @see \Ness\Component\Acl\Acl::getProcessors()
     */
    public function testGetProcessors(): void
    {
        $acl = new Acl($this->getMockBuilder(ResourceLoaderInterface::class)->getMock(), $this->getMockBuilder(EntryLoaderInterface::class)->getMock());
        
        $this->assertSame([], $acl->getProcessors());
        
        $fooProcessor = $this->getMockBuilder(ResourceProcessorInterface::class)->getMock();
        $fooProcessor->expects($this->once())->method("getIdentifier")->will($this->returnValue("FooProcessor"));
        $barProcessor = $this->getMockBuilder(ResourceProcessorInterface::class)->getMock();
        $barProcessor->expects($this->once())->method("getIdentifier")->will($this->returnValue("BarProcessor"));
        
        $acl->registerProcessor($fooProcessor);
        $acl->registerProcessor($barProcessor);
        
        $this->assertSame(["FooProcessor", "BarProcessor"], $acl->getProcessors());
    }
    
    /**____EXCEPTIONS____**/
    
    /**
     * @see \Ness\Component\Acl\Acl::isAllowed()
     */
    public function testExceptionIsAllowedWhenResourceIsNotAValidType(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Resource MUST be an instance of AclBindableInterface or a string. 'array' given");
        
        $acl = $this->getAcl();
        
        $acl->isAllowed($this->getMockBuilder(UserInterface::class)->getMock(), [], "foo");
    }
    
    /**
     * @see \Ness\Component\Acl\Acl::isAllowed()
     */
    public function testExceptionIsAllowedWhenResourceNotFound(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        
        $action = function(MockObject $resourceLoader, MockObject $entryLoader): void {
            $resourceLoader->expects($this->once())->method("load")->with("FooResource")->will($this->throwException(new ResourceNotFoundException()));   
        };
        
        $acl = $this->getAcl($action);
        
        $acl->isAllowed($this->getMockBuilder(UserInterface::class)->getMock(), "FooResource", "foo");
    }
    
    /**
     * @see \Ness\Component\Acl\Acl::isAllowed()
     */
    public function testExceptionIsAllowedWhenAPermissionIsNotFound(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        
        $action = function(MockObject $resourceLoader, MockObject $entryLoader): void {
            $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
            $resource->expects($this->once())->method("getPermission")->with("foo")->will($this->throwException(new PermissionNotFoundException()));
            $resourceLoader->expects($this->once())->method("load")->with("FooResource")->will($this->returnValue($resource));
        };
        
        $acl = $this->getAcl($action);
        
        $acl->isAllowed($this->getMockBuilder(UserInterface::class)->getMock(), "FooResource", "foo");
    }
    
    /**
     * Generate an acl instance with a mocked resource and entry loader setted
     * 
     * @param \Closure $actions
     *   Action to perform on the resource loader and entry loader
     * 
     * @return Acl
     *   Initialize acl
     */
    private function getAcl(?\Closure $actions = null): Acl
    {
        $resourceLoader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $entryLoader = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        
        if(null !== $actions)
            $actions->call($this, $resourceLoader, $entryLoader);
        
        return new Acl($resourceLoader, $entryLoader);
    }
    
}
