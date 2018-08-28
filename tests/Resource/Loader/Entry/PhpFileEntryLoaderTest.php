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
        $resource->expects($this->exactly(4))->method("getName")->will($this->returnValue("BarResource"));
        
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
        $this->expectExceptionMessage("This entry 'BarEntry' cannot be found for resource 'NOT_FOUND'");
        
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
    
}
