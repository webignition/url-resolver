<?php

namespace webignition\Tests\Url\Resolver\Configuration;

use webignition\Tests\Url\Resolver\BaseTest;
use webignition\Url\Resolver\Configuration\Configuration;

class FollowMetaRedirectsTest extends BaseTest {
    
    
    /**
     *
     * @var \webignition\Url\Resolver\Configuration\Configuration
     */
    private $configuration;
    
    public function setUp() {
        $this->configuration = new Configuration();
    }
    
    public function testEnableReturnsSelf() {
        $this->assertEquals($this->configuration, $this->configuration->enableFollowMetaRedirects());
    }    
    
    public function testDisableReturnsSelf() {
        $this->assertEquals($this->configuration, $this->configuration->disableFollowMetaRedirects());
    }       
    
    public function testEnable() {
        $this->assertTrue($this->configuration->enableFollowMetaRedirects()->getFollowMetaRedirects());   
    }
    
    public function testDisable() {
        $this->assertFalse($this->configuration->disableFollowMetaRedirects()->getFollowMetaRedirects());   
    }
    
    public function testIsEnabledByDefault() {        
        $this->assertTrue($this->configuration->getFollowMetaRedirects());   
    }    
    
}