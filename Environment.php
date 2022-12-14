<?php

namespace Rammewerk\Component\Environment;

use Closure;
use Throwable;
use JsonException;
use RuntimeException;
use InvalidArgumentException;

class Environment {

    private array $env = [];
    private array $env_files = [];




    /**
     * Run validation
     *
     * @param \Closure $callback
     *
     * @return void
     */
    public function validate(Closure $callback): void {
        $callback( new Validator( $this->env ) );
    }




    /**
     * Load environment file
     *
     * Warning: If an environment key already exist, it will overwrite this!
     *
     * If cache file is defined it will load from cache or create new cache.
     * Supports loading of multiple env files, but these can never share same cache.
     *
     * @param string $file            Path to environment file
     * @param string|null $cache_file Optional path to where cache file will be stored
     * @param \Closure|null $validate Optional validate closure to run before saving new cache
     *
     * @return $this
     */
    public function load(string $file, ?string $cache_file = null, ?Closure $validate = null): self {

        # Make sure given file exists!
        if( ! is_file( $file ) || ! is_readable( $file ) ) {
            throw new RuntimeException( "Unable to find or read environment file $file" );
        }

        # Remove from file list
        unset( $this->env_files[$file] );

        # Different files cannot share same cache
        if( $cache_file && $key = array_search( $cache_file, $this->env_files, true ) ) {
            throw new InvalidArgumentException( "Cache is already defined for the environment file: $key" );
        }

        # Save to file list
        $this->env_files[$file] = $cache_file;

        # Load environment variables
        return ( $cache_file ) ? $this->loadCache( $file, $cache_file, $validate ) : $this->loadFile( $file, $cache_file, $validate );

    }




    /**
     * Reload all previous environment files and build new cache.
     *
     * @return $this
     */
    public function reload(): self {
        foreach( $this->env_files as $file => $cache_file ) {
            if( is_file( $cache_file ) ) unlink( $cache_file );
            $this->load( $file, $cache_file );
        }
        return $this;
    }




    private function loadCache(string $file, string $cache_file, ?Closure $validate): self {

        # Load from cache if cache is still valid
        if( is_file( $cache_file ) && filemtime( $cache_file ) > filemtime( $file ) ) {
            $variables = $this->getCacheContent( $cache_file );
            $this->env = array_merge( $this->env, $variables );
        }

        # Check if cache had variables, else load from file
        return empty( $variables ) ? $this->loadFile( $file, $cache_file, $validate ) : $this;

    }




    /**
     * Load environment from cache file
     */
    private function getCacheContent(string $file): array {
        try {
            return json_decode( file_get_contents( $file ), true, 512, JSON_THROW_ON_ERROR );
        } catch ( JsonException ) {
            return [];
        }
    }




    /**
     * Load environment from file
     *
     * @param string $file                   Path to environment file
     * @param string|null $cache_file        Optional path to cache file
     * @param \Closure|null $validateClosure Optional validate closure
     *
     * @return \Rammewerk\Component\Environment\Environment
     */
    private function loadFile(string $file, ?string $cache_file, ?Closure $validateClosure): self {

        # Get environment variables
        $variables = ( new Reader() )->load( $file );

        # Convert environment types
        $variables = array_map( static function (string $value): string|bool|array|int|null {

            $value = trim( $value );

            if( $value === 'NULL' || $value === '' ) return null;

            # Convert boolean values
            if( in_array( $value, [ 'TRUE', 'FALSE', 'true', 'false' ], true ) ) {
                return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
            }

            # Convert integer values
            if( filter_var( $value, FILTER_VALIDATE_INT ) ) {
                return (int)$value;
            }

            # Create arrays
            if( str_starts_with( $value, '[' ) && str_ends_with( $value, ']' ) ) {
                $array = explode( ',', trim( $value, '[]' ) );
                return array_map( static fn($v) => trim( $v ), $array );
            }

            return $value;


        }, $variables );

        $this->env = array_merge( $this->env, $variables );

        if( $validateClosure ) $this->validate( $validateClosure );

        if( $cache_file ) $this->saveCache( $cache_file, $variables );

        return $this;

    }




    /**
     * Save environment variables to cache
     *
     * @param string $file
     * @param array $variables
     *
     * @return void
     */
    private function saveCache(string $file, array $variables): void {

        # Create cache folder if not existing
        $folder = dirname( $file );

        if( ! is_dir( $folder ) && ! mkdir( $folder, 0755, true ) && ! is_dir( $folder ) ) {
            throw new RuntimeException( "Failed to create caching folder: $folder" );
        }

        # Try saving cache
        try {
            file_put_contents( $file, json_encode( $variables, JSON_THROW_ON_ERROR ) );
        } catch ( Throwable $e ) {
            throw new RuntimeException( "Unable to save environment cache for $file", $e->getCode(), $e );
        }

    }




    /**
     * Get loaded environment variable
     *
     * Will always try dynamic variables first
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key): mixed {
        return $this->env[$key];
    }




    /**
     * Set environment variable dynamically
     *
     * @param string $key
     * @param bool|int|string|array $value
     *
     * @return void
     */
    public function set(string $key, bool|int|string|array $value): void {
        $this->env[$key] = $value;
    }


}