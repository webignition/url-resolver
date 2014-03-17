<?php

namespace webignition\Tests\Url\Resolver\Resolve\CookiesTest;

class PathTest extends CookieIsSetTest { 
    
    protected function getCookies() {
        return array(
            array(
                'domain' => '.example.com',
                'path' => '/path',
                'name' => 'name1',
                'value' => 'value1'
            )                       
        );         
    }    
    
}