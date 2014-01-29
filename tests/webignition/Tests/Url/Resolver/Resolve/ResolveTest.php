<?php

namespace webignition\Tests\Url\Resolver\Resolve;

use webignition\Tests\Url\Resolver\BaseTest;
use webignition\Url\Resolver\Resolver;

class ResolveTest extends BaseTest {
    
    public function testTest() {
        $url = 'http://example.com/';
        $resolver = new Resolver();
        $this->assertEquals($url, $resolver->resolve($url));
    }
    
}