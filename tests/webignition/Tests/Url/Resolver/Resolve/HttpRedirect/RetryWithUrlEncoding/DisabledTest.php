<?php

namespace webignition\Tests\Url\Resolver\Resolve\HttpRedirect\RetryWithUrlEncoding;

use webignition\Tests\Url\Resolver\BaseTest;
use webignition\Url\Resolver\Resolver;

class DisabledTest extends BaseTest {
    
    const SOURCE_URL = 'http://example.com/';
    const EFFECTIVE_URL = 'http://wwww.example.com/';
    
    
    public function setUp() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 301\nLocation:" . self::EFFECTIVE_URL,
            "HTTP/1.0 " . $this->getTestStatusCode(),
            "HTTP/1.0 200 OK",
        )));        

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        
        $this->assertEquals(self::EFFECTIVE_URL, $resolver->resolve(self::SOURCE_URL));        
        $this->assertEquals($this->getTestStatusCode(), $resolver->getLastResponse()->getStatusCode());
    }
    
    public function test400() {}
    public function test404() {}
    public function test500() {}
    public function test503() {}    
    
    
    /**
     * 
     * @return int
     */
    private function getTestStatusCode() {
        return (int)str_replace('test', '', $this->getName());
    }
    
}