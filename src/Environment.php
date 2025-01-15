<?php

namespace Rammewerk\Component\Environment;

use Closure;

class Environment {

    /** @var array<string, string|bool|string[]|int|null> */
    private array $env = [];



    /**
     * Load environment file
     *
     * Warning: If an environment key already exist, it will overwrite this!
     *
     * If cache file is defined it will load from cache or create new cache.
     * Supports loading of multiple env files, but these can never share same cache.
     *
     * @param string $file Path to environment file
     *
     * @return static
     */
    public function load(string $file): static {
        foreach (new Reader()->load($file) as $key => $value) {
            $this->env[$key] = $value;
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
        $callback(new Validator($this->env));
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
        $v = $this->get($key);
        return is_string($v) ? $v : null;
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
        $v = $this->get($key);
        if (!is_numeric($v)) return null;
        return (int)round((float)$v);
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
        $v = $this->get($key);
        return filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
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
        $v = $this->get($key);
        return is_array($v) ? $v : null;
    }


}