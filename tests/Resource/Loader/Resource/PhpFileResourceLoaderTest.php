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

namespace NessTest\Component\Acl\Resource\Loader\Resource;

use NessTest\Component\Acl\AclTestCase;
use Ness\Component\Acl\Resource\Loader\Resource\PhpFileResourceLoader;
use Ness\Component\Acl\Resource\ResourceInterface;
use Ness\Component\Acl\Exception\ResourceNotFoundException;
use Ness\Component\Acl\Exception\ParseErrorException;

/**
 * PhpFileResourceLoader testcase
 * 
 * @see \Ness\Component\Acl\Resource\Loader\PhpFileResourceLoader
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class PhpFileResourceLoaderTest extends AclTestCase
{
    
    /**
     * Fixtures directory
     * 
     * @var string
     */
    private const FIXTURES_DIRECTORY = __DIR__."/../../../Fixtures/Resource/Loader/PhpFileResourceLoaderFixtures";
    
    /**
     * Files considered exploitables by the loader tested
     * 
     * @var string[]
     */
    private const VALID_FILES = [
        self::FIXTURES_DIRECTORY."/valid/MozSimple.php",
        self::FIXTURES_DIRECTORY."/valid/FooSimple.php",
        self::FIXTURES_DIRECTORY."/valid/BarSimple.php",
        self::FIXTURES_DIRECTORY."/valid/directory"
    ];
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Resource\PhpFileResourceLoader::load()
     */
    public function testLoad(): void
    {
        $loader = new PhpFileResourceLoader(self::VALID_FILES);
        
        $fooSimple = $loader->load("FooSimple");
        $barSimple = $loader->load("BarSimple");
        $mozSimple = $loader->load("MozSimple");
        $multipleFoo = $loader->load("MultipleFoo");
        $multipleBar = $loader->load("MultipleBar");
        $multipleMoz = $loader->load("MultipleMoz");
        $multiplePoz = $loader->load("MultiplePoz");
        $combinedFoo = $loader->load("CombinedFoo");
        $combinedBar = $loader->load("CombinedBar");
        $combinedMoz = $loader->load("CombinedMoz");
        
        $this->assertSame("FooSimple", $fooSimple->getName());
        $this->assertSame("BarSimple", $barSimple->getName());
        $this->assertSame("MozSimple", $mozSimple->getName());
        $this->assertSame("MultipleFoo", $multipleFoo->getName());
        $this->assertSame("MultipleBar", $multipleBar->getName());
        $this->assertSame("MultipleMoz", $multipleMoz->getName());
        $this->assertSame("MultiplePoz", $multiplePoz->getName());
        $this->assertSame("CombinedFoo", $combinedFoo->getName());
        $this->assertSame("CombinedBar", $combinedBar->getName());
        $this->assertSame("CombinedMoz", $combinedMoz->getName());
        
        $this->assertSame("FooSimple", $barSimple->getParent());
        $this->assertSame("BarSimple", $mozSimple->getParent());
        $this->assertSame("FooSimple", $multipleFoo->getParent());
        $this->assertSame("MultipleFoo", $multipleBar->getParent());
        $this->assertSame("MultipleFoo", $multipleMoz->getParent());
        $this->assertSame("MultipleMoz", $multiplePoz->getParent());
        $this->assertSame("MultiplePoz", $combinedFoo->getParent());
        $this->assertSame("CombinedFoo", $combinedMoz->getParent());
        
        $this->assertSame(1, $fooSimple->getPermission("foo"));
        
        $this->assertSame(1, $barSimple->getPermission("foo"));
        $this->assertSame(2, $barSimple->getPermission("bar"));
        
        $this->assertSame(1, $mozSimple->getPermission("foo"));
        $this->assertSame(2, $mozSimple->getPermission("bar"));
        $this->assertSame(4, $mozSimple->getPermission("moz"));
        
        $this->assertSame(1, $multipleFoo->getPermission("foo"));
        $this->assertSame(2, $multipleFoo->getPermission("foomultiple"));
        
        $this->assertSame(1, $multipleBar->getPermission("foo"));
        $this->assertSame(2, $multipleBar->getPermission("foomultiple"));
        $this->assertSame(4, $multipleBar->getPermission("barmultiple"));
        
        $this->assertSame(1, $multipleMoz->getPermission("foo"));
        $this->assertSame(2, $multipleMoz->getPermission("foomultiple"));
        $this->assertSame(4, $multipleMoz->getPermission("mozmultiple"));
        
        $this->assertSame(1, $multiplePoz->getPermission("foo"));
        $this->assertSame(2, $multiplePoz->getPermission("foomultiple"));
        $this->assertSame(4, $multiplePoz->getPermission("mozmultiple"));
        $this->assertSame(8, $multiplePoz->getPermission("pozmultiple"));
        
        $this->assertSame(1, $combinedFoo->getPermission("foo"));
        $this->assertSame(2, $combinedFoo->getPermission("foomultiple"));
        $this->assertSame(4, $combinedFoo->getPermission("mozmultiple"));
        $this->assertSame(8, $combinedFoo->getPermission("pozmultiple"));
        $this->assertSame(16, $combinedFoo->getPermission("foocombined"));
        
        $this->assertSame(1, $combinedBar->getPermission("barcombined"));
        
        $this->assertSame(1, $combinedMoz->getPermission("foo"));
        $this->assertSame(2, $combinedMoz->getPermission("foomultiple"));
        $this->assertSame(4, $combinedMoz->getPermission("mozmultiple"));
        $this->assertSame(8, $combinedMoz->getPermission("pozmultiple"));
        $this->assertSame(16, $combinedMoz->getPermission("foocombined"));
        $this->assertSame(32, $combinedMoz->getPermission("mozcombined"));
        
        foreach ([$fooSimple, $barSimple, $mozSimple, $multipleFoo, $multipleBar, $multipleMoz, $multipleMoz, $combinedFoo, $combinedBar, $combinedMoz] as $resource)
            $this->assertSame(ResourceInterface::WHITELIST, $resource->getBehaviour());
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Resource\PhpFileResourceLoader::__construct()
     */
    public function testExceptionLoadWhenAFileDoesNotExist(): void
    {
        $file = __DIR__."/foo";
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("This file '{$file}' is neither a directory or a file");
        
        $loader = new PhpFileResourceLoader([__DIR__."/foo"]);
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Resource\PhpFileResourceLoader::load()
     */
    public function testExceptionWhenAResourceCannotBeLoaded(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("This resource 'NotFound' cannot be loaded via this loader");
        
        $loader = new PhpFileResourceLoader(self::VALID_FILES);
        
        $loader->load("NotFound");
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Resource\PhpFileResourceLoader::load()
     */
    public function testExceptionLoadWhenPermissionsKeyIsNotAnArray(): void
    {
        $file = self::FIXTURES_DIRECTORY."/invalid/InvalidPermission.php";
        
        $this->expectException(ParseErrorException::class);
        $this->expectExceptionMessage("Permissions setted for resource 'InvalidPermission' MUST be an array into file '{$file}'");
        
        $loader = new PhpFileResourceLoader([$file]);
        
        $loader->load("InvalidPermission");
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Resource\PhpFileResourceLoader::load()
     */
    public function testExceptionLoadWhenFileReturnAnInvalidType(): void
    {
        $file = self::FIXTURES_DIRECTORY."/invalid/InvalidFile.php";
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("File '{$file}' does not return a value handled by this loaded. It must return an array or an instance of ResourceInterface");
        
        $loader = new PhpFileResourceLoader([$file]);
        
        $loader->load("InvalidFile");
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Resource\PhpFileResourceLoader::load()
     */
    public function testExceptionLoadWhenANativeResourceInstanceFileIsNotConcordantWithFilename(): void
    {
        $file = self::FIXTURES_DIRECTORY."/invalid/NotConcordant.php";
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Resource instance name 'Concordant' not concordant with filename 'NotConcordant' into file '{$file}'");
        
        $loader = new PhpFileResourceLoader([$file]);
        
        $loader->load("NotConcordant");
    }
    
    /**
     * @see \Ness\Component\Acl\Resource\Loader\Resource\PhpFileResourceLoader::load()
     */
    public function testExceptionLoadWhenAnInvalidTypeIsGivenAsResourceIntoAMultipleFileResource(): void
    {
        $file = self::FIXTURES_DIRECTORY."/invalid/InvalidCombined.php";
        
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Invalid value given into file '{$file}' as resource. MUST be an array indexed by the resource name or an instance of ResourceInterface. 'string' given");
        
        $loader = new PhpFileResourceLoader([$file]);
        
        $loader->load("Bar");
    }
    
}
