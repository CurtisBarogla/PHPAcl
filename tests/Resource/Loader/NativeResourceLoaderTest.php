<?php
//StrictType
declare(strict_types = 1);

/*
 * Zoe
 * Acl component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */

namespace ZoeTest\Component\Acl\Resource\Loader;

use PHPUnit\Framework\TestCase;
use Zoe\Component\Acl\Resource\Loader\NativeResourceLoader;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\Exception\ResourceNotFoundException;

/**
 * NativeResourceLoader testcase
 * 
 * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NativeResourceLoaderTest extends TestCase
{
    
    /**
     * Base path for a resource fixtures
     * 
     * @var string
     */
    private const BASE_FIXTURE_DIRECTORY = __DIR__."/../../Fixtures/Resource";
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::load()
     */
    public function testLoad(): void
    {
        $base = self::BASE_FIXTURE_DIRECTORY . DIRECTORY_SEPARATOR . "resource/native/valid";
        $files = ["{$base}/Foo.php", "{$base}/Bar.php"];
        
        $loader = new NativeResourceLoader($files);
        
        $foo = $loader->load("Foo");
        $bar = $loader->load("Bar");

        foreach ([$foo, $bar] as $resource) {
            $this->assertInstanceOf(ResourceInterface::class, $resource);
            $this->assertSame(ResourceInterface::BLACKLIST, $resource->getBehaviour());
            $this->assertSame(1, $resource->getPermission("foo"));
            $this->assertSame(2, $resource->getPermission("bar"));
            $this->assertSame(3, $resource->getPermission("all"));
        }
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::loadCollection()
     */
    public function testLoadCollection(): void
    {
        $base = self::BASE_FIXTURE_DIRECTORY . DIRECTORY_SEPARATOR . "collection/native/valid";
        
        $loader = new NativeResourceLoader(["{$base}/FooCollection.php"]);
        
        $collection = $loader->loadCollection("FooCollection");
        
        foreach ($collection as $name => $resource) {
            $this->assertInstanceOf(ResourceInterface::class, $resource);
            $this->assertSame(ResourceInterface::BLACKLIST, $resource->getBehaviour());
            $this->assertSame(1, $resource->getPermission("foo"));
            $this->assertSame(2, $resource->getPermission("bar"));
            $this->assertSame(3, $resource->getPermission("all"));
            switch ($name) {
                case "Foo":
                    $this->assertSame("Foo", $resource->getName());
                    break;
                case "Bar":
                    $this->assertSame("Bar", $resource->getName());
                    break;
            }
        }
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::load()
     */
    public function testExceptionLoadWhenAResourceIsNotRegisteredIntoFileList(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource/collection 'Foo' cannot be loaded over '" . NativeResourceLoader::class . "' resource load as it is not registered into given files");
        
        $loader = new NativeResourceLoader([]);
        
        $loader->load("Foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::loadCollection()
     */
    public function testExceptionLoadCollectionWhenACollectionIsNotRegisteredIntoFileList(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource/collection 'FooCollection' cannot be loaded over '" . NativeResourceLoader::class . "' resource load as it is not registered into given files");
        
        $loader = new NativeResourceLoader([]);
        
        $loader->loadCollection("FooCollection");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::load()
     */
    public function testExceptionLoadWhenResourceIsRegisteredAndFileDoesNotExist(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource/collection 'Foo' cannot be loaded over '" . NativeResourceLoader::class . "' as given file : '/foo/bar/Foo.php' does not exist");
        
        $loader = new NativeResourceLoader(["/foo/bar/Foo.php"]);
        
        $loader->load("Foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::loadCollection()
     */
    public function testExceptionLoadCollectionWhenCollectionIsRegisteredAndFileDoesNotExist(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource/collection 'FooCollection' cannot be loaded over '" . NativeResourceLoader::class . "' as given file : '/foo/bar/FooCollection.php' does not exist");
        
        $loader = new NativeResourceLoader(["/foo/bar/FooCollection.php"]);
        
        $loader->loadCollection("FooCollection");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::load()
     */
    public function testExceptionLoadWhenFileDoesNotReturnAnInstanceOfAResourceInterfaceImplementation(): void
    {
        $file = self::BASE_FIXTURE_DIRECTORY."/resource/native/invalid/Foo.php";
        
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This file '{$file}' MUST resource an instance of 'Zoe\Component\Acl\Resource\ResourceInterface'");
        
        $loader = new NativeResourceLoader([$file]);
        
        $loader->load("Foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::loadCollection()
     */
    public function testExceptionLoadCollectionWhenFileDoesNotReturnAnInstanceOfAResourceCollectionInterfaceImplementation(): void
    {
        $file = self::BASE_FIXTURE_DIRECTORY."/collection/native/invalid/FooCollection.php";
        
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This file '{$file}' MUST resource an instance of 'Zoe\Component\Acl\Resource\ResourceCollectionInterface'");
        
        $loader = new NativeResourceLoader([$file]);
        
        $loader->loadCollection("FooCollection");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::load()
     */
    public function testExceptionLoadWhenResourceNameDoesNotCorrespondToGivenFilenameOne(): void
    {
        $file = self::BASE_FIXTURE_DIRECTORY."/resource/native/invalid/Bar.php";
        
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("Resource/Collection name 'Bar' from file '{$file}' from loaded resource/collection 'Foo' does not correspond");
        
        $loader = new NativeResourceLoader([$file]);
        
        $loader->load("Bar");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::loadCollection()
     */
    public function testExceptionLoadCollectionWhenResourceCollectionNameDoesNotCorrespondToGivenFilenameOne(): void
    {
        $file = self::BASE_FIXTURE_DIRECTORY."/collection/native/invalid/BarCollection.php";
        
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("Resource/Collection name 'BarCollection' from file '{$file}' from loaded resource/collection 'FooCollection' does not correspond");
        
        $loader = new NativeResourceLoader([$file]);
        
        $loader->loadCollection("BarCollection");
    }
    
}
