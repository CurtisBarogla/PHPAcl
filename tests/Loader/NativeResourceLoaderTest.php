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

namespace ZoeTest\Component\Acl\Loader;

use PHPUnit\Framework\TestCase;
use Zoe\Component\Acl\Loader\NativeResourceLoader;
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\Exception\ResourceNotFoundException;

/**
 * NativeResourceLoader testcase
 * 
 * @see \Zoe\Component\Acl\Loader\NativeResourceLoader
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NativeResourceLoaderTest extends TestCase
{
    
    /**
     * @see \Zoe\Component\Acl\Loader\NativeResourceLoader::load()
     */
    public function testLoad(): void
    {
        $files = [__DIR__."/../Fixture/Loader/native/Foo.php", __DIR__."/../Fixture/Loader/native/Bar.php"];
        $loader = new NativeResourceLoader($files);
        
        $this->assertInstanceOf(ResourceInterface::class, $loader->load("Foo"));
        $this->assertInstanceOf(ResourceInterface::class, $loader->load("Bar"));
    }
    
                    /**_____EXCEPTIONS_____**/

    /**
     * @see \Zoe\Component\Acl\Loader\NativeResourceLoader::__construct()
     */
    public function testExceptionWhenFileDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("This file 'Invalid' does not exist");
        
        $loader = new NativeResourceLoader(["Invalid"]);
    }
    
    /**
     * @see \Zoe\Component\Acl\Loader\NativeResourceLoader::load()
     */
    public function testExceptionLoadWhenResourceNotFound(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource 'Foo' cannot be loaded");
        
        $loader = new NativeResourceLoader([]);
        $loader->load("Foo");
    }
    
    /**
     * @see \Zoe\Component\Acl\Loader\NativeResourceLoader::load()
     */
    public function testExceptionLoadWhenResourceFileDoesNotReturnAResourceInterface(): void
    {
        $this->expectException(\RuntimeException::class);
        $path = __DIR__."/../Fixture/Loader/native/Invalid.php";
        $this->expectExceptionMessage("This acl resource file '{$path}' MUST return a ResourceInterface");
        
        $loader = new NativeResourceLoader([$path]);
        $loader->load("Invalid");
    }
    
}
