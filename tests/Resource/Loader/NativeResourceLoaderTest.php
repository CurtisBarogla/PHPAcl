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
    private const BASE_FIXTURE_DIRECTORY = __DIR__."/../../Fixtures/Resource/resource";
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::load()
     */
    public function testLoad(): void
    {
        $base = self::BASE_FIXTURE_DIRECTORY . DIRECTORY_SEPARATOR . "valid";
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
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::load()
     */
    public function testExceptionLoadWhenAResourceIsNotRegisteredIntoFileList(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource 'Foo' cannot be loaded over '" . NativeResourceLoader::class . "' resource load as it is not registered into given files");
        
        $loader = new NativeResourceLoader([]);
        
        $loader->load("Foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::load()
     */
    public function testExceptionLoadWhenResourceIsRegisteredAndFileDoesNotExist(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource 'Foo' cannot be loaded over '" . NativeResourceLoader::class . "' as given file : '/foo/bar/Foo.php' does not exist");
        
        $loader = new NativeResourceLoader(["/foo/bar/Foo.php"]);
        
        $loader->load("Foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::load()
     */
    public function testExceptionLoadWhenFileDoesNotReturnAnInstanceOfAResourceInterfaceImplementation(): void
    {
        $file = self::BASE_FIXTURE_DIRECTORY."/invalid/Foo.php";
        
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This file '{$file}' MUST resource an instance of ResourceInterface");
        
        $loader = new NativeResourceLoader([$file]);
        
        $loader->load("Foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Resource\Loader\NativeResourceLoader::load()
     */
    public function testExceptionLoadWhenResourceNameDoesNotCorrespondToGivenFilenameOne(): void
    {
        $file = self::BASE_FIXTURE_DIRECTORY."/invalid/Bar.php";
        
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("Resource name 'Bar' from file '{$file}' from loaded resource 'Foo' does not correspond");
        
        $loader = new NativeResourceLoader([$file]);
        
        $loader->load("Bar");
    }
    
}
