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
use Zoe\Component\Acl\Exception\ResourceNotFoundException;
use Zoe\Component\Acl\Loader\ArrayResourceLoader;
use Zoe\Component\Acl\Resource\ResourceInterface;

/**
 * ArrayResourceLoader testcase
 * 
 * @see \Zoe\Component\Acl\Loader\ArrayResourceLoader
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ArrayResourceLoaderTest extends TestCase
{
    
    /**
     * @see \Zoe\Component\Acl\Loader\ArrayResourceLoader::load()
     */
    public function testLoad(): void
    {
        $resources = include_once __DIR__."/../Fixture/Loader/array/resources.php";
        
        $loader = new ArrayResourceLoader($resources);
        
        $foo = $loader->load("Foo");
        $bar = $loader->load("Bar");
        $moz = $loader->load("Moz");
        $poz = $loader->load("Poz");
        
        foreach ([$foo, $bar, $moz, $poz] as $resource) {
            $this->assertInstanceOf(ResourceInterface::class, $resource);
        }
        
        // behaviour
        $this->assertSame(ResourceInterface::BLACKLIST, $foo->getBehaviour());
        $this->assertSame(ResourceInterface::WHITELIST, $bar->getBehaviour());
        $this->assertSame(ResourceInterface::BLACKLIST, $moz->getBehaviour());
        $this->assertSame(ResourceInterface::WHITELIST, $poz->getBehaviour());
        
        // permissions
        $this->assertEmpty($foo->getPermissions());
        $this->assertEmpty($bar->getPermissions());
        
        $this->assertCount(2, $moz->getPermissions());
        $this->assertSame(1, $moz->getPermission("FooPermission")->getValue());
        $this->assertSame(2, $moz->getPermission("BarPermission")->getValue());
        
        $this->assertCount(2, $poz->getPermissions());
        $this->assertSame(1, $poz->getPermission("FooPermission")->getValue());
        $this->assertSame(2, $poz->getPermission("BarPermission")->getValue());
        
        // entities
        $this->assertNull($foo->getEntities());
        $this->assertNull($bar->getEntities());
        $this->assertNull($moz->getEntities());
        
        $this->assertNotNull($poz->getEntities());
        
        $fooEntity = $poz->getEntity("Foo");
        $this->assertNull($fooEntity->getProcessor());
        $this->assertCount(2, $fooEntity);
        $this->assertSame(["FooPermission", "BarPermission"], $fooEntity->get("FooValue"));
        $this->assertSame(["FooPermission"], $fooEntity->get("BarValue"));
        
        $barEntity = $poz->getEntity("Bar");
        $this->assertSame("FooProcessor", $barEntity->getProcessor());
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\Loader\ArrayResourceLoader::load()
     */
    public function testExceptionLoadWhenResourceNotFound(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource 'Foo' cannot be loaded");
        
        $loader = new ArrayResourceLoader([]);
        $loader->load("Foo");
    }
    
}
