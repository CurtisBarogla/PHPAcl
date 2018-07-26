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

use Ness\Component\Acl\SimpleAcl;
use Ness\Component\Acl\Exception\InvalidArgumentException;
use Ness\Component\Acl\Exception\ResourceNotFoundException;
use Psr\SimpleCache\CacheInterface;
use Ness\Component\User\UserInterface;
use Ness\Component\Acl\AclBindableInterface;
use Ness\Component\Acl\Exception\PermissionNotFoundException;

/**
 * SimpleAcl testcase
 * 
 * @see \Ness\Component\Acl\SimpleAcl
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class SimpleAclTest extends AclTestCase
{
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     */
    public function testIsAllowed(): void
    {
        // Blacklist
        $acl = new SimpleAcl(SimpleAcl::BLACKLIST);
        $acl->addResource("FooResource")
            ->addPermission("foo")
            ->addPermission("bar")
            ->addPermission("moz");
        $acl->addResource("BarResource", "FooResource")
            ->addPermission("loz")
            ->addPermission("kek");
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->any())->method("getAttribute")->with(SimpleAcl::USER_ATTRIBUTE)->will($this->returnValue(null));
        
        $this->assertTrue($acl->isAllowed($user, "BarResource", "moz"));
        $this->assertFalse($acl->isAllowed($user, "BarResource", "moz", function(UserInterface $user): bool {
            return true;
        }));
        
        // Whitelist
        $acl = new SimpleAcl();
        $acl->addResource("FooResource")
            ->addPermission("foo")
            ->addPermission("bar")
            ->addPermission("moz")
            ->wrapProcessor("FooProcessor")
                ->addEntry("FooEntry", ["foo", "bar"])
            ->endWrapProcessor();
        $acl->addResource("BarResource", "FooResource")
            ->addPermission("loz")
            ->addPermission("kek");
        $acl->registerProcessor("ROOTPROCESSOR", function(UserInterface $user): void {
            $this->grant("NOTFOUND");
            $this->deny("NOTFOUND");
            
            if($user->getName() === "ROOTUSER" && $this->getBehaviour() === SimpleAcl::WHITELIST) {
                $this->grant("ROOT");
                $this->lock();
                return;
            }
            
            $this->grant("ROOT");
            
            if($user->getName() === "TODENYUSER") {
                $this->deny("ROOT");
                $this->lock();
            }
            
            $this->grant("ROOT");
        });
        $acl->registerProcessor("FooProcessor", function(UserInterface $user): void {
            $this->deny("ROOT");
            $this->grant("FooEntry");  
        });
        
        // Resource setted into attribute
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(5))
            ->method("getAttribute")
            ->with(SimpleAcl::USER_ATTRIBUTE)
            ->will($this->onConsecutiveCalls(
                ["<FooResource>"    =>  3],
                ["FooResource"      =>  0],
                ["BarResource"      =>  3],
                ["BarResource"      =>  3],
                ["BarResource"      =>  3]
        ));
        
        $bindable = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $bindable->expects($this->exactly(3))->method("getAclResourceName")->will($this->returnValue("BarResource"));
        $bindable->expects($this->once())->method("updateAclPermission")->with($user, "moz")->will($this->returnValue(true));
        $this->assertNull($acl->pipeline());
        $this->assertFalse($acl->isAllowed($user, "FooResource", "moz", function(UserInterface $user): bool {
            return true;
        }));
        $this->assertTrue($acl->isAllowed($user, "FooResource", "moz", function(UserInterface $user): bool {
            return true;
        }));
        $this->assertTrue($acl->isAllowed($user, $bindable, "foo"));
        $this->assertTrue($acl->isAllowed($user, $bindable, "foo"));
        $this->assertTrue($acl->isAllowed($user, $bindable, "moz"));
        $this->assertNull($acl->endPipeline());
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(3))
            ->method("getAttribute")
            ->will($this->onConsecutiveCalls(
                null, 
                [],
                ["<FooResource>" => 7]
        ));
        $user
            ->expects($this->exactly(3))
            ->method("addAttribute")
            ->withConsecutive(
                [SimpleAcl::USER_ATTRIBUTE, []],
                [SimpleAcl::USER_ATTRIBUTE, ["<FooResource>" => 7]],
                [SimpleAcl::USER_ATTRIBUTE, ["<FooResource>" => 7, "<BarResource>" => 31]]
            );
        $user->expects($this->exactly(2))->method("getName")->will($this->returnValue("ROOTUSER"));
        
        $this->assertTrue($acl->isAllowed($user, "FooResource", "foo"));
        $this->assertTrue($acl->isAllowed($user, "BarResource", "foo"));
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(3))
            ->method("getAttribute")
            ->will($this->onConsecutiveCalls(
                null,
                [],
                ["<FooResource>" => 0]
        ));
        $user
            ->expects($this->exactly(3))
            ->method("addAttribute")
            ->withConsecutive(
                [SimpleAcl::USER_ATTRIBUTE, []],
                [SimpleAcl::USER_ATTRIBUTE, ["<FooResource>" => 0]],
                [SimpleAcl::USER_ATTRIBUTE, ["<FooResource>" => 0, "<BarResource>" => 0]]
        );
        
        $user->expects($this->exactly(4))->method("getName")->will($this->returnValue("TODENYUSER"));
        
        $this->assertFalse($acl->isAllowed($user, "FooResource", "bar"));
        $this->assertFalse($acl->isAllowed($user, "BarResource", "loz"));
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(4))
            ->method("getAttribute")
            ->will($this->onConsecutiveCalls(
                null,
                [],
                ["FooResource" => 3],
                ["FooResource" => 3, "BarResource" => 3]
        ));
        $user
            ->expects($this->exactly(3))
            ->method("addAttribute")
            ->withConsecutive(
                [SimpleAcl::USER_ATTRIBUTE, []],
                [SimpleAcl::USER_ATTRIBUTE, ["FooResource" => 3]],
                [SimpleAcl::USER_ATTRIBUTE, ["FooResource" => 3, "BarResource" => 3]]
        );
        
        $user->expects($this->exactly(4))->method("getName")->will($this->returnValue("LAMBDAUSER"));
        
        $this->assertTrue($acl->isAllowed($user, "FooResource", "foo"));
        $this->assertTrue($acl->isAllowed($user, "BarResource", "bar"));
        $this->assertFalse($acl->isAllowed($user, "BarResource", "loz"));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::cache()
     */
    public function testCache(): void
    {
        if(!\interface_exists("Psr\SimpleCache\CacheInterface"))
            self::markTestSkipped("PSR-16 Not installed");

        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        
        $acl = new SimpleAcl();
        
        $acl->addResource("Foo")->addPermission("foo")->addPermission("bar")->end();
        $acl->addResource("Bar", "Foo")->addPermission("moz")->addPermission("poz")->end();
        
        $cache->expects($this->once())->method("set")->with(SimpleAcl::CACHE_KEY, \json_encode([
            "map"       =>  $this->extractAclProperty($acl, "acl"),
            "behaviour" =>  $this->extractAclProperty($acl, "behaviour")
        ]))->will($this->returnValue(true));
        
        $this->assertTrue($acl->cache($cache));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildFromCache()
     */
    public function testBuildFromCache(): void
    {
        if(!\interface_exists("Psr\SimpleCache\CacheInterface"))
            self::markTestSkipped("PSR-16 Not installed");
        
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        
        $acl = new SimpleAcl();
        
        $acl->addResource("Foo")->addPermission("foo")->addPermission("bar")->end();
        $acl->addResource("Bar", "Foo")->addPermission("moz")->addPermission("poz")->wrapProcessor("FooProcessor")->addEntry("Foo", ["foo", "bar"])->endWrapProcessor()->end();
        
        $json = \json_encode([
            "map"       =>  $this->extractAclProperty($acl, "acl"),
            "behaviour" =>  $this->extractAclProperty($acl, "behaviour")
        ]);
        
        $cache->expects($this->exactly(2))->method("get")->withConsecutive([SimpleAcl::CACHE_KEY])->will($this->onConsecutiveCalls(null, $json));
        
        $newAcl = new SimpleAcl();
        
        $this->assertFalse($newAcl->buildFromCache($cache));
        $this->assertTrue($newAcl->buildFromCache($cache));
        
        $this->assertEquals($newAcl, $acl);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::invalidateCache()
     */
    public function testInvalidateCache(): void
    {
        if(!\interface_exists("Psr\SimpleCache\CacheInterface"))
            self::markTestSkipped("PSR-16 Not installed");
            
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $cache->expects($this->once())->method("delete")->with(SimpleAcl::CACHE_KEY);
        
        $acl = new SimpleAcl();
        
        $this->assertNull($acl->invalidateCache($cache));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildFromFile()
     */
    public function testBuildFromFile(): void
    {
        $files = [
            __DIR__."/Fixtures/SimpleAcl/acl1.php",
            __DIR__."/Fixtures/SimpleAcl/Directory"
        ];
        
        $acl = new SimpleAcl();
        
        $this->assertNull($acl->buildFromFiles($files));
        $this->assertSame([
            "FooResource"   =>  ["name" => "FooResource", "permissions" => ["foo" => 1, "bar" => 2], "entries" => ["ROOT" => 3, "FooEntry" => 3], "behaviour" => 1, "processors" => ["FooProcessor" => ["FooEntry"]], "parent" => null, "root" => 3],
            "BarResource"   =>  ["name" => "BarResource", "permissions" => ["moz" => 4, "poz" => 8], "entries" => ["ROOT" => 15, "FooEntry" => 15], "behaviour" => 1, "processors" => null, "parent" => "FooResource", "root" => 15],
            "MozResource"   =>  ["name" => "MozResource", "permissions" => null, "entries" => null, "behaviour" => 1, "processors" => null, "parent" => "BarResource", "root" => 15]
        ], $this->extractAclProperty($acl, "acl"));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::changeBehaviour()
     */
    public function testChangeBehaviour(): void
    {
        $acl = new SimpleAcl();
        
        $this->assertNull($acl->changeBehaviour(SimpleAcl::BLACKLIST));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addProcessor()
     */
    public function testAddProcessor(): void
    {
        $acl = new SimpleAcl();
        
        $processor = function(): void {};
        
        $this->assertNull($acl->registerProcessor("foo", $processor));
        
        $this->assertSame(["foo" => $processor], $this->extractAclProperty($acl, "processors"));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addResource()
     * @see \Ness\Component\Acl\SimpleAcl::end()
     */
    public function testAddResourceAndEnd(): void
    {
        $acl = new SimpleAcl();
        
        $this->assertSame($acl, $acl->addResource("Foo"));
        $this->assertSame($acl, $acl->addResource("Bar", "Foo"));
        
        $this->assertSame([
            "Foo"   =>  ["name" => "Foo", "permissions" => null, "entries" => null, "behaviour" => 1, "processors" => null, "parent" => null, "root" => 0],
            "Bar"   =>  ["name" => "Bar", "permissions" => null, "entries" => null, "behaviour" => 1, "processors" => null, "parent" => "Foo", "root" => 0]
        ], $this->extractAclProperty($acl, "acl"));
        $this->assertSame("Bar", $this->extractAclProperty($acl, "currentResource"));
        $this->assertNull($acl->end());
        $this->assertNull($this->extractAclProperty($acl, "currentResource"));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addPermissions()
     */
    public function testAddPermission(): void
    {
        $acl = new SimpleAcl(SimpleAcl::BLACKLIST);
        
        $this->assertSame($acl, $acl->addResource("Bar"));
        $this->assertSame($acl, $acl
                                ->addResource("Foo")
                                ->addPermission("foo")
                                ->addPermission("bar", "Bar")
                                ->addPermission("bar")
                                ->addPermission("moz"));
        $this->assertSame($acl, $acl->addResource("Moz", "Foo")->addPermission("poz"));
        
        $this->assertSame([
            "Bar"   =>  ["name" => "Bar", "permissions" => ["bar" => 1], "entries" => ["ROOT" => 1], "behaviour" => 0, "processors" => null, "parent" => null, "root" => 1],
            "Foo"   =>  ["name" => "Foo", "permissions" => ["foo" => 1, "bar" => 2, "moz" => 4], "entries" => ["ROOT" => 7], "behaviour" => 0, "processors" => null, "parent" => null, "root" => 7],
            "Moz"   =>  ["name" => "Moz", "permissions" => ["poz" => 8], "entries" => ["ROOT" => 15], "behaviour" => 0, "processors" => null, "parent" => "Foo", "root" => 15]
        ], $this->extractAclProperty($acl, "acl"));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::wrapProcessor()
     * @see \Ness\Component\Acl\SimpleAcl::endWrapProcessor()
     */
    public function testWrapProcessorAndEndWrapProcessor(): void
    {
        $acl = new SimpleAcl();
        
        $this->assertSame($acl, $acl->wrapProcessor("Foo"));
        $this->assertSame("Foo", $this->extractAclProperty($acl, "currentWrapProcessor"));
        $this->assertSame($acl, $acl->endWrapProcessor());
        $this->assertNull($this->extractAclProperty($acl, "currentWrapProcessor"));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addEntry()
     */
    public function testAddEntry(): void
    {
        $acl = new SimpleAcl();
        
        $acl->addResource("Foo")
            ->addPermission("foo")
            ->addPermission("bar")
            ->wrapProcessor("FooProcessor")
                ->addEntry("FooEntry", ["foo", "bar"])
                ->addEntry("BarEntry", ["foo"])
            ->endWrapProcessor()
        ->end();
        $acl->addResource("Bar", "Foo")
            ->addPermission("moz")
            ->addPermission("poz")
                ->addEntry("BarEntry", ["moz", "poz", "foo", "bar"])
        ->end();
        $acl->addResource("Moz", "Bar")
            ->addPermission("loz")
            ->addPermission("kek")
                ->addEntry("MozEntry", ["BarEntry", "loz", "kek"])
        ->end();
        
        $this->assertSame([
            "Foo"   =>  ["name" => "Foo", "permissions" => ["foo" => 1, "bar" => 2], "entries" => ["ROOT" => 3, "FooEntry" => 3, "BarEntry" => 1], "behaviour" => 1, "processors" => ["FooProcessor" => ["FooEntry", "BarEntry"]], "parent" => null, "root" => 3],
            "Bar"   =>  ["name" => "Bar", "permissions" => ["moz" => 4, "poz" => 8], "entries" => ["ROOT" => 15, "BarEntry" => 15], "behaviour" => 1, "processors" => null, "parent" => "Foo", "root" => 15],
            "Moz"   =>  ["name" => "Moz", "permissions" => ["loz" => 16, "kek" => 32], "entries" => ["ROOT" => 63, "MozEntry" => 63], "behaviour" => 1, "processors" => null, "parent" => "Bar", "root" => 63],
        ], $this->extractAclProperty($acl, "acl"));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildMaskRepresentation()
     */
    public function testBuildMaskRepresentation(): void
    {
        $acl = new SimpleAcl();
        $acl->addResource("Foo")->addPermission("foo")->addPermission("bar")->end();
        $acl->addResource("Bar", "Foo")->addPermission("moz")->addPermission("poz")->addEntry("FooEntry", ["foo", "bar", "moz"])->end();
        
        $expected = [
            "<span style=\"color:green\">foo</span>|<span style=\"color:red\">bar</span>|<span style=\"color:green\">moz</span>|<span style=\"color:red\">poz</span>|",
            "<span style=\"color:green\">foo</span>|<span style=\"color:red\">bar</span>|",
            "foo+bar+moz-poz+",
            "foo+bar+moz+poz-",
        ];
        
        $this->assertSame($expected[0], SimpleAcl::buildMaskRepresentation($acl, "Bar", 0b0101));
        $this->assertSame($expected[1], SimpleAcl::buildMaskRepresentation($acl, "Foo", 0b1101));
        $this->assertSame($expected[2], SimpleAcl::buildMaskRepresentation($acl, "Bar", 0b1011, function(string& $representation, string $permission, bool $granted): void {
            $representation .= $permission .= ($granted) ? "+" : "-"; 
        }));
        $this->assertSame($expected[3], SimpleAcl::buildMaskRepresentation($acl, "Bar", "FooEntry", function(string& $representation, string $permission, bool $granted): void {
            $representation .= $permission .= ($granted) ? "+" : "-";
        }));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::__construct()
     */
    public function testExceptionWhenBehaviourIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Acl behaviour MUST be one of the value determined into the acl");
        
        $acl = new SimpleAcl(3);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     */
    public function testExceptionIsAllowedWhenGivenResourceIsNeitherAStringOrABindable(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Resource MUST be a string or an implementation of AclBindableInterface. 'array' given");
        
        $acl = new SimpleAcl();
        $acl->isAllowed($this->getMockBuilder(UserInterface::class)->getMock(), [], "Foo");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     */
    public function testExceptionIsAllowedWhenGivenPermissionIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Resource name 'Foo+' is invalid");
        
        $acl = new SimpleAcl();
        $acl->isAllowed($this->getMockBuilder(UserInterface::class)->getMock(), "Foo+", "Foo");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     */
    public function testExceptionIsAllowedWhenResourceIsNotRegistered(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource 'Foo' is not registered into the acl");
        
        $acl = new SimpleAcl();
        $acl->isAllowed($this->getMockBuilder(UserInterface::class)->getMock(), "Foo", "Foo");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildFromFiles()
     */
    public function testExceptionBuildFromFilesWhenAnInvalidFileIsGiven(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This file 'Foo' is neither a valid file or directory");
        
        $acl = new SimpleAcl();
        $acl->buildFromFiles(["Foo"]);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::changeBehaviour()
     */
    public function testExceptionChangerBehaviourWhenBehaviourIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Acl behaviour MUST be one of the value determined into the acl");
        
        $acl = new SimpleAcl();
        
        $acl->changeBehaviour(3);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addResource()
     */
    public function testExceptionAddResourceWhenTheResourceIsAlreadyRegistered(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This resource 'Foo' is already registered into the acl");
        
        $acl = new SimpleAcl();
        
        $acl->addResource("Foo")->addResource("Foo");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addResource()
     */
    public function testExceptionAddResourceWhenParentResourceIsNotRegistered(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource 'Bar' given as parent is not registered into the acl");
        
        $acl = new SimpleAcl();
        
        $acl->addResource("Foo", "Bar");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addPermission()
     */
    public function testExceptionAddPermissionWhenPermissionIsAlreadyRegistered(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This permission 'foo' is already setted into resource 'Foo'");
        
        $acl = new SimpleAcl();
        
        $acl->addResource("Bar")->addPermission("foo")->end();
        $acl->addResource("Foo")->addPermission("foo")->addPermission("foo");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addPermission()
     */
    public function testExceptionAddPermissionWhenPermissionIsAlreadyRegisteredIntoAParentResource(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This permission 'foo' is already registered into parent resource 'Foo' and cannot be redeclared into resource 'Bar'");
        
        $acl = new SimpleAcl();
        
        $acl->addResource("Foo")->addPermission("foo")->end();
        $acl->addResource("Bar", "Foo")->addPermission("foo")->end();
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addPermission()
     */
    public function testExceptionAddPermissionWhenNotResourceIsSetted(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("No resource has been declared to register permission or entries");
        
        $acl = new SimpleAcl();
        
        $acl->addPermission("foo");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addPermission()
     */
    public function testExceptionAddPermissionWhenResourceIsNotRegistered(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource 'Foo' is not registered into the acl");
        
        $acl = new SimpleAcl();
        
        $acl->addPermission("foo", "Foo");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addPermission()
     */
    public function testExceptionAddPermissionWhenLimitIsReached(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Max permission allowed reached for resource 'Foo'");
        
        $acl = new SimpleAcl();
        $acl->addResource("Foo");
        for ($i = 0; $i < 32; $i++) {
            $acl->addPermission("foo{$i}");
        }
    }
    
    public function testExceptionAddEntryWhenEntryNameIsRoot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("ROOT entry name is reserved and cannot be reassigned into resource 'Foo'");
        
        $acl = new SimpleAcl();
        
        $acl->addResource("Foo")->addEntry("ROOT", []);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildMaskRepresentation()
     */
    public function testExceptionBuildMaskRepresentationWhenMaskIsNeitherAStringOrAnInt(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Mask MUST be an int or a string");

        $acl = new SimpleAcl();
        
        SimpleAcl::buildMaskRepresentation($acl, "Foo", []);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildMaskRepresentation()
     */
    public function testExceptionBuildMaskRepresentationWhenResourceIsNotFound(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource 'Foo' is not registered into the acl");
        
        $acl = new SimpleAcl();
        
        SimpleAcl::buildMaskRepresentation($acl, "Foo", 0b0000);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildMaskRepresentation()
     */
    public function testExceptionBuildMaskRepresentationWhenMaskGivenIsNotARegisteredEntryAllParent(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This entry 'FooEntry' is not registred into resource 'MozResource' neither into one of its parent 'FooResource, BarResource'");
        
        $acl = new SimpleAcl();
        $acl->addResource("FooResource")->addResource("BarResource", "FooResource")->addResource("MozResource", "BarResource");
        
        SimpleAcl::buildMaskRepresentation($acl, "MozResource", "FooEntry");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildMaskRepresentation()
     */
    public function testExceptionBuildMaskRepresentationWhenMaskGivenIsNotARegisteredEntry(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This entry 'FooEntry' is not registered into resource 'FooResource'");
        
        $acl = new SimpleAcl();
        $acl->addResource("FooResource");
        
        SimpleAcl::buildMaskRepresentation($acl, "FooResource", "FooEntry");
    }
    
    /**
     * Extract a property from a SimpleAcl instace for comparaison
     * 
     * @param SimpleAcl $acl
     *   Acl instance
     * @param string $property
     *   Property to extract
     * 
     * @return mixed
     *   Current property value
     */
    private function extractAclProperty(SimpleAcl $acl, string $property)
    {
        $reflection = new \ReflectionClass($acl);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        
        return $property->getValue($acl);
    }
    
}
