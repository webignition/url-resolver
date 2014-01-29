<?php

namespace webignition\Tests\Url\Resolver\Resolve;

use webignition\Tests\Url\Resolver\BaseTest;
use webignition\Url\Resolver\Resolver;

class ResolveTest extends BaseTest {
    
    const SOURCE_URL = 'http://example.com/';
    const EFFECTIVE_URL = 'http://www.example.com/';    
    
    public function testNoHttpRedirect() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK",
        )));

        $resolver = new Resolver();        
        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
        
        $this->assertEquals(self::SOURCE_URL, $resolver->resolve(self::SOURCE_URL));
    }
    
}