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
use Zoe\Component\Acl\Loader\Cache\NativeSerializeCacheFormatStrategy;

/**
 * NativeSerializeCacheFormatStrategy testcase
 * 
 * @see \Zoe\Component\Acl\Loader\Cache\NativeSerializeCacheFormatStrategy
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NativeSerializeCacheFormatStrategyTest extends TestCase
{
    
    /**
     * @see \Zoe\Component\Acl\Loader\Cache\NativeSerializeCacheFormatStrategy::processSetting()
     */
    public function testProcessSetting(): void
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();
        
        $format = new NativeSerializeCacheFormatStrategy();
        
        $this->assertTrue(\is_string($format->processSetting($resource)));
    }
    
    /**
     * @see \Zoe\Component\Acl\Loader\Cache\NativeSerializeCacheFormatStrategy::processGetting()
     */
    public function testProcessGetting(): void
    {
        $resource = $this->getMockBuilder(ResourceInterface::class)->getMock();        
        $format = new NativeSerializeCacheFormatStrategy();
        $serialize = $format->processSetting($resource);
        
        $this->assertEquals($resource, $format->processGetting($serialize));
    }
    
}
