<?php

namespace webignition\Tests\Url\Resolver\Resolve\HttpRedirect;

use webignition\Tests\Url\Resolver\BaseTest;
use webignition\Url\Resolver\Resolver;

class FollowTest extends BaseTest {
    
    const SOURCE_URL = 'http://example.com/';
    const EFFECTIVE_URL = 'http://www.example.com/';
    
    
    public function setUp() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 " . $this->getTestStatusCode() . "\nLocation:" . self::EFFECTIVE_URL,
            "HTTP/1.0 200 OK",
        )));        

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        
        $this->assertEquals(self::EFFECTIVE_URL, $resolver->resolve(self::SOURCE_URL));        
    }
    
    public function test301() {}
    public function test302() {}
    public function test303() {}
    public function test307() {}
    public function test308() {}
    
    
    /**
     * 
     * @return int
     */
    private function getTestStatusCode() {
        return (int)str_replace('test', '', $this->getName());
    }
    
}