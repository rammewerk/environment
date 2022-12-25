Rammewerk Environment
======================

A simple, fast and yet powerful environment variable handler for projects.

* Parses and caches .env file
* No other dependencies - small size.
* Will convert values like boolean, integer, even array (read more below)
* Support closure to validate environment variables
* Includes caching for even faster loading.
* Does not add to variables $_ENV - which might lead to exposing values if you are not careful.
* Support for multiple files

Getting Started
---------------

```
$ composer require rammewerk/environment
```

```php
use Rammewerk\Component\Environment\Environment;

$env = new Environment();

// Load from environment variable file
$env->load( ROOT_DIR . '.env');

// Get value from environment
$debug_mode = $env->get( 'DEBUG_MODE' );
```

Support for multiple .env files
---------------
You can add multiple environment files or create new variables on the fly.

A file does not necessarily need to be .env. For instance, a file.txt will also work as long as it is correctly
formatted.

```php
$env->load( ROOT_DIR . '.env');

# Warning: will overwrite value for keys that exist in both files.
$env->load( ROOT_DIR . '.env.app');

# You can also define new variables or overwrite values on the fly.
$env->set('NEW_KEY', 'new value');
```

Caching
---------------

```php
# You can load files from wherever you want.
$env_file = ROOT_DIR . '/app/.env';

# You decide where to put the cache.
$cache_file = CACHE_DIR . 'env-cache.json';

# Load the environment variables
# If cache does not exist it will create one.
# If cache exist, and is newer than the env_file, it will load from cache.
$env->load( $env_file, $cache_file);

# You can reload the file at any time.
$env->load( $env_file, $cache_file);

# But you cannot define the same cache file path for a different env file.
# This will throw a \RuntimeException()
$env->load( $some_other_env_file, $cache_file);

# And, if you want to reload and build new cache for all previous loaded env-files, you can do so
$env->reload();
```

Validating environment variables
---------------

```php
use \Rammewerk\Component\Environment\Validator;

...

# Validate variables when loading from file.
# If loaded from cache - it will not run validation.
$env->load( $env_file, $cache_file, static function( Validator $env) {
    $env->require('DEBUG_MODE')->isBoolean();
});

# You can validate later of in you want.
# Will validate straight away, not only on load.
$env->validate( static function(Validator $env) {
    $env->ifPresent('APP_URL')->endWith('/');
})

```

Limitation and automatic types
---------------
This is a simple env parser. You will need to format your env-files accordingly:

```dotenv
# Comments are only allowed on new lines, never on the same line as variables.

# Values can be quoted. These are all the same values:
KEY1=value
KEY2='value'
KEY3="value"

# Values will be automatically trimmed. This is the same as KEY2='HELLO'
KEY4=' HELLO '

# TRUE or FALSE will be converted to valid boolean type in PHP
KEY5=TRUE

# An interger value will be converted to a valid PHP interger.
# Also, if you use quotes.
KEY6=120

# Empty string '' or NULL will be converted to PHP NULL value.
KEY7=NULL

# Add commaseparated string inside brackets to convert to array
KEY9='[value1,value2,value3]'
```

Tips
---------------
A `new Environment()` will return a new instance of the class. So, if you use a dependency injection container or
similar, consider making the Environment class a shared instance. Or make your own singleton wrapper.