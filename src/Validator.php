<?php

namespace Rammewerk\Component\Environment;

use LogicException;

class Validator {

    /** @var array<string, string|bool|string[]|int|null> */
    private array $env;


    /**
     * Validator constructor.
     *
     * @param array<string, string|bool|string[]|int|null> $env
     */
    public function __construct(array $env) {
        $this->env = $env;
    }


    /**
     * Check if required environment variable is set
     *
     * @param string $key
     *
     * @return ValidatorTypes
     */
    public function require(string $key): ValidatorTypes {
        if( !isset( $this->env[$key] ) ) throw new LogicException( "One or more environment variables failed assertions: $key is required" );
        return new ValidatorTypes( $key, $this->env[$key] );
    }


    /**
     * @param string $key
     *
     * @return ValidatorTypes
     * @noinspection PhpUnused PhpUnused
     */
    public function ifPresent(string $key): ValidatorTypes {
        if( isset( $this->env[$key] ) ) return new ValidatorTypes( $key, $this->env[$key] );
        return new ValidatorTypes( null, null );
    }

}