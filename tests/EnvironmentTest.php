<?php

namespace Rammewerk\Component\Environment\Tests;

use JsonException;
use PHPUnit\Framework\TestCase;
use Rammewerk\Component\Environment\Environment;
use Rammewerk\Component\Environment\Validator;

class EnvironmentTest extends TestCase {

    private Environment $env;



    public function setUp(): void {
        $this->env = new Environment();
        $this->env->load( __DIR__ . '/envFiles/booleans.env' );
        $this->env->load( __DIR__ . '/envFiles/strings.env' );
        $this->env->load( __DIR__ . '/envFiles/arrays.env' );
        $this->env->load( __DIR__ . '/envFiles/numbers.env' );
    }



    public function testLoad(): void {
        $this->assertTrue( $this->env->get( 'VALID_LOWERCASE_TRUE' ) );
        $this->assertSame( $this->env->getString( 'VALID_STRING' ), 'valid' );
        $this->assertIsArray( $this->env->get( 'VALID_ARRAY' ) );
        $this->assertSame( 1, $this->env->get( 'VALID_INT' ) );
    }



    /**
     * Test if boolean environment variables are correctly loaded
     *
     * @return void
     */
    public function testBoolean(): void {
        $this->assertEquals( 'true', $this->env->get( 'INVALID_STRING_TRUE' ) );
        $this->assertIsNotBool( $this->env->get( 'INVALID_STRING_TRUE' ) );
        $this->assertFalse( $this->env->getBool( 'VALID_LOWERCASE_FALSE' ) );
        $this->assertEquals( 'false', $this->env->get( 'INVALID_STRING_FALSE' ) );
        $this->assertIsNotBool( $this->env->get( 'INVALID_STRING_FALSE' ) );
        $this->assertTrue( $this->env->get( 'VALID_LOWERCASE_TRUE' ) );
        $this->assertTrue( $this->env->getBool( 'VALID_LOWERCASE_TRUE' ) );
        $this->assertTrue( $this->env->get( 'VALID_UPPERCASE_TRUE' ) );
        $this->assertTrue( $this->env->getBool( 'VALID_UPPERCASE_TRUE' ) );
        $this->assertFalse( $this->env->get( 'VALID_LOWERCASE_FALSE' ) );
        $this->assertFalse( $this->env->getBool( 'VALID_LOWERCASE_FALSE' ) );
        $this->assertFalse( $this->env->get( 'VALID_UPPERCASE_FALSE' ) );
        $this->assertFalse( $this->env->getBool( 'VALID_UPPERCASE_FALSE' ) );
    }



    public function testString(): void {
        $this->assertSame( 'valid', $this->env->getString( 'VALID_STRING' ) );
        $this->assertSame( 'valid', $this->env->getString( 'VALID_UNQUOTED_STRING' ) );
        $this->assertNull( $this->env->getString( 'NOT_VALID_STRING' ) );
        $this->assertNull( $this->env->getString( 'NOT_VALID_STRING_ARRAY' ) );
    }



    public function testArrays(): void {
        $this->assertIsArray( $this->env->get( 'VALID_ARRAY' ) );
        $this->assertIsArray( $this->env->getArray( 'VALID_ARRAY' ) );
        $this->assertCount( 2, $this->env->getArray( 'VALID_ARRAY' ) );
        $this->assertIsArray( $this->env->get( 'VALID_UNQUOTED_ARRAY' ) );
        $this->assertIsNotArray( $this->env->get( 'INVALID_ARRAY' ) );
        $this->assertIsArray( $this->env->get( 'VALID_QUOTED_ARRAY' ) );
        $this->assertIsArray( $this->env->get( 'VALID_EMPTY_ARRAY' ) );
        $this->assertIsNotArray( $this->env->getArray( 'NOT_AN_ARRAY' ) );
        $this->assertSame( [], $this->env->getArray( 'VALID_EMPTY_ARRAY' ) );
        $this->assertCount( 0, $this->env->getArray( 'VALID_EMPTY_ARRAY' ) );
    }



    public function testValidatorRequiredException(): void {
        # Should not throw exception
        $this->env->validate( function(Validator $v) {
            $v->require( 'VALID_LOWERCASE_TRUE' )->isBoolean();
            $v->ifPresent( 'VALID_ARRAY' )->isArray();
        } );

        # Should throw exception
        $this->expectException( \LogicException::class );
        $this->env->validate( function(Validator $v) {
            $v->require( 'NONE_EXISTING_KEY' );
        } );
    }



    public function testValidator(): void {
        # These validations should not throw exceptions
        $this->env->validate( function(Validator $v) {
            $v->require( 'VALID_LOWERCASE_TRUE' )->isBoolean();
            $v->require( 'VALID_INT' )->isInteger();
            $v->require( 'VALID_STRING' )->notEmpty();
            $v->ifPresent( 'VALID_LOWERCASE_TRUE' )->isBoolean();
            $v->ifPresent( 'VALID_ARRAY' )->isArray();
            # Check that non-existing key is not required
            $v->ifPresent( 'NON_EXITING_KEY' )->isArray();
            $v->require( 'VALID_STRING' )->endWith( 'id' );
            $v->require( 'VALID_STRING' )->allowedValues( ['valid', 'whatever'] );
        } );
        $this->assertTrue( $this->env->getBool( 'VALID_LOWERCASE_TRUE' ) );
    }



    public function testValidatorExceptionEndWith(): void {
        $this->expectException( \LogicException::class );
        $this->env->validate( function(Validator $v) {
            $v->require( 'VALID_STRING' )->endWith( 'ix' );
        } );
    }



    public function testValidatorExceptionAllowedValues(): void {
        $this->expectException( \LogicException::class );
        $this->env->validate( function(Validator $v) {
            $v->require( 'VALID_STRING' )->allowedValues( ['whatever'] );
        } );
    }



    public function testOverwrite(): void {
        $key = 'VALID_LOWERCASE_FALSE';
        $this->assertFalse( $this->env->get( $key ) );
        $this->env->set( $key, true );
        $this->assertTrue( $this->env->get( $key ) );
    }



    public function testCache(): void {
        $env = new Environment();
        $cache_path = __DIR__ . '/cache/booleans.json';
        $this->assertFileDoesNotExist( $cache_path, 'Cache file already exist, please remove before new test' );
        $env->load( __DIR__ . '/envFiles/booleans.env', $cache_path );
        $this->assertTrue( $env->getBool( 'VALID_LOWERCASE_TRUE' ) );
        $this->assertFileExists( $cache_path, 'Cache file was not created' );
        unlink( $cache_path );
        $this->assertFileDoesNotExist( $cache_path, 'Unable to delete cache file' );
    }



    /**
     * @throws JsonException
     */
    public function testCacheFileMock(): void {
        $cache_path = __DIR__ . '/cache/fake.json';
        file_put_contents( $cache_path, json_encode( ['FAKE' => true], JSON_THROW_ON_ERROR ) );
        $env = new Environment();
        $env->load( __DIR__ . '/envFiles/booleans.env', $cache_path );
        $this->assertTrue( $env->getBool( 'FAKE' ) );
        $this->assertNull( $env->get( 'VALID_LOWERCASE_TRUE' ) );
        $this->assertFileExists( $cache_path, 'Cache file was not created' );
        unlink( $cache_path );
        $this->assertFileDoesNotExist( $cache_path, 'Unable to delete cache file' );
    }



    public function testReload(): void {
        $env = new Environment();
        $env->load( __DIR__ . '/envFiles/booleans.env' );
        $key = 'VALID_LOWERCASE_TRUE';
        $this->assertTrue( $env->get( $key ) );
        $env->set( $key, false );
        $this->assertFalse( $env->get( $key ) );
        $env->reload();
        $this->assertTrue( $env->get( $key ) );
    }


}
