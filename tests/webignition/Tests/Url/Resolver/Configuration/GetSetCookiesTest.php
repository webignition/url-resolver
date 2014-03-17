<?php

namespace webignition\Tests\Url\Resolver\Configuration;

use webignition\Tests\Url\Resolver\BaseTest;
use webignition\Url\Resolver\Configuration\Configuration;

class GetSetCookiesTest extends BaseTest {
    
    
    /**
     *
     * @var \webignition\Url\Resolver\Configuration\Configuration
     */
    private $configuration;
    
    public function setUp() {
        $this->configuration = new Configuration();
    }
    
    public function testDefaultGetCookies() {
        $this->assertEquals(array(), $this->configuration->getCookies());
    }    
    
    public function testSetReturnsSelf() {
        $this->assertEquals($this->configuration, $this->configuration->setCookies(array()));
    }       
    
    public function testGetReturnsValuesSet() {
        $cookies = array(
            array(
                'domain' => '.example.com',
                'path' => '/',
                'secure' => true,
                'name' => 'foo',
                'value' => 'bar'
            )
        );
        
        $this->configuration->setCookies($cookies);
        
        $this->assertEquals($cookies, $this->configuration->setCookies($cookies)->getCookies());   
    }  
    
}