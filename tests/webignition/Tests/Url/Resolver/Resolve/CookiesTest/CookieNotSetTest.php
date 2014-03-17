<?php

namespace webignition\Tests\Url\Resolver\Resolve\CookiesTest;

abstract class CookieNotSetTest extends CookiesTest { 
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldBeSet() {
        return array();
    }    
    
    
    /**
     * 
     * @return \Guzzle\Http\Message\RequestInterface[]
     */
    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        return array($this->getHttpHistory()->getLastRequest());
    }
    
}