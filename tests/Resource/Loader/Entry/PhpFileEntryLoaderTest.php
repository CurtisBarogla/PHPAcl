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

namespace NessTest\Component\Acl\Resource\Loader\Entry;

use NessTest\Component\Acl\AclTestCase;
use Ness\Component\Acl\Resource\Loader\Entry\PhpFileEntryLoader;
use Ness\Component\Acl\Exception\EntryNotFoundException;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Resource\Loader\Resource\ResourceLoaderInterface;
use Ness\Component\Acl\Resource\Resource;
use Ness\Component\Acl\Resource\ExtendableResource;

/**
 * PhpFileEntryLoader testcase
 * 
 * @see \Ness\Component\Acl\Resource\Loader\Entry\PhpFileEntryLoader
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class PhpFileEntryLoaderTest extends AclTestCase
{
    
    /**
     * Fixtures directory
     *
     * @var string
     */
    private const FIXTURES_DIRECTORY = __DIR__."/../../../Fixtures/Entry/Loader/PhpFileResourceLoaderFixtures";
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\PhpFileEntryLoader::load()
     */
    public function testLoad(): void
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(4))->method("getName")->will($this->returnValue("FooResource"));
        
        $files = [
            self::FIXTURES_DIRECTORY."/valid/FooResource",
            self::FIXTURES_DIRECTORY."/valid/BarResource_ENTRIES.php",
            self::FIXTURES_DIRECTORY."/valid/BarResource_FooProcessor_ENTRIES.php",
        ];
        
        $loader = new PhpFileEntryLoader($files);

        $entries = [];
        
        $entries[false][] = $loader->load($resource, "FooEntry");
        $entries[true][] = $loader->load($resource, "FooEntry", "FooProcessor");
        $entries[false][] = $loader->load($resource, "BarEntry");
        $entries[true][] = $loader->load($resource, "BarEntry", "FooProcessor");
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(7))->method("getName")->will($this->returnValue("BarResource"));
        
        $inheriteEntry = $loader->load($resource, "MozEntry");
        $entries[false][] = $loader->load($resource, "FooEntry");
        $entries[true][] = $loader->load($resource, "FooEntry", "FooProcessor");
        $entries[false][] = $loader->load($resource, "BarEntry");
        $entries[true][] = $loader->load($resource, "BarEntry", "FooProcessor");
        
        foreach ($entries as $processable => $entries) {
            $loop = 0;
            foreach ($entries as $entry) {
                if($loop % 2 === 0) {
                    $this->assertSame("FooEntry", $entry->getName());
                    if($processable)
                        $this->assertSame(["fooo", "barr"], $entry->getPermissions());
                    else
                        $this->assertSame(["foo", "bar"], $entry->getPermissions());
                } else {
                    if($processable)
                        $this->assertSame(["mozz", "pozz"], $entry->getPermissions());
                    else
                        $this->assertSame(["moz", "poz"], $entry->getPermissions());
                    $this->assertSame("BarEntry", $entry->getName());
                }

                $loop++;
            }
        }
        
        $this->assertSame("MozEntry", $inheriteEntry->getName());
        $this->assertSame(["foo", "bar", "moz", "poz", "kek"], $inheriteEntry->getPermissions());
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\PhpFileEntryLoader::load()
     */
    public function testLoadWithEntryInheritanceFromParentResource(): void
    {
        $fooResource = new Resource("FooResource");
        $barResource = new ExtendableResource("BarResource", $fooResource);
        $mozResource = new ExtendableResource("MozResource", $barResource);
        
        $resourceLoader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $resourceLoader
            ->expects($this->exactly(6))
            ->method("load")
            ->withConsecutive(["BarResource"], ["FooResource"], ["BarResource"], ["FooResource"], ["BarResource"], ["FooResource"])
            ->will($this->onConsecutiveCalls($barResource, $fooResource, $barResource, $fooResource, $barResource, $fooResource));
        
        $loader = new PhpFileEntryLoader([self::FIXTURES_DIRECTORY."/valid/Inheritance"]);
        $loader->setLoader($resourceLoader);
        
        $entry = $loader->load($mozResource, "FooEntry");
        
        $this->assertSame("FooEntry", $entry->getName());
        $this->assertSame(["foo", "foo2", "foo3"], $entry->getPermissions());
        
        $entry = $loader->load($mozResource, "FooEntry", "FooProcessor");
        
        $this->assertSame("FooEntry", $entry->getName());
        $this->assertSame(["fooz", "fooz2", "fooz3"], $entry->getPermissions());
        
        $entry = $loader->load($mozResource, "BarEntry");
        
        $this->assertSame("BarEntry", $entry->getName());
        $this->assertSame(["bar", "bar2"], $entry->getPermissions());
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\PhpFileEntryLoader::__construct()
     */
    public function testException__constructWhenAFileIsInvalid(): void
    {
        $file = __DIR__."/foo";
        $this->expectExceptionMessage(\LogicException::class);
        $this->expectExceptionMessage("This file '{$file}' is neither a directory or a file");
        
        $loader = new PhpFileEntryLoader([__DIR__."/foo"]);
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\PhpFileEntryLoader::load()
     */
    public function testExceptionLoadWhenResourceNotFound(): void
    {
        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage("This entry 'BarEntry' cannot be found for resource 'MUST_BE_FOUND'");
        
        $file = self::FIXTURES_DIRECTORY."/invalid/NOT_FOUND_ENTRIES.php";
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getName")->will($this->returnValue("MUST_BE_FOUND"));
        
        $loader = new PhpFileEntryLoader([$file]);
        $loader->load($resource, "BarEntry");
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\PhpFileEntryLoader::load()
     */
    public function testExceptionLoadWhenAnEntryCannotBeLoaded(): void
    {
        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage("This entry 'BarEntry' cannot be loaded for resource 'NOT_FOUND'. It may be invalid or not registered");
        
        $file = self::FIXTURES_DIRECTORY."/invalid/NOT_FOUND_ENTRIES.php";
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getName")->will($this->returnValue("NOT_FOUND"));
        
        $loader = new PhpFileEntryLoader([$file]);
        $loader->load($resource, "BarEntry");
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\PhpFileEntryLoader::load()
     */
    public function testExceptionLoadWhenAnInvalidFileIsGiven(): void
    {
        $file = self::FIXTURES_DIRECTORY."/invalid/INVALID_ENTRIES.php";
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This file '{$file}' MUST return an array representing all entries loadables for resource 'INVALID'");
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getName")->will($this->returnValue("INVALID"));
        
        $loader = new PhpFileEntryLoader([$file]);
        $loader->load($resource, "BarEntry");
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\PhpFileEntryLoader::load()
     */
    public function testExceptionLoadWhenAnInvalidInheritanceEntryIsGiven(): void
    {
        $file = self::FIXTURES_DIRECTORY."/invalid/FAIL_INHERITANCE_ENTRIES.php";
        
        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage("This entry 'FooEntry' cannot be loaded for resource 'FAIL_INHERITANCE'. It may be invalid or not registered");
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(4))->method("getName")->will($this->returnValue("FAIL_INHERITANCE"));
        
        $loader = new PhpFileEntryLoader([$file]);
        $loader->load($resource, "FooEntry");
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\PhpFileEntryLoader::load()
     */
    public function testExceptionLoadWhenAnInvalidInheritanceEntryWithTheSameParentEntryNameAndResourceIsNotExtendable(): void
    {
        $file = self::FIXTURES_DIRECTORY."/invalid/FAIL_INHERITANCE_ENTRIES.php";
        
        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage("This entry 'MozEntry' cannot be loaded for resource 'FAIL_INHERITANCE'. It may be invalid or not registered");
        
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->exactly(2))->method("getName")->will($this->returnValue("FAIL_INHERITANCE"));
        
        $loader = new PhpFileEntryLoader([$file]);
        $loader->load($resource, "MozEntry");
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Entry\PhpFileEntryLoader::load()
     */
    public function testExceptionLoadWhenAnInvalidInheritanceEntryAsPermissionCannotBeLoaded(): void
    {
        $this->expectException(EntryNotFoundException::class);
        $this->expectExceptionMessage("This parent entry 'BarEntry' for loading entry 'FooEntry' cannot be loaded into resource 'BarResource' not into its parents");
        
        $file = self::FIXTURES_DIRECTORY."/invalid/Inheritance";
        
        $fooResource = new Resource("FooResource");
        $barResource = new ExtendableResource("BarResource", $fooResource);
        
        $resourceLoader = $this->getMockBuilder(ResourceLoaderInterface::class)->getMock();
        $resourceLoader
            ->expects($this->once())
            ->method("load")
            ->with("FooResource")
            ->will($this->returnValue($fooResource));
        
        $loader = new PhpFileEntryLoader([$file]);
        $loader->setLoader($resourceLoader);
        
        $entry = $loader->load($barResource, "FooEntry");
    }
    
}
