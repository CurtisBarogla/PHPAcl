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
use Zoe\Component\Acl\Resource\ResourceInterface;
use Zoe\Component\Acl\JsonRestorableInterface;
use Zoe\Component\Acl\Loader\Cache\JsonCacheFormatStrategy;
use Zoe\Component\Acl\Resource\Resource;

/**
 * JsonCacheFormatStrategy testcase
 * 
 * @see \Zoe\Component\Acl\Loader\Cache\JsonCacheFormatStrategy
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class JsonCacheFormatStrategyTest extends TestCase
{
    
    /**
     * @see \Zoe\Component\Acl\Loader\Cache\JsonCacheFormatStrategy::processSetting()
     */
    public function testProcessSetting(): void
    {
        $resource = $this->getMockBuilder([ResourceInterface::class, JsonRestorableInterface::class])->getMock();
        $resource->expects($this->once())->method("jsonSerialize")->will($this->returnValue("Foo"));
        
        $format = new JsonCacheFormatStrategy();
        
        $this->assertSame('"Foo"', $format->processSetting($resource));
    }
    
    /**
     * @see \Zoe\Component\Acl\Loader\Cache\JsonCacheFormatStrategy::processGetting()
     */
    public function testProcessGetting(): void
    {
        $resource = new Resource("Foo", ResourceInterface::BLACKLIST);
        $json = \json_encode($resource);
        
        $format = new JsonCacheFormatStrategy();
        $this->assertEquals($resource, $format->processGetting($json));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Acl\Loader\Cache\JsonCacheFormatStrategy::processSetting()
     */
    public function testExceptionProcessSettingWhenResourceIsNotJsonRestorable(): void
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        $resource->expects($this->once())->method("getName")->will($this->returnValue("Foo"));
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Resource 'Foo' must implement JsonRestorableInteface");
        
        $format = new JsonCacheFormatStrategy();
        $format->processSetting($resource);
    }
    
}
