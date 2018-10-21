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

use PHPUnit\Framework\TestCase;
use Ness\Component\Acl\SimpleAcl;
use Ness\Component\Acl\Exception\InvalidArgumentException;
use Ness\Component\Acl\Exception\ResourceNotFoundException;
use Ness\Component\Acl\Exception\EntryNotFoundException;
use Ness\Component\User\UserInterface;
use Ness\Component\Acl\Exception\PermissionNotFoundException;
use Ness\Component\Acl\AclBindableInterface;
use Psr\SimpleCache\CacheInterface;
use Ness\Component\Acl\Normalizer\LockPatternNormalizerInterface;

/**
 * SimpleAcl testcase
 * 
 * @see \Ness\Component\Acl\SimpleAcl
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class SimpleAclTest extends TestCase
{
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     * @see \Ness\Component\Acl\SimpleAcl::multi()
     * @see \Ness\Component\Acl\SimpleAcl::clearMulti()
     */
    public function testIsAllowedWithUserWithLockedResource(): void
    {
        $normalizer = $this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock();
        $normalizer
            ->expects($this->exactly(5))
            ->method("apply")
            ->withConsecutive(
                ["FooResource"],
                ["FooResource"],
                ["FooResource"],
                ["BarResource"],
                ["BarResource"]
            )
            ->will($this->onConsecutiveCalls("::FooResource::", "::FooResource::", "::FooResource::", "::BarResource::", "::BarResource::"));
            
        $barResource = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $barResource->expects($this->exactly(2))->method("getAclResourceName")->will($this->returnValue("BarResource"));
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(5))
            ->method("getAttribute")
            ->with(SimpleAcl::ACL_USER_ATTRIBUTE)
            ->will($this->returnValue(["::FooResource::" => 3, "::BarResource::" => 7]));
        
        $acl = new SimpleAcl($normalizer);
        $acl->addResource("FooResource")->addPermission("foo")->addPermission("bar")->addPermission("moz")->endResource();
        $acl->addResource("BarResource", "FooResource")->addPermission("loz")->endResource();
        
        $this->assertNull($acl->multi());
        $this->assertTrue($acl->isAllowed($user, "FooResource", "foo", function(): bool {
            return false;
        }));
        $this->assertTrue($acl->isAllowed($user, "FooResource", "bar"));
        $this->assertFalse($acl->isAllowed($user, "FooResource", "moz"));
        $this->assertTrue($acl->isAllowed($user, $barResource, "moz"));
        $this->assertFalse($acl->isAllowed($user, $barResource, "loz"));
        $this->assertNull($acl->clearMulti());
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     */
    public function testIsAllowedWithUserWithNoAttribute(): void
    {
        $normalizer = $this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock();
        $normalizer->expects($this
            ->exactly(3))
            ->method("apply")
            ->withConsecutive(["FooResource"], ["BarResource"], ["MozResource"])
            ->will($this->onConsecutiveCalls("::FooResource::", "::BarResource::", "::MozResource::"));
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(3))
            ->method("getAttribute")
            ->withConsecutive([SimpleAcl::ACL_USER_ATTRIBUTE])
            ->will($this->onConsecutiveCalls(null, ["FooResource" => 15], ["FooResource" => 15, "BarResource" => 31]));
        $user->expects($this->exactly(4))->method("addAttribute")->withConsecutive(
            [SimpleAcl::ACL_USER_ATTRIBUTE, []],
            [SimpleAcl::ACL_USER_ATTRIBUTE, ["FooResource" => 15]],
            [SimpleAcl::ACL_USER_ATTRIBUTE, ["FooResource" => 15, "BarResource" => 31]],
            [SimpleAcl::ACL_USER_ATTRIBUTE, ["FooResource" => 15, "BarResource" => 31, "MozResource" => 0]]
            );
        
        $acl = new SimpleAcl($normalizer);
        $acl->changeBehaviour(SimpleAcl::BLACKLIST);
        $acl->addResource("FooResource")->addPermission("foo")->addPermission("bar")->addPermission("moz")->addPermission("poz")->endResource();
        $acl->addResource("BarResource", "FooResource")->addPermission("loz")->endResource();
        $acl->changeBehaviour(SimpleAcl::WHITELIST);
        $acl->addResource("MozResource")->addPermission("foo")->endResource();
        
        $this->assertTrue($acl->isAllowed($user, "FooResource", "foo"));
        $this->assertTrue($acl->isAllowed($user, "BarResource", "loz"));
        $this->assertFalse($acl->isAllowed($user, "MozResource", "foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     */
    public function testIsAllowedWithUserWithNoAttributeAndAProcessorLockingThePermission(): void
    {
        $normalizer = $this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock();
        $normalizer->expects($this
            ->exactly(6))
            ->method("apply")
            ->withConsecutive(["FooResource"], ["FooResource"], ["BarResource"], ["BarResource"], ["MozResource"], ["MozResource"])
            ->will($this->onConsecutiveCalls("::FooResource::", "::FooResource::", "::BarResource::", "::BarResource::", "::MozResource::", "::MozResource::"));
        $barResource = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $barResource->expects($this->once())->method("getAclResourceName")->will($this->returnValue("BarResource"));
        $barResource->expects($this->never())->method("updateAclPermission");
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects($this->exactly(3))
            ->method("getAttribute")
            ->withConsecutive([SimpleAcl::ACL_USER_ATTRIBUTE])
            ->will($this->onConsecutiveCalls(null, ["::FooResource::" => 15], ["::FooResource::" => 15, "::BarResource::" => 15]));
        $user
            ->expects($this->exactly(4))
            ->method("addAttribute")
            ->withConsecutive(
                [SimpleAcl::ACL_USER_ATTRIBUTE, []],
                [SimpleAcl::ACL_USER_ATTRIBUTE, ["::FooResource::"    => 15]],
                [SimpleAcl::ACL_USER_ATTRIBUTE, ["::FooResource::"    => 15, "::BarResource::"    => 15]],
                [SimpleAcl::ACL_USER_ATTRIBUTE, ["::FooResource::"    => 15, "::BarResource::"    => 15, "::MozResource::" => 2]]
            );
        
        $processor = function(UserInterface $user): void {
            if($this->getBehaviour() === SimpleAcl::WHITELIST)
                $this->grant("ROOT");
            else 
                $this->deny("foo");
            $this->lock();
        };
        
        $acl = new SimpleAcl($normalizer);
        $acl->addResource("FooResource")->addPermission("foo")->addPermission("bar")->addPermission("moz")->addPermission("poz")->endResource();
        $acl->addResource("BarResource", "FooResource")->endResource();
        $acl->changeBehaviour(SimpleAcl::BLACKLIST);
        $acl->addResource("MozResource")->addPermission("foo")->addPermission("bar")->endResource();
        $acl->registerProcessor("FooProcessor", $processor);
        
        $this->assertTrue($acl->isAllowed($user, "FooResource", "bar", function() {
            return false;
        }));
        $this->assertTrue($acl->isAllowed($user, $barResource, "moz"));
        $this->assertFalse($acl->isAllowed($user, "MozResource", "foo"));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     */
    public function testIsAllowedWithAclBindableActingOnResourceBlacklist(): void
    {
        $normalizer = $this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock();
        $normalizer->expects($this->exactly(5))->method("apply")->with("FooResource")->will($this->returnValue("::FooResource::"));
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(5))->method("getAttribute")->with(SimpleAcl::ACL_USER_ATTRIBUTE)->will($this->returnValue(["FooResource" => 7]));
        
        $resource = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $resource->expects($this->exactly(5))->method("getAclResourceName")->will($this->returnValue("FooResource"));
        $resource->expects($this->exactly(4))->method("updateAclPermission")->withConsecutive([$user, "foo", true])->will($this->onConsecutiveCalls(null, true, false, true));
        
        $acl = new SimpleAcl($normalizer, SimpleAcl::BLACKLIST);
        $acl->addResource("FooResource")->addPermission("foo")->addPermission("bar")->addPermission("moz")->endResource();
        
        $this->assertTrue($acl->isAllowed($user, $resource, "foo"));
        $this->assertFalse($acl->isAllowed($user, $resource, "foo"));
        $this->assertTrue($acl->isAllowed($user, $resource, "foo"));
        $this->assertFalse($acl->isAllowed($user, $resource, "foo", function(UserInterface $user, AclBindableInterface $bindable) use ($resource): ?bool {
            $this->assertSame($bindable, $resource);
            return true;
        }));
        $this->assertFalse($acl->isAllowed($user, $resource, "foo", function(UserInterface $user, AclBindableInterface $resource): ?bool {
            return null;
        }));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     */
    public function testIsAllowedWithAclBindableActingOnResourceWhitelist(): void
    {
        $normalizer = $this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock();
        $normalizer->expects($this->exactly(5))->method("apply")->with("FooResource")->will($this->returnValue("::FooResource::"));
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->exactly(5))->method("getAttribute")->with(SimpleAcl::ACL_USER_ATTRIBUTE)->will($this->returnValue(["FooResource" => 0]));
        
        $resource = $this->getMockBuilder(AclBindableInterface::class)->getMock();
        $resource->expects($this->exactly(5))->method("getAclResourceName")->will($this->returnValue("FooResource"));
        $resource->expects($this->exactly(4))->method("updateAclPermission")->withConsecutive([$user, "foo", false])->will($this->onConsecutiveCalls(null, true, false, true));
        
        $acl = new SimpleAcl($normalizer);
        $acl->addResource("FooResource")->addPermission("foo")->addPermission("bar")->addPermission("moz")->endResource();

        $this->assertFalse($acl->isAllowed($user, $resource, "foo"));
        $this->assertTrue($acl->isAllowed($user, $resource, "foo"));
        $this->assertFalse($acl->isAllowed($user, $resource, "foo"));
        $this->assertTrue($acl->isAllowed($user, $resource, "foo", function(UserInterface $user, AclBindableInterface $bindable) use ($resource): ?bool {
            $this->assertSame($resource, $bindable);
            return true;
        }));
        $this->assertTrue($acl->isAllowed($user, $resource, "foo", function(UserInterface $user, AclBindableInterface $resource): ?bool {
            return null;  
        }));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     * @see \Ness\Component\Acl\SimpleAcl::getProcessables()
     */
    public function testIsAllowedExtractProcessablesIntoProcessor(): void
    {
        $ref = null;
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl
        ->addResource("FooResource")
            ->addPermission("foo")
            ->addPermission("bar")
            ->wrapProcessor("FooProcessor")
                ->addEntry("FooEntry", ["foo"])
                ->addEntry("BarEntry", ["bar"])
            ->endProcessor()
            ->addEntry("FooEntry", ["bar"])
        ->endResource();
        $acl
        ->addResource("BarResource", "FooResource")
            ->addPermission("moz")
            ->addPermission("poz")
            ->wrapProcessor("FooProcessor")
                ->addEntry("FooEntry", ["FooEntry", "moz"])
                ->addEntry("BarEntry", ["BarEntry", "poz"])
            ->endProcessor()
            ->addEntry("FooEntry", ["moz", "poz"])
        ->endResource();
        
        $processor = function(UserInterface $user) use (&$ref) {
            $ref = $this->getEntries();
        };
        $acl->registerProcessor("FooProcessor", $processor);
        
        $this->assertFalse($acl->isAllowed($this->getMockBuilder(UserInterface::class)->getMock(), "FooResource", "foo"));
        $this->assertSame([
            "FooEntry"  =>  1,
            "BarEntry"  =>  2
        ], $ref);
        $this->assertFalse($acl->isAllowed($this->getMockBuilder(UserInterface::class)->getMock(), "BarResource", "foo"));
        $this->assertSame([
            "FooEntry"  =>  1|4,
            "BarEntry"  =>  2|8
        ], $ref);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::cache()
     */
    public function testCache(): void
    {
        if(!\interface_exists("Psr\SimpleCache\CacheInterface"))
            self::markTestSkipped("PSR-16 not found. Test skipped");
        
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->addResource("FooResource")->addPermission("foo")->addPermission("bar")->wrapProcessor("FooProcessor")->addEntry("FooEntry", ["foo", "bar"])->endProcessor()->addEntry("FooEntry", ["foo"])->endResource();
        $acl->addResource("BarResource", "FooResource")->endResource();
        $acl->changeBehaviour(SimpleAcl::BLACKLIST);
        $acl->addResource("MozResource")->addPermission("foo")->addPermission("bar")->endResource();
        $acl->changeBehaviour(SimpleAcl::WHITELIST);
        
        $setToCache = \json_encode($this->extractProperties($acl, ["acl", "behaviour"]));
        $cache->expects($this->once())->method("set")->with(SimpleAcl::CACHE_IDENTIFIER, $setToCache)->will($this->returnValue(true));
        
        $this->assertTrue($acl->cache($cache));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildFromCache()
     */
    public function testBuildFromCache(): void
    {
        if(!\interface_exists("Psr\SimpleCache\CacheInterface"))
            self::markTestSkipped("PSR-16 not found. Test skipped");
        
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->addResource("FooResource")->addPermission("foo")->addPermission("bar")->wrapProcessor("FooProcessor")->addEntry("FooEntry", ["foo", "bar"])->endProcessor()->addEntry("FooEntry", ["foo"])->endResource();
        $acl->addResource("BarResource", "FooResource")->endResource();
        $acl->changeBehaviour(SimpleAcl::BLACKLIST);
        $acl->addResource("MozResource")->addPermission("foo")->addPermission("bar")->endResource();
        $acl->changeBehaviour(SimpleAcl::WHITELIST);
        
        $cachedAcl = \json_encode($this->extractProperties($acl, ["acl", "behaviour"]));
        
        $expected = $acl;
        
        $cache->expects($this->exactly(2))->method("get")->with(SimpleAcl::CACHE_IDENTIFIER)->will($this->onConsecutiveCalls(null, $cachedAcl));
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());

        $this->assertNull($this->extractProperties($acl, ["acl"])["acl"]);
        $this->assertFalse($acl->buildFromCache($cache));
        $this->assertTrue($acl->buildFromCache($cache));
        $this->assertEquals($expected, $acl);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildFromFiles()
     */
    public function testBuildFromFiles(): void
    {
        $baseDir = __DIR__."/Fixtures/SimpleAcl/Valid";
        $files = [
            $baseDir."/Acl1.php",
            $baseDir."/Directory"
        ];
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        
        $this->assertNull($acl->buildFromFiles($files));
        $properties = $this->extractProperties($acl, ["behaviour", "acl", "currentProcessor", "currentResource"]);
        $this->assertNull($properties["currentResource"]);
        $this->assertNull($properties["currentProcessor"]);
        $this->assertSame(SimpleAcl::WHITELIST, $properties["behaviour"]);
        $this->assertSame([
            "MozResource"   =>  ["name" => "MozResource", "permissions" => ["foo" => 1, "bar" => 2], "entries" => ["ROOT" => 3], "parent" => null, "behaviour" => 0],
            "FooResource"   =>  ["name" => "FooResource", "permissions" => ["foo" => 1, "bar" => 2, "moz" => 4, "poz" => 8], "entries" => ["ROOT" => 15, "processed_entries" => ["FooProcessor" => ["FooEntry" => 1, "BarEntry" => 1|2]]], "parent" => null, "behaviour" => 1],
            "BarResource"   =>  ["name" => "BarResource", "permissions" => ["loz" => 16, "kek" => 32], "entries" => ["ROOT" => 63, "global_entries" => ["FooEntry" => 16|32], "processed_entries" => ["FooProcessor" => ["FooEntry" => 1|32, "BarEntry" => 1|2|16]]], "parent" => "FooResource", "behaviour" => 1]
        ], $properties["acl"]);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::registerProcessor()
     */
    public function testRegisterProcessor(): void
    {
        $processor = function(UserInterface $user): void {
            
        };
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $this->assertNull($acl->registerProcessor("FooProcessor", $processor));
        
        $processors = $this->extractProperties($acl, ["processors"])["processors"];
        
        $this->assertSame(["FooProcessor" => $processor], $processors);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::changeBehaviour()
     */
    public function testChangeBehaviour(): void
    {
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        
        $this->assertNull($acl->changeBehaviour(SimpleAcl::BLACKLIST));
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addResource()
     * @see \Ness\Component\Acl\SimpleAcl::endResource()
     */
    public function testAddResource(): void
    {
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        
        $this->assertSame($acl, $acl->addResource("FooResource"));
        $current = $this->extractProperties($acl, ["currentResource"])["currentResource"];
        $this->assertSame("FooResource", $current);
        $acl->endResource();
        $current = $this->extractProperties($acl, ["currentResource"])["currentResource"];
        $this->assertNull($current);
        $acl->changeBehaviour(SimpleAcl::BLACKLIST);
        $this->assertSame($acl, $acl->addResource("BarResource", "FooResource"));
        $current = $this->extractProperties($acl, ["currentResource"])["currentResource"];
        $this->assertSame("BarResource", $current);
        $acl->endResource();
        $acl->addResource("MozResource")->endResource();
        
        $acl = $this->extractProperties($acl, ["acl"])["acl"];
        
        $this->assertSame([
            "FooResource" => ["name" => "FooResource", "permissions" => null, "entries" => ["ROOT" => 0], "parent" => null, "behaviour" => 1],
            "BarResource" => ["name" => "BarResource", "permissions" => null, "entries" => ["ROOT" => 0], "parent" => "FooResource", "behaviour" => 1],
            "MozResource" => ["name" => "MozResource", "permissions" => null, "entries" => ["ROOT" => 0], "parent" => null, "behaviour" => 0]
        ], $acl);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addPermission()
     */
    public function testAddPermission(): void
    {
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        
        $acl->addResource("FooResource")->addPermission("foo")->addPermission("bar")->endResource();
        $acl->addResource("BarResource", "FooResource")->addPermission("moz")->addPermission("poz")->endResource();
        $acl->addResource("MozResource", "BarResource")->addPermission("loz")->addPermission("kek")->endResource();
        
        $acl = $this->extractProperties($acl, ["acl"])["acl"];
        $this->assertSame([
            "FooResource" => 
                ["name" => "FooResource", "permissions" => ["foo" => 1, "bar" => 2], "entries" => ["ROOT" => 3], "parent" => null, "behaviour" => 1],
            "BarResource" => 
                ["name" => "BarResource", "permissions" => ["moz" => 4, "poz" => 8], "entries" => ["ROOT" => 15], "parent" => "FooResource", "behaviour" => 1],
            "MozResource" => 
                ["name" => "MozResource", "permissions" => ["loz" => 16, "kek" => 32], "entries" => ["ROOT" => 63], "parent" => "BarResource", "behaviour" => 1]
        ], $acl);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addEntry()
     */
    public function testAddEntry(): void
    {
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        
        $acl
        ->addResource("FooResource")
            ->addPermission("foo")
            ->addPermission("bar")
            ->addPermission("moz")
            ->addPermission("poz");
        $this->assertSame($acl, $acl->wrapProcessor("FooProcessor"));
        $this->assertSame($acl, $acl->addEntry("FooEntry", ["foo", "poz"]));
        $this->assertSame($acl, $acl->addEntry("BarEntry", ["FooEntry", "bar"]));
        $this->assertSame($acl, $acl->endProcessor());
        $acl->wrapProcessor("BarProcessor")
            ->addEntry("FooEntry", ["foo"])
            ->addEntry("BarEntry", ["ROOT"])
        ->endProcessor();
        $acl->addEntry("FooEntry", ["bar", "moz"]);
        $acl->endResource();
        
        $acl
        ->addResource("BarResource", "FooResource")
            ->addPermission("loz")
            ->addPermission("kek")
            ->addEntry("PozEntry", ["loz", "foo", "kek"])
            ->wrapProcessor("FooProcessor")
                ->addEntry("FooEntry", ["FooEntry", "loz"])
                ->addEntry("MozEntry", ["BarEntry", "kek"])
                ->addEntry("PozEntry", ["PozEntry", "bar"])
            ->endProcessor()
            ->wrapProcessor("BarProcessor")
                ->addEntry("FooEntry", ["FooEntry", "moz", "kek"])
                ->addEntry("BarEntry", ["BarEntry", "kek"])
            ->endProcessor()
            ->addEntry("FooEntry", ["FooEntry", "kek"])
        ->endResource();
        $acl->wrapProcessor("FooProcessor")->addEntry("LozEntry", ["foo", "bar", "kek"], "BarResource")->endProcessor();
        $acl->addEntry("LozEntry", ["foo", "bar"], "FooResource");
        
        $acl = $this->extractProperties($acl, ["acl"])["acl"];
        $this->assertSame([
            "FooResource" =>
                [
                    "name" => "FooResource", 
                    "permissions" => ["foo" => 1, "bar" => 2, "moz" => 4, "poz" => 8], 
                    "entries" => [
                        "ROOT" => 1|2|4|8,
                        "processed_entries" =>  [
                            "FooProcessor"      =>  ["FooEntry" => 1|8, "BarEntry" => 1|8|2],
                            "BarProcessor"      =>  ["FooEntry" => 1, "BarEntry" => 1|2|4|8]
                        ],
                        "global_entries"    =>  [
                            "FooEntry"          =>  2|4,
                            "LozEntry"          =>  1|2
                        ]
                    ], 
                    "parent" => null,
                    "behaviour" => 1
                ],
            "BarResource" =>
                [
                    "name" => "BarResource", 
                    "permissions" => ["loz" => 16, "kek" => 32], 
                    "entries" => [
                        "ROOT" => 1|2|4|8|16|32,
                        "global_entries"    =>  [
                            "PozEntry"          =>  16|1|32,
                            "FooEntry"          =>  2|4|32
                        ],
                        "processed_entries" =>  [
                            "FooProcessor"      =>  ["FooEntry" => 1|8|16, "MozEntry" => 1|8|2|32, "PozEntry" => 16|1|32|2, "LozEntry" => 1|2|32],
                            "BarProcessor"      =>  ["FooEntry" => 1|4|32, "BarEntry" => 1|2|4|8|32]
                        ]
                    ], 
                    "parent" => "FooResource",
                    "behaviour" => 1
                ]
        ], $acl);
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::__construct()
     */
    public function testException__constructWhenBehaviourIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Behaviour is invalid. Use SimpleAcl::WHITELIST or SimpleAcl::BLACKLIST const");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock(), 3);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     */
    public function testExceptionIsAllowedWhenGivenResourceIsNotAValidType(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Resource MUST be a string or an instance of AclBindableInterface. 'array' given");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->isAllowed($this->getMockBuilder(UserInterface::class)->getMock(), [], "foo");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     */
    public function testExceptionIsAllowedWhenGivenResourceIsNotRegisteredIntoTheAcl(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource 'FooResource' is not registered into the acl");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->isAllowed($this->getMockBuilder(UserInterface::class)->getMock(), "FooResource", "foo");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     */
    public function testExceptionIsAllowedWhenPermissionNotFound(): void
    {
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage("This permission 'foo' is not registered into resource 'FooResource'");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->addResource("FooResource")->endResource();
        $acl->isAllowed($this->getMockBuilder(UserInterface::class)->getMock(), "FooResource", "foo");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     */
    public function testExceptionIsAllowedWhenAProcessorIsSettedToStrictAndTryingToGrantAnInvalidPermissionOrEntry(): void
    {
        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage("This entry/permission 'foo' cannot be processed into processor 'FooProcessor'. See message : This entry 'foo' is not registered into resource 'FooResource'");
        
        $processor = function(UserInterface $user): void {
            $this->setToStrict();
            $this->grant("foo");
        };
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->once())->method("getAttribute")->with(SimpleAcl::ACL_USER_ATTRIBUTE)->will($this->returnValue(null));
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->registerProcessor("FooProcessor", $processor);
        $acl->addResource("FooResource")->addPermission("bar")->endResource();
        
        $acl->isAllowed($user, "FooResource", "bar");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::isAllowed()
     */
    public function testExceptionIsAllowedWhenAProcessorIsSettedToStrictAndTryingToDenyAnInvalidPermissionOrEntry(): void
    {
        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage("This entry/permission 'foo' cannot be processed into processor 'FooProcessor'. See message : This entry 'foo' is not registered into resource 'FooResource'");
        
        $processor = function(UserInterface $user): void {
            $this->setToStrict();
            $this->deny("foo");
        };
        
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->expects($this->once())->method("getAttribute")->with(SimpleAcl::ACL_USER_ATTRIBUTE)->will($this->returnValue(null));
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->registerProcessor("FooProcessor", $processor);
        $acl->addResource("FooResource")->addPermission("bar")->endResource();
        
        $acl->isAllowed($user, "FooResource", "bar");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildFromFiles()
     */
    public function testExceptionBuildFromFilesWhenResourceNotFoundException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("An error happen into this file '/home/algorab/Shared/Framework/Acl/tests/Fixtures/SimpleAcl/Invalid/ResourceNotFoundException.php' during the initialization of the acl. See : This parent resource 'FooResource' is not registered into the acl and cannot be extended to 'BarResource'");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->buildFromFiles([__DIR__."/Fixtures/SimpleAcl/Invalid/ResourceNotFoundException.php"]);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildFromFiles()
     */
    public function testExceptionBuildFromFilesWhenLogicException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("An error happen into this file '/home/algorab/Shared/Framework/Acl/tests/Fixtures/SimpleAcl/Invalid/LogicException.php' during the initialization of the acl. See : This permission 'foo' is already registered into resource 'FooResource'");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->buildFromFiles([__DIR__."/Fixtures/SimpleAcl/Invalid/LogicException.php"]);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildFromFiles()
     */
    public function testExceptionBuildFromFilesWhenEntryNotFoundException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("An error happen into this file '/home/algorab/Shared/Framework/Acl/tests/Fixtures/SimpleAcl/Invalid/EntryNotFoundException.php' during the initialization of the acl. See : This entry 'foo' is not registered into resource 'FooResource'");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->buildFromFiles([__DIR__."/Fixtures/SimpleAcl/Invalid/EntryNotFoundException.php"]);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildFromFiles()
     */
    public function testExceptionBuildFromFilesWhenAFileIsRegisteringAProcessor(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("An error happen into this file '/home/algorab/Shared/Framework/Acl/tests/Fixtures/SimpleAcl/Invalid/ProcessorError.php' during the initialization of the acl. See : Cannot register acl processor into file");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->buildFromFiles([__DIR__."/Fixtures/SimpleAcl/Invalid/ProcessorError.php"]);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::buildFromFiles()
     */
    public function testExceptionWhenAFileIsInvalid(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This file 'Foo' is neither a valid file or directory");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->buildFromFiles(["Foo"]);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::changeBehaviour()
     */
    public function testExceptionWhenBehaviourWhenBehaviourIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Behaviour is invalid. Use SimpleAcl::WHITELIST or SimpleAcl::BLACKLIST const");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->changeBehaviour(3);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addResource()
     */
    public function testExceptionAddResourceWhenResourceIsAlreadyRegistered(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This resource 'FooResource' is already registered into the acl");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->addResource("FooResource")->endResource();
        $acl->addResource("FooResource");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addResource()
     */
    public function testExceptionAddResourceWhenParentResourceIsNotRegistered(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This parent resource 'FooResource' is not registered into the acl and cannot be extended to 'BarResource'");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->addResource("BarResource", "FooResource");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addResource()
     */
    public function testExceptionAddResourceWhenLastResourceCursorIsNotCleared(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot add a new resource now. End registration of 'FooResource' before new registration");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->addResource("FooResource")->addResource("BarResource");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addPermission()
     */
    public function testExceptionAddPermissionWhenNoResourceSelected(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("No resource has been defined");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->addPermission("foo");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addPermission()
     */
    public function testExceptionAddPermissionWhenPermissionHasBeenAlreadyDeclaredIntoResource(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This permission 'foo' is already registered into resource 'FooResource'");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->addResource("FooResource")->addPermission("foo")->addPermission("foo");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addPermission()
     */
    public function testExceptionAddPermissionWhenPermissionHasBeenAlreadyDeclaredIntoAParentResource(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This permission 'foo' has been already declared into parent resource 'FooResource' of resource 'MozResource' and cannot be redeclared");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->addResource("FooResource")->addPermission("foo")->endResource();
        $acl->addResource("BarResource", "FooResource")->addPermission("bar")->endResource();
        $acl->addResource("MozResource", "BarResource")->addPermission("foo")->endResource();
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addPermission()
     */
    public function testExceptionAddPermissionWhenMaxPermissionCountAllowedIsReached(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot add more permission into resource 'FooResource'");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->addResource("FooResource");
        for ($i = 0; $i < 32; $i++) {
            $acl->addPermission((string) $i);
        }
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addEntry()
     */
    public function testExceptionAddEntryWhenResourceIsNotRegistered(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource 'FooResource' is not registered into the acl");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->addEntry("FooEntry", [], "FooResource");
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addEntry()
     */
    public function testExceptionAddEntryWhenEntryIsRoot(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("ROOT entry cannot be overriden into resource 'FooResource'");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->addResource("FooResource")->addEntry("ROOT", []);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addEntry()
     */
    public function testExceptionAddEntryWhenPermissionNotFoundIntoResourceWithNoParent(): void
    {
        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage("This entry 'foo' is not registered into resource 'FooResource'");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->addResource("FooResource")->addEntry("FooEntry", ["foo"]);
    }
    
    /**
     * @see \Ness\Component\Acl\SimpleAcl::addEntry()
     */
    public function testExceptionAddEntryWhenPermissionNotFoundIntoResourceWithParent(): void
    {
        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage("This entry 'foo' is not registered into resource 'BarResource' nor into one of its parents 'FooResource'");
        
        $acl = new SimpleAcl($this->getMockBuilder(LockPatternNormalizerInterface::class)->getMock());
        $acl->addResource("FooResource")->endResource();
        $acl->addResource("BarResource", "FooResource")->addEntry("FooEntry", ["foo"]);
    }
    
    /**
     * Extract multiple properties from an acl 
     * 
     * @param SimpleAcl $acl
     *   Acl initialized
     * @param array $properties
     *   Properties to extract
     * 
     * @return array
     *   All values indexed by the property name
     */
    private function extractProperties(SimpleAcl $acl, array $properties): array
    {
        $values = [];
        $reflection = new \ReflectionClass($acl);
        foreach ($properties as $property) {
            $current = $reflection->getProperty($property);
            $current->setAccessible(true);
            $values[$property] = $current->getValue($acl);
        }
        
        return $values;
    }
    
}
