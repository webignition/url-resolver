<?php

namespace webignition\Tests\Url\Resolver\Resolve\CookiesTest;

use webignition\Tests\Url\Resolver\BaseTest;
use webignition\Url\Resolver\Resolver;

abstract class CookiesTest extends BaseTest {

    const SOURCE_URL = 'https://example.com/path';  
    
    /**
     * 
     * @return array
     */
    abstract protected function getCookies();
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */    
    abstract protected function getExpectedRequestsOnWhichCookiesShouldBeSet();
    
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */    
    abstract protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet();    
    
    public function setUp() { 
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 200 OK'
        )));        

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        $resolver->getConfiguration()->setCookies($this->getCookies());
        $resolver->resolve(self::SOURCE_URL);
    }    
    
    public function testCookiesAreSetOnExpectedRequests() {
        foreach ($this->getExpectedRequestsOnWhichCookiesShouldBeSet() as $request) {            
            $this->assertEquals($this->getExpectedCookieValues(), $request->getCookies());
        }
    }
    
    
    public function testCookiesAreNotSetOnExpectedRequests() {        
        foreach ($this->getExpectedRequestsOnWhichCookiesShouldNotBeSet() as $request) {            
            $this->assertEquals(array(), $request->getCookies());
        }
    }    
    

    /**
     * 
     * @return array
     */
    private function getExpectedCookieValues() {
        $nameValueArray = array();
        
        foreach ($this->getCookies() as $cookie) {
            $nameValueArray[$cookie['name']] = $cookie['value'];
        }
        
        return $nameValueArray;
    }
    
}