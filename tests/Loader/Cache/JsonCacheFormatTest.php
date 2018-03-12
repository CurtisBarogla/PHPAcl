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

namespace ZoeTest\Component\Acl\Loader\Cache;

use PHPUnit\Framework\TestCase;
use Zoe\Component\Acl\JsonRestorableInterface;
use Zoe\Component\Acl\Loader\Cache\JsonCacheFormat;
use Zoe\Component\Acl\Resource\Resource;
use Zoe\Component\Acl\Resource\ResourceInterface;

/**
 * JsonCacheFormatStrategy testcase
 * 
 * @see \Zoe\Component\Acl\Loader\Cache\JsonCacheFormatStrategy
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class JsonCacheFormatTest extends TestCase
{
    
    /**
     * @see \Zoe\Component\Acl\Loader\Cache\JsonCacheFormat::processSetting()
     */
    public function testProcessSetting(): void
    {
        $resource = $this->getMockBuilder([ResourceInterface::class, JsonRestorableInterface::class])->getMock();
        $resource->expects($this->once())->method("jsonSerialize")->will($this->returnValue("Foo"));
        
        $format = new JsonCacheFormat();
        
        $this->assertSame('"Foo"', $format->processSetting($resource));
    }
    
    /**
     * @see \Zoe\Component\Acl\Loader\Cache\JsonCacheFormat::processGetting()
     */
    public function testProcessGetting(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $json = \json_encode($resource);
        
        $format = new JsonCacheFormat();
        $this->assertEquals($resource, $format->processGetting($json));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\Loader\Cache\JsonCacheFormat::processSetting()
     */
    public function testExceptionProcessSettingWhenResourceIsNotJsonRestorable(): void
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Resource 'Foo' must implement JsonRestorableInteface");
        
        $format = new JsonCacheFormat();
        $format->processSetting($resource);
    }
    
}
