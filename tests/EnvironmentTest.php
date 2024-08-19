<?php

namespace Rammewerk\Component\Environment\Tests;

use PHPUnit\Framework\TestCase;
use Rammewerk\Component\Environment\Environment;

class EnvironmentTest extends TestCase {

    public function testLoad(): void {
        $environment = (new Environment())->load( __DIR__ . '/envFiles/booleans.env' );
        $this->assertTrue( $environment->get( 'VALID_LOWERCASE_TRUE' ) );
    }


    /**
     * Test if boolean environment variables are correctly loaded
     *
     * @return void
     */
    public function testBoolean(): void {
        $environment = (new Environment())->load( __DIR__ . '/envFiles/booleans.env' );
        $this->assertEquals( 'true', $environment->get( 'INVALID_STRING_TRUE' ) );
        $this->assertIsNotBool( $environment->get( 'INVALID_STRING_TRUE' ) );
        $this->assertEquals( 'false', $environment->get( 'INVALID_STRING_FALSE' ) );
        $this->assertIsNotBool( $environment->get( 'INVALID_STRING_FALSE' ) );
        $this->assertTrue( $environment->get( 'VALID_LOWERCASE_TRUE' ) );
        $this->assertTrue( $environment->get( 'VALID_UPPERCASE_TRUE' ) );
        $this->assertFalse( $environment->get( 'VALID_LOWERCASE_FALSE' ) );
        $this->assertFalse( $environment->get( 'VALID_UPPERCASE_FALSE' ) );
    }


    public function testOverwrite(): void {
        $key = 'VALID_LOWERCASE_FALSE';
        $environment = (new Environment())->load( __DIR__ . '/envFiles/booleans.env' );
        $this->assertFalse( $environment->get( $key ) );
        $environment->set( $key, true );
        $this->assertTrue( $environment->get( $key ) );
    }

}
