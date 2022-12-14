<?php

namespace Rammewerk\Component\Environment;

use LogicException;
use RuntimeException;


class Reader {

    /**
     * Load data from .env file
     *
     * @param string $file
     *
     * @return array
     */
    public function load(string $file): array {

        if( ! is_file( $file ) || ! is_readable( $file ) ) {
            throw new RuntimeException( 'Unable to read environment file' );
        }

        # Load file content
        $content = file_get_contents( $file );

        if( ! $content ) throw new RuntimeException( 'Unable to get environment file content' );

        # Create an array of each line
        $lines = preg_split( "/(\r\n|\n|\r)/", $content );

        if( ! $lines ) throw new RuntimeException( 'Unable to read lines from environment file' );

        # Remove empty lines and comments
        $lines = array_values( array_filter( $lines, static function (string $value): string {
            return $value !== '' && ! str_starts_with( trim( $value ), '#' );
        } ) );

        $variables = [];

        foreach( $lines as $line ) {

            if( ! str_contains( $line, '=' ) ) {
                throw new LogicException( "Invalid line in environment file: $line" );
            }

            $exploded = explode( '=', $line );
            $key = array_shift( $exploded );
            $value = implode( '=', $exploded );

            $variables[$key] = $this->removeQuotes( trim( $value ) );

        }

        return $variables;

    }




    /**
     * Remove quotes from environment value
     *
     * @param string $value
     *
     * @return string
     */
    private function removeQuotes(string $value): string {
        if( strlen( $value ) <= 2 ) return $value;
        if( ! str_starts_with( $value, '"' ) && ! str_starts_with( $value, '\'' ) ) return $value;
        if( ! str_ends_with( $value, '"' ) && ! str_ends_with( $value, '\'' ) ) return $value;
        return substr( substr( $value, 1 ), 0, -1 );
    }

}