Rammewerk Environment
======================

A **fast**, **typed**, and **opinionated** environment variable handler designed for simplicity.

This package provides a streamlined, dependency-free approach to handling `.env` files in your project, offering both
speed and type safety. Unlike other solutions, Rammewerk Environment focuses on performance and ensures minimal exposure
risks by not injecting variables into $_ENV.

#### Why choose Rammewerk Environment?

- **Blazing fast parsing** of .env files, suitable for high-performance applications.
- **Type-safe outputs**: Automatically converts values to proper types like bool, int, null, or even array.
- **No hidden magic**: Variables are not automatically added to global environment arrays.
- **Dependency-free**: Minimal footprint for lightweight projects.
- **Extensible validation**: Easily validate required variables using closures.
- **Multiple file support**: Load and manage multiple .env files with ease.

***Note**: This package supports a simplified .env format, with specific rules for variable names, comments, and values.
See
[Limitations](#Limitations) for details.*

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

// Or use the built-in typed getters which returns true or false, defaults to false
$debug_mode = $env->getBool('DEBUG_MODE'); // 

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

Validating environment variables
---------------

```php
use Rammewerk\component\environment\src\Validator;

...
# Validate the setup of your environment variables.
$env->validate( static function(Validator $env) {
    $env->require('DEBUG_MODE')->isBoolean();
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
