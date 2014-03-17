<?php

namespace webignition\Tests\Url\Resolver\Resolve\CookiesTest;

abstract class CookieIsSetTest extends CookiesTest { 
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldBeSet() {
        return array($this->getHttpHistory()->getLastRequest());
    }    
    
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        return array();
    }
    
}