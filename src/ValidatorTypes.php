<?php

namespace Rammewerk\Component\Environment;

use LogicException;
use RuntimeException;
use Throwable;

class ValidatorTypes {

    private ?string $key;

    /** @var string|bool|string[]|int|null */
    private string|bool|array|int|null $value;


    /**
     * @param string|null $key
     * @param string|bool|string[]|int|null $value
     */
    public function __construct(?string $key, string|bool|array|int|null $value) {
        $this->key = $key;
        $this->value = $value;
    }


    /**
     * Validate if environment variable is not empty
     *
     * @return $this
     * @noinspection PhpUnused PhpUnused
     */
    public function notEmpty(): static {
        if( $this->key && empty( $this->value ) ) $this->error( "is empty" );
        return $this;
    }


    /**
     * Validate if environment variable is boolean
     *
     * @return $this
     * @noinspection PhpUnused PhpUnused
     */
    public function isBoolean(): static {
        if( $this->key && !is_bool( $this->value ) ) $this->error( "is not boolean" );
        return $this;
    }


    /**
     * Validate if environment variable is integer
     *
     * @return $this
     * @noinspection PhpUnused PhpUnused
     */
    public function isInteger(): static {
        if( $this->key && !is_int( $this->value ) ) $this->error( "is not integer" );
        return $this;
    }


    /**
     * Validate if environment variable is an array
     *
     * @return $this
     * @noinspection PhpUnused PhpUnused
     */
    public function isArray(): static {
        if( $this->key && !is_array( $this->value ) ) $this->error( "is not array" );
        return $this;
    }


    /**
     * Validate if environment variable ends with given string
     *
     * @param string $end
     *
     * @return $this
     * @noinspection PhpUnused PhpUnused
     */
    public function endWith(string $end): static {
        if( !$this->key ) return $this;
        if( !is_scalar( $this->value ) || !str_ends_with( (string)$this->value, $end ) ) $this->error( "does not end with $end" );
        return $this;
    }


    /**
     * Check if environment variable is allowed
     *
     * @param string[] $values
     *
     * @return $this
     * @noinspection PhpUnused PhpUnused
     */
    public function allowedValues(array $values): static {
        if( $this->key && !in_array( $this->value, $values, true ) ) $this->error( "is not an allowed value" );
        return $this;
    }


    /**
     * Validate if environment variable matches regex
     *
     * @param string $pattern
     *
     * @return $this
     * @noinspection PhpUnused PhpUnused
     */
    public function allowedRegexValues(string $pattern): static {
        if( !$this->key ) return $this;

        if( !is_scalar( $this->value ) ) {
            $this->error( "Cannot perform regular expression on non-scalar value" );
        }

        try {
            /** @phpstan-ignore-next-line */
            $match = @preg_match( $pattern, (string)$this->value );
        } catch( Throwable $e ) {
            throw new RuntimeException( "Unable to perform regular expression - " . $e->getMessage() );
        }

        # Get error if invalid pattern
        if( $match === false && preg_last_error() !== PREG_NO_ERROR ) {
            throw new RuntimeException( "Invalid regular expression - " . preg_last_error_msg() );
        }

        /** @phpstan-ignore-next-line */
        if( preg_match( $pattern, $this->value ) !== 1 ) {
            $this->error( "does not match $pattern" );
        }

        return $this;
    }


    /**
     * Throw error
     *
     * @param string $message
     */
    private function error(string $message): void {
        throw new LogicException( "One or more environment variables failed assertions: $this->key $message" );
    }

}