Rammewerk Environment
======================

A simple and fast environment variable handler for projects.

This package is a different approach to handle environment variables in your project:

* Parses and automatically caches .env file
* Will NOT add variables to $_ENV - as it might lead to exposing values if you are not careful with your
  debugging.
* No other dependencies - small size.
* Will automatically convert values to types like boolean, integer, null and even array (read more below)
* Support closure to validate environment variables
* Includes caching for even faster loading.
* Support for multiple files

**Important: There are some limitations to the .env file format. See below.**

Getting Started
---------------

```
$ composer require rammewerk/environment
```

```php
use Rammewerk\component\environment\src\Environment;

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
$env->load( ROOT_DIR . '.env-app');

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
use Rammewerk\component\environment\src\Validator;

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

Limitations
---------------
This is a simple env parser. You will need to format your env-files accordingly:

### Variable names

Environment variable names must consist solely of letters, digits, and the
underscore ( _ ) and must not begin with a digit.

### Comments

Comments are **only** allowed on new lines, never on the same line as variables.

```dotenv
# This is a valid comment
USER=John # Comment like this is not allowed!
```

### Variable values

Values can be quoted.

```dotenv
# Values can be quoted. These are all the same values:
KEY1=value
KEY2='value'
KEY3="value"
```

### Values will be trimmed and converted to types

```dotenv
# Values will be automatically trimmed. This is the same as KEY2='HELLO'
KEY4=' HELLO '

# TRUE or FALSE will be converted to valid boolean type in PHP. If you use quotes, it will be converted to string.
KEY5=TRUE

# An interger value will be converted to a valid PHP interger. If you use quotes, it will be converted to string.
KEY6=120

# Empty string '' or NULL will be converted to PHP NULL value.
KEY7=NULL

# Add commaseparated string inside brackets to convert to array of strings
KEY9='[value1,value2,value3]'
```

Tips
---------------
A `new Environment()` will return a new instance of the class. So, if you use a dependency injection container or
similar, consider making the Environment class a shared instance. Or make your own singleton wrapper.

## Typed getters

You can use typed getters to get the value of a key as a specific type. For example:

```php
$env->getString('KEY1'); // Returns string or null
$env->getInt('KEY2'); // Returns int or null
$env->getFloat('KEY3'); // Returns float or null
$env->getBool('KEY4'); // Returns bool
$env->getArray('KEY5'); // Returns array or null
```

If the value is not a string, int, float, bool or array, the getter will return null.
