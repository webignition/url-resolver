<?php

namespace webignition\Tests\Url\Resolver\Resolve;

use webignition\Tests\Url\Resolver\BaseTest;
use webignition\Url\Resolver\Resolver;

class HttpErrorTest extends BaseTest {
    
    const SOURCE_URL = 'http://example.com/';
    const EFFECTIVE_URL = 'http://example.com/';
    
    public function setUp() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 " . $this->getTestStatusCode(),
            "HTTP/1.0 " . $this->getTestStatusCode(),
        )));        

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        $resolver->getConfiguration()->enableRetryWithUrlEncodingDisabled();        
        
        $this->assertEquals(self::EFFECTIVE_URL, $resolver->resolve(self::SOURCE_URL));        
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