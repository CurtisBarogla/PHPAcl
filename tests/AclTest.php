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
use Ness\Component\Acl\AclBindableInterface;
use Ness\Component\Acl\Resource\Resource;
use Ness\Component\Acl\Resource\Processor\AbstractResourceProcessor;
use Ness\Component\User\User;
use Ness\Component\Acl\User\AclUser;
use Ness\Component\Acl\Normalizer\LockPatternNormalizerInterface;
use Ness\Component\Acl\Signal\ResetSignalHandlerInterface;

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
    
    /**
     * @see \Ness\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWhenUserNotLockedAndPermissionNotSettedWithProcessorsLocking(): void
    {
        $baseUser = new User("FooUser");
        $resource = new Resource("Resource");
        $resource->addPermission("foo")->addPermission("bar");
        $action = function(MockObject $resourceLoader, MockObject $entryLoader, MockObject $normalizer, MockObject $handler) use ($resource, $baseUser): void {
            $resourceLoader
                ->expects($this->once())
                ->method("load")
                ->with("Resource")
                ->will($this->returnValue($resource));
            $handler->expects($this->once())->method("handle")->with($baseUser, AclUser::ACL_ATTRIBUTE_IDENTIFIER);
        };
        
        $processor = new class extends AbstractResourceProcessor {
            
            /**
             * {@inheritDoc}
             * @see \Ness\Component\Acl\Resource\Processor\ResourceProcessorInterface::process()
             */
            public function process(ResourceInterface $resource, EntryLoaderInterface $loader): void 
            {
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
        
        $processorBar = $this->getMockBuilder(ResourceProcessorInterface::class)->getMock();
        $processorBar->expects($this->once())->method("getIdentifier")->will($this->returnValue("BarProcessor"));
        $processorBar->expects($this->never())->method("setUser");
        $processorBar->expects($this->never())->method("process");

        $acl = $this->getAcl($action);
        $acl->registerProcessor($processor);
        $acl->registerProcessor($processorBar);
        
        $this->assertFalse($acl->isAllowed($baseUser, "Resource", "foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWhenUserNotLockedAndPermissionNotSettedWithProcessorsNotLocking(): void
    {
        $loaderReference = null;
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermission")->with("foo")->will($this->returnValue(4));
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::WHITELIST));
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        $user->expects($this->atLeastOnce())->method("getName")->will($this->returnValue("FooUser"));
        $user
            ->expects($this->exactly(3))
            ->method("isLocked")
            ->with($resource)
            ->will($this->returnValue(false, false, false));
        $user
            ->expects($this->exactly(2))
            ->method("getPermission")
            ->withConsecutive([$resource])
            ->will($this->onConsecutiveCalls(null, 0));
        
        $action = function(MockObject $resourceLoader, MockObject $entryLoader, MockObject $normalizer, MockObject $handler) use ($resource, &$loaderReference, $user): void {
            $loaderReference = $entryLoader;
            $resourceLoader
                ->expects($this->once())
                ->method("load")
                ->with("Resource")
                ->will($this->returnValue($resource));
            $handler->expects($this->never())->method("handle");
        };
        
        
        $acl = $this->getAcl($action);
        
        $processorFoo = $this->getMockBuilder(ResourceProcessorInterface::class)->getMock();
        $processorBar = $this->getMockBuilder(ResourceProcessorInterface::class)->getMock();
        $processorFoo->expects($this->once())->method("setUser")->with($user);
        $processorFoo->expects($this->once())->method("process")->with($resource, $loaderReference);
        $processorFoo->expects($this->once())->method("getIdentifier")->will($this->returnValue("FooProcessor"));
        $processorBar->expects($this->once())->method("setUser")->with($user);
        $processorBar->expects($this->once())->method("process")->with($resource, $loaderReference);
        $processorBar->expects($this->once())->method("getIdentifier")->will($this->returnValue("BarProcessor"));
        
        $acl->registerProcessor($processorFoo);
        $acl->registerProcessor($processorBar);
        $this->injectAclUser("FooUser", $user, $acl);
        
        $this->assertFalse($acl->isAllowed($user, "Resource", "foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWhenUserNotLockedAndPermissionNotSettedWithNotProcessorsResourceBlacklist(): void
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermission")->with("foo")->will($this->returnValue(4));
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::BLACKLIST));
        
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        $user->expects($this->atLeastOnce())->method("getName")->will($this->returnValue("FooUser"));
        $user
            ->expects($this->once())
            ->method("isLocked")
            ->with($resource)
            ->will($this->returnValue(false));
        $user
            ->expects($this->exactly(2))
            ->method("getPermission")
            ->withConsecutive([$resource])
            ->will($this->onConsecutiveCalls(null, 15));
        
        $resource->expects($this->once())->method("grantRoot")->will($this->returnValue($resource));
        $resource->expects($this->once())->method("to")->with($user);
        
        $action = function(MockObject $resourceLoader, MockObject $entryLoader, MockObject $normalizer, MockObject $handler) use ($resource, $user): void {
            $resourceLoader
                ->expects($this->once())
                ->method("load")
                ->with("ResourceBlacklist")
                ->will($this->returnValue($resource));
            $handler->expects($this->never())->method("handle");
        };
        
        $acl = $this->getAcl($action);
        $this->injectAclUser("FooUser", $user, $acl);
        
        $this->assertTrue($acl->isAllowed($user, "ResourceBlacklist", "foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWhenUserNotLockedAndPermissionNotSettedWithNotProcessorsResourceWhitelist(): void
    {        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getPermission")->with("foo")->will($this->returnValue(1));
        $resource->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::WHITELIST));
        
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        $user->expects($this->once())->method("setPermission")->with($resource, 0);
        $user->expects($this->atLeastOnce())->method("getName")->will($this->returnValue("FooUser"));
        $user
            ->expects($this->once())
            ->method("isLocked")
            ->with($resource)
            ->will($this->returnValue(false));
        $user
            ->expects($this->exactly(2))
            ->method("getPermission")
            ->withConsecutive([$resource])
            ->will($this->onConsecutiveCalls(null, 0));
        
        $action = function(MockObject $resourceLoader, MockObject $entryLoader, MockObject $normalizer, MockObject $handler) use ($resource, $user): void {
            $resourceLoader
                ->expects($this->once())
                ->method("load")
                ->with("ResourceWhitelist")
                ->will($this->returnValue($resource));
            $handler->expects($this->never())->method("handle");
        };
        
        $acl = $this->getAcl($action);
        $this->injectAclUser("FooUser", $user, $acl);
        
        $this->assertFalse($acl->isAllowed($user, "ResourceWhitelist", "foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWhenUserNotLockedAndPermissionSettedWithUpdateViaUpdateOverridingBindableResourceBlacklist(): void
    {
        $resourceFoo = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resourceFoo->expects($this->once())->method("getPermission")->with("foo")->will($this->returnValue(1));
        $resourceFoo->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::BLACKLIST));
        $resourceBar = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resourceBar->expects($this->once())->method("getPermission")->with("foo")->will($this->returnValue(2));
        $resourceBar->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::BLACKLIST));
        $resourceMoz = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resourceMoz->expects($this->once())->method("getPermission")->with("foo")->will($this->returnValue(2));
        $resourceMoz->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::BLACKLIST));
        
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        
        $bindable = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $bindable->expects($this->once())->method("getAclResourceName")->will($this->returnValue("BarResource"));
        $bindable->expects($this->never())->method("updateAclPermission");
        
        $bindableMoz = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $bindableMoz->expects($this->once())->method("getAclResourceName")->will($this->returnValue("MozResource"));
        $bindableMoz->expects($this->once())->method("updateAclPermission")->with($user, "foo", true)->will($this->returnValue(true));
        
        $user->expects($this->atLeastOnce())->method("getName")->will($this->returnValue("FooUser"));
        $user
            ->expects($this->exactly(3))
            ->method("isLocked")
            ->withConsecutive([$resourceFoo], [$resourceBar], [$resourceMoz])
            ->will($this->returnValue(false, false, false));
        $user
            ->expects($this->exactly(3))
            ->method("getPermission")
            ->withConsecutive([$resourceFoo], [$resourceBar], [$resourceMoz])
            ->will($this->onConsecutiveCalls(15, 0, 15));
        
        $action = function(MockObject $resourceLoader, MockObject $entryLoader, MockObject $normalizer, MockObject $handler) use ($resourceFoo, $resourceBar, $resourceMoz, $user): void {
            $resourceLoader
                ->expects($this->exactly(3))
                ->method("load")
                ->withConsecutive(["FooResource"], ["BarResource"], ["MozResource"])
                ->will($this->onConsecutiveCalls($resourceFoo, $resourceBar, $resourceMoz));
            $handler->expects($this->never())->method("handle");
        };
        
        $acl = $this->getAcl($action);
        $this->injectAclUser("FooUser", $user, $acl);
        
        $this->assertFalse($acl->isAllowed($user, "FooResource", "foo", function(UserInterface $user): ?bool {
            return true;
        }));
        $this->assertTrue($acl->isAllowed($user, $bindable, "foo", function(UserInterface $user, AclBindableInterface $resource) use ($bindable): ?bool {
            $this->assertSame($bindable, $resource);
            return false;
        }));
        $this->assertFalse($acl->isAllowed($user, $bindableMoz, "foo", function(UserInterface $user, AclBindableInterface $resource) use ($bindableMoz): ?bool {
            $this->assertSame($bindableMoz, $resource);
            return null;
        }));
    }
    
    /**
     * @see \Ness\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWhenUserNotLockedAndPermissionSettedWithUpdateViaUpdateOverridingBindableResourceWhitelist(): void
    {
        $resourceFoo = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resourceFoo->expects($this->once())->method("getPermission")->with("foo")->will($this->returnValue(1));
        $resourceFoo->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::WHITELIST));
        $resourceBar = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resourceBar->expects($this->once())->method("getPermission")->with("foo")->will($this->returnValue(2));
        $resourceBar->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::WHITELIST));
        $resourceMoz = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resourceMoz->expects($this->once())->method("getPermission")->with("foo")->will($this->returnValue(2));
        $resourceMoz->expects($this->once())->method("getBehaviour")->will($this->returnValue(ResourceInterface::WHITELIST));
        
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        
        $bindable = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $bindable->expects($this->once())->method("getAclResourceName")->will($this->returnValue("BarResource"));
        $bindable->expects($this->never())->method("updateAclPermission");
        
        $bindableMoz = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $bindableMoz->expects($this->once())->method("getAclResourceName")->will($this->returnValue("MozResource"));
        $bindableMoz->expects($this->once())->method("updateAclPermission")->with($user, "foo", false)->will($this->returnValue(true));
        
        $user->expects($this->atLeastOnce())->method("getName")->will($this->returnValue("FooUser"));
        $user
            ->expects($this->exactly(3))
            ->method("isLocked")
            ->withConsecutive([$resourceFoo], [$resourceBar], [$resourceMoz])
            ->will($this->returnValue(false, false, false));
        $user
            ->expects($this->exactly(3))
            ->method("getPermission")
            ->withConsecutive([$resourceFoo], [$resourceBar], [$resourceMoz])
            ->will($this->onConsecutiveCalls(0, 0, 0));
        
        $action = function(MockObject $resourceLoader, MockObject $entryLoader, MockObject $normalizer, MockObject $handler) use ($resourceFoo, $resourceBar, $resourceMoz, $user): void {
            $resourceLoader
                ->expects($this->exactly(3))
                ->method("load")
                ->withConsecutive(["FooResource"], ["BarResource"], ["MozResource"])
                ->will($this->onConsecutiveCalls($resourceFoo, $resourceBar, $resourceMoz));
            $handler->expects($this->never())->method("handle");
        };
        
        $acl = $this->getAcl($action);
        $this->injectAclUser("FooUser", $user, $acl);
        
        $this->assertTrue($acl->isAllowed($user, "FooResource", "foo", function(UserInterface $user): ?bool {
            return true; 
        }));
        $this->assertFalse($acl->isAllowed($user, $bindable, "foo", function(UserInterface $user, AclBindableInterface $resource) use ($bindable): ?bool {
            $this->assertSame($bindable, $resource);
            return false;
        }));
        $this->assertTrue($acl->isAllowed($user, $bindableMoz, "foo", function(UserInterface $user, AclBindableInterface $resource) use ($bindableMoz): ?bool {
            $this->assertSame($bindableMoz, $resource);
            return null;
        }));
    }
    
    /**
     * @see \Ness\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWhenUserNotLockedAndPermissionSetWithNoUpdate(): void
    {
        $resourceFoo = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resourceBar = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resourceFoo->expects($this->once())->method("getPermission")->with("foo")->will($this->returnValue(1));
        $resourceBar->expects($this->once())->method("getPermission")->with("foo")->will($this->returnValue(2));
        
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        
        $bindable = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $bindable->expects($this->once())->method("getAclResourceName")->will($this->returnValue("BarResource"));
        $bindable->expects($this->once())->method("updateAclPermission")->with($user, "foo", false)->will($this->returnValue(null));
        
        $user->expects($this->atLeastOnce())->method("getName")->will($this->returnValue("FooUser"));
        $user
            ->expects($this->exactly(2))
            ->method("isLocked")
            ->withConsecutive([$resourceFoo], [$resourceBar])
            ->will($this->returnValue(false, false));
        $user
            ->expects($this->exactly(2))
            ->method("getPermission")
            ->withConsecutive([$resourceFoo], [$resourceBar])
            ->will($this->onConsecutiveCalls(3, 0));
        
        $action = function(MockObject $resourceLoader, MockObject $entryLoader, MockObject $normalizer, MockObject $handler) use ($resourceFoo, $resourceBar, $user): void {
            $resourceLoader
                ->expects($this->exactly(2))
                ->method("load")
                ->withConsecutive(["FooResource"], ["BarResource"])
                ->will($this->onConsecutiveCalls($resourceFoo, $resourceBar));
            $handler->expects($this->never())->method("handle");
        };
        
        $acl = $this->getAcl($action);
        $this->injectAclUser("FooUser", $user, $acl);
        
        $this->assertTrue($acl->isAllowed($user, "FooResource", "foo"));
        $this->assertFalse($acl->isAllowed($user, $bindable, "foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\Acl::isAllowed()
     */
    public function testIsAllowedWhenUserIsLocked(): void
    {
        $bindable = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $bindable->expects($this->never())->method("updateAclPermission");
        $bindable->expects($this->once())->method("getAclResourceName")->will($this->returnValue("BarResource"));
        
        $resourceFoo = $this->getMockBuilder(ResourceInterface::class)->getMock();   
        $resourceFoo->expects($this->once())->method("getPermission")->with("foo")->will($this->returnValue(1));
        $resourceBar = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resourceBar->expects($this->once())->method("getPermission")->with("foo")->will($this->returnValue(1));
        
        $user = $this->getMockBuilder(AclUser::class)->disableOriginalConstructor()->getMock();
        $user->expects($this->atLeastOnce())->method("getName")->will($this->returnValue("FooUser"));
        $user
            ->expects($this->exactly(3))
            ->method("isLocked")
            ->withConsecutive([$resourceFoo], [$resourceFoo], [$resourceBar])
            ->will($this->returnValue(true, true, true));
        $user
            ->expects($this->exactly(3))
            ->method("getPermission")
            ->withConsecutive([$resourceFoo], [$resourceFoo], [$resourceBar])
            ->will($this->onConsecutiveCalls(3, 0, 2));
        $action = function(MockObject $resourceLoader, MockObject $entryLoader, MockObject $normalizer, MockObject $handler) use ($resourceFoo, $resourceBar, $user): void {
            $resourceLoader
                ->expects($this->exactly(2))
                ->method("load")
                ->withConsecutive(["FooResource"], ["BarResource"])
                ->will($this->onConsecutiveCalls($resourceFoo, $resourceBar));
            $handler->expects($this->never())->method("handle");
        };
        $acl = $this->getAcl($action);
        $this->injectAclUser("FooUser", $user, $acl);
        
        $this->assertTrue($acl->isAllowed($user, "FooResource", "foo"));
        $this->assertFalse($acl->isAllowed($user, "FooResource", "foo"));
        
        $this->assertFalse($acl->isAllowed($user, $bindable, "foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\Acl::registerProcessor()
     */
    public function testRegisterProcessor(): void
    {
        $acl = $this->getAcl(null);
        
        $processor = $this->getMockBuilder(ResourceProcessorInterface::class)->getMock();
        $processor->expects($this->once())->method("getIdentifier")->will($this->returnValue("FooProcessor"));
        
        $this->assertNull($acl->registerProcessor($processor));
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
        
        $action = function(MockObject $resourceLoader, MockObject $entryLoader, MockObject $normalizer, MockObject $handler): void {
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
        
        $action = function(MockObject $resourceLoader, MockObject $entryLoader, MockObject $normalizer, MockObject $handler): void {
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
     *   Action to perform on the resource loader and entry loader and normalizer
     * 
     * @return Acl
     *   Initialized acl
     */
    private function getAcl(?\Closure $actions = null): Acl
    {
        $resourceLoader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $entryLoader = $this->getMockBuilder(EntryLoaderInterface::class)->getMock();
        $normalizer = $this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock();
        $handler = $this->getMockBuilder(ResetSignalHandlerInterface::class)->getMock();
        
        if(null !== $actions)
            $actions->call($this, $resourceLoader, $entryLoader, $normalizer, $handler);
        
        return new Acl($resourceLoader, $entryLoader, $normalizer, $handler);
    }
    
    /**
     * Inject an acl user into the loaded property of the acl instance
     * 
     * @param string $username
     *   Acl username
     * @param AclUser $user
     *   User instance to inject
     * @param Acl $acl
     *   Acl inject which the user is injected
     */
    private function injectAclUser(string $username, AclUser $user, Acl $acl): void
    {
        $reflection = new \ReflectionClass($acl);
        $property = $reflection->getProperty("loaded");
        $property->setAccessible(true);
        $property->setValue($acl, [$username => $user]);
    }
    
}
