<?php

namespace webignition\Tests\Url\Resolver\Resolve\HttpRedirect\RetryWithUrlEncoding;

use webignition\Tests\Url\Resolver\BaseTest;
use webignition\Url\Resolver\Resolver;

class EnabledTest extends BaseTest {
    
    const SOURCE_URL = 'http://example.com/';
    const EFFECTIVE_URL = 'http://www.example.com/';
    
    const EXPECTED_STATUS_CODE = 200;
    
    
    public function setUp() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 301\nLocation:" . self::EFFECTIVE_URL,
            "HTTP/1.0 " . $this->getTestStatusCode(),
            "HTTP/1.0 301\nLocation:" . self::EFFECTIVE_URL,
            "HTTP/1.0 " . self::EXPECTED_STATUS_CODE . " OK",
        )));        

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        $resolver->getConfiguration()->enableRetryWithUrlEncodingDisabled();
        
        $this->assertEquals(self::EFFECTIVE_URL, $resolver->resolve(self::SOURCE_URL));        
        $this->assertEquals(self::EXPECTED_STATUS_CODE, $resolver->getLastResponse()->getStatusCode());
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