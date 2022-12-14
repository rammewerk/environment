<?php

namespace Rammewerk\Component\Environment;

use LogicException;

class Validator {

    private array $env;




    public function __construct(array $env) {
        $this->env = $env;
    }




    public function require(string $key): ValidatorTypes {
        if( ! isset( $this->env[$key] ) ) throw new LogicException( "One or more environment variables failed assertions: $key is required" );
        return new ValidatorTypes( $key, $this->env[$key] );
    }




    public function ifPresent(string $key): ValidatorTypes {
        if( isset( $this->env[$key] ) ) return new ValidatorTypes( $key, $this->env[$key] );
        return new ValidatorTypes( null, null );
    }

}