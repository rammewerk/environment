<?php

namespace Rammewerk\Component\Environment;

use Throwable;
use LogicException;
use RuntimeException;

class ValidatorTypes {

    private ?string $key;
    private mixed $value;




    public function __construct(?string $key, mixed $value) {
        $this->key = $key;
        $this->value = $value;
    }




    public function notEmpty(): self {
        if( $this->key && empty( $this->value ) ) $this->error( "is empty" );
        return $this;
    }




    public function isBoolean(): self {
        if( $this->key && ! is_bool( $this->value ) ) $this->error( "is not boolean" );
        return $this;
    }




    public function isInteger(): self {
        if( $this->key && ! is_int( $this->value ) ) $this->error( "is not integer" );
        return $this;
    }




    public function isArray(): self {
        if( $this->key && ! is_array( $this->value ) ) $this->error( "is not array" );
        return $this;
    }




    public function endWith(string $end): self {
        if( $this->key && ! str_ends_with( (string)$this->value, $end ) ) $this->error( "does not end with $end" );
        return $this;
    }




    public function allowedValues(array $values): self {
        if( $this->key && ! in_array( $this->value, $values, true ) ) $this->error( "is not an allowed value" );
        return $this;
    }




    public function allowedRegexValues(string $pattern): self {
        if( ! $this->key ) return $this;

        try {
            $match = @preg_match( $pattern, $this->value );
        } catch ( Throwable $e ) {
            throw new RuntimeException( "Unable to perform regular expression - " . $e->getMessage() );
        }

        # Get error if invalid pattern
        if( $match === false && preg_last_error() !== PREG_NO_ERROR ) {
            throw new RuntimeException( "Invalid regular expression - " . preg_last_error_msg() );
        }

        if( preg_match( $pattern, $this->value ) !== 1 ) {
            $this->error( "does not match $pattern" );
        }

        return $this;
    }




    private function error($message): void {
        throw new LogicException( "One or more environment variables failed assertions: $this->key $message" );
    }

}