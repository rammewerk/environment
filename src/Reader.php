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
     * @return array<string, string|int|bool|null|string[]>
     */
    public function load(string $file): array {

        $variables = [];

        foreach ($this->getEnvFileContent($file) as $line) {

            $line = trim($line);

            # Skip empty lines and comments
            if (empty($line) || $line[0] === '#') continue;

            if (!str_contains($line, '=')) {
                throw new LogicException("Invalid line in environment file: $line. Line must be in format KEY=VALUE.");
            }

            $exploded = explode('=', $line);
            $key = array_shift($exploded);
            $value = implode('=', $exploded);

            if (!$this->validKey($key)) {
                throw new LogicException("Invalid key in environment file: $line. Key must be a-z, A-Z or underscore only.");
            }

            unset($line, $exploded);

            $value = $this->convertValueToType($value);
            $value = is_string($value) ? $this->removeQuotes(trim($value)) : $value;

            if (is_string($value) && str_starts_with($value, '[') && str_ends_with($value, ']')) {
                $value = $this->convertToArray($value);
            }

            $variables[$key] = $value;

        }

        return $variables;

    }



    /**
     * Convert environment value to array
     *
     * @return string[]
     */
    public function convertToArray(string $value): array {
        $value = array_map(static fn($v) => trim($v), explode(',', trim($value, '[]')));
        $value = array_filter($value, static fn($v) => $v !== '');
        return array_values($value);
    }



    /**
     * Convert environment value to type
     *
     * @param string $value
     *
     * @return bool|int|string|null
     */
    private function convertValueToType(string $value): null|bool|int|string {

        if ($value === 'NULL' || $value === '') return null;

        # Convert boolean values
        if (in_array($value, ['TRUE', 'FALSE', 'true', 'false'], true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        # Convert integer values
        if (filter_var($value, FILTER_VALIDATE_INT)) {
            return (int)$value;
        }

        return $value;

    }



    /**
     * @param string $file
     *
     * @return string[]
     */
    private function getEnvFileContent(string $file): array {
        if (is_file($file) && is_readable($file)) {
            return @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        }
        throw new RuntimeException('Unable to read environment file ' . $file);
    }



    /**
     * @param string $string
     *
     * @return bool
     */
    private function validKey(string $string): bool {
        return preg_match('/^[a-zA-Z_]+\w*$/', $string) === 1;
    }



    /**
     * Remove quotes from environment value
     *
     * @param string $value
     *
     * @return string
     */
    private function removeQuotes(string $value): string {
        if (strlen($value) <= 2) return $value;
        if (!str_starts_with($value, '"') && !str_starts_with($value, '\'')) return $value;
        if (!str_ends_with($value, '"') && !str_ends_with($value, '\'')) return $value;
        return substr(substr($value, 1), 0, -1);
    }


}