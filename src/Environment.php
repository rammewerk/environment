<?php

namespace Rammewerk\Component\Environment;

use Closure;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Throwable;

class Environment {

    /** @var array<string, string|bool|string[]|int|null> */
    private array $env = [];

    /** @var array<string, string|null> */
    private array $env_files = [];



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
     * @param Closure|null $validate  Optional validate closure to run before saving new cache
     *
     * @return static
     */
    public function load(string $file, ?string $cache_file = null, ?Closure $validate = null): static {

        # Make sure given file exists!
        if( !is_file( $file ) || !is_readable( $file ) ) {
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
        return ($cache_file) ? $this->loadCache( $file, $cache_file, $validate ) : $this->loadFile( $file, $cache_file, $validate );

    }



    /**
     * Reload all previous environment files and build new cache.
     *
     * @return static
     * @noinspection PhpUnused PhpUnused
     */
    public function reload(): static {
        foreach( $this->env_files as $file => $cache_file ) {
            if( $cache_file && is_file( $cache_file ) ) unlink( $cache_file );
            $this->load( $file, $cache_file );
        }
        return $this;
    }



    /**
     * Run validation
     *
     * @param Closure(Validator): void $callback
     *
     * @return void
     */
    public function validate(Closure $callback): void {
        $callback( new Validator( $this->env ) );
    }



    /**
     * @param string $file
     * @param string $cache_file
     * @param Closure|null $validate
     *
     * @return $this
     */
    private function loadCache(string $file, string $cache_file, ?Closure $validate): static {

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
     *
     * @param string $file
     *
     * @return array<string, string|bool|string[]|int|null>
     */
    private function getCacheContent(string $file): array {
        try {
            $v = json_decode( @file_get_contents( $file ) ?: '', true, 512, JSON_THROW_ON_ERROR );
            if( !is_array( $v ) ) return [];
            /** @var array<string, string|bool|string[]|int|null> $v */
            return $v;
        } catch( JsonException ) {
            return [];
        }
    }



    /**
     * Load environment from file
     *
     * @param string $file
     * @param string|null $cache_file
     * @param Closure|null $validateClosure
     *
     * @return $this
     */
    private function loadFile(string $file, ?string $cache_file, ?Closure $validateClosure): static {

        # Get environment variables
        $variables = new Reader()->load( $file );

        # Merge to environment variables list - overwrite existing ones
        $this->env = array_merge( $this->env, $variables );

        # Validate if closure is defined
        if( $validateClosure ) $this->validate( $validateClosure );

        # Save to cache if cache file is defined
        if( $cache_file ) $this->saveCache( $cache_file, $variables );

        return $this;

    }



    /**
     * Save environment variables to cache
     *
     * @param string $file
     * @param array<string, string|bool|string[]|int|null> $variables
     *
     * @return void
     */
    private function saveCache(string $file, array $variables): void {

        # Create cache folder if not existing
        $folder = dirname( $file );

        if( !is_dir( $folder ) && !mkdir( $folder, 0755, true ) && !is_dir( $folder ) ) {
            throw new RuntimeException( "Failed to create caching folder: $folder" );
        }

        # Try saving cache
        try {
            file_put_contents( $file, json_encode( $variables, JSON_THROW_ON_ERROR ) );
        } catch( Throwable $e ) {
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
     * @return string|bool|string[]|int|null
     */
    public function get(string $key): string|bool|array|int|null {
        return $this->env[$key];
    }



    /**
     * Set environment variable dynamically
     *
     * @param string $key
     * @param bool|int|string|string[] $value
     *
     * @return void
     */
    public function set(string $key, bool|int|string|array $value): void {
        $this->env[$key] = $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Typed getters
    |--------------------------------------------------------------------------
    */


    /**
     * Get loaded environment variable of type string
     * Returns null if not found or not a string
     *
     * @param string $key
     *
     * @return string|null
     * @noinspection PhpUnused PhpUnused
     */
    public function getString(string $key): ?string {
        $v = $this->get( $key );
        return is_string( $v ) ? $v : null;
    }



    /**
     * Get loaded environment variable of type int
     * Returns null if not found or not an integer
     *
     * @param string $key
     *
     * @return int|null
     * @noinspection PhpUnused PhpUnused
     */
    public function getInt(string $key): ?int {
        $v = $this->get( $key );
        if( !is_numeric( $v ) ) return null;
        return (int)round( (float)$v );
    }



    /**
     * Get loaded environment variable of type bool
     * Returns false if not found or not a bool
     *
     * @param string $key
     *
     * @return bool
     * @noinspection PhpUnused PhpUnused
     */
    public function getBool(string $key): bool {
        $v = $this->get( $key );
        return filter_var( $v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ?? false;
    }



    /**
     * Get loaded environment variable of type array
     * Returns null if not found or not an array
     *
     * @param string $key
     *
     * @return string[]|null
     * @noinspection PhpUnused PhpUnused
     */
    public function getArray(string $key): ?array {
        $v = $this->get( $key );
        return is_array( $v ) ? $v : null;
    }


}