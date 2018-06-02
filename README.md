# php-deployer

A deploy service for shared php-based hosting accounts such as HostMonster, BlueHost, etc...

Receives openssl RSA signed deploy requests, downloads the repos from github, and extracts files from a source directory to a destination directory on the server.

## Install

1. Download this [php-deployer repo](https://github.com/jayzawrotny/php-deployer/archive/master.zip)
2. Rename the config.sample.php to config.secret.php and update the values as needed.
3. Upload the src/deploy/server folder to a PHP web server
4. Upload openssl RSA public keys in the folder specified by `KEY_DIR` in `config.secret.php`.
5. Copy the file `targets/sample.disabled.php` to `targets/<<name>>.enabled.php`. The name should correspond to the `name` POST param.
6. Update `before_deploy` and `after_deploy` functions to your liking.

## Usage

Send POST requests to `index.php`:

```json
{
  "name": "php-deployer",
  "repo": "jazyawrotny/php-deployer",
  "branch": "master",
  "src_dir": "src/deploy/server",
  "dest_dir": "public_html/tools/deploy",
  "timeout": 1234567890,
  "hash": "jazyawrotny/php-deployer|master|12334567890",
  "signature": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
}
```

Generate signatures with an openssl RSA private key file. You can generate a private and public keypair using the following commands:

```
openssl genrsa -out resources/deploy.secret.pem 1024
openssl rsa -in resources/deploy.secret.pem -pubout -out resources/deploy.secret.pub
```

You can use the [clj-deploy](https://github.com/jayzawrotny/clj-deploy) to send signed server requests to the deploy server.

### Arguments

**name**

Corresponds to `targets/$name.enabled.php`. Used to white list deploy servers along with `before_deploy` and `after_deploy` hook functions.

**repo**

String name of a github repo in the format of `username/repo-name`.

**src_dir**

String name of source directory relative to the repo such as a "dist" or "public" directory.

**dest_dir**

String name of destination directory relative to server like a `public_html/$website_name` folder.

**timeout**

Number of seconds since the unix epoch when this request token expires. Should always be in the future.

**hash**

A string combining the `repo`, `branch`, and `timeout` POST params.

**signature**

A signed version of the hash using an openssl RSA private key.

### Config

Stored in `src/deploy/server/config.secret.php` and should define the following keys:

```php
$CONFIG = array(
  'ROOT_DIR' => '/users/my-account/public_html',
  'KEY_DIR' => '/deploy_keys',
);
```

**ROOT_DIR**

A directory path string relative to the server. Typically this should be your `public_html` or `/var/www` directory. This is to ensure files are not deployed outside your `$ROOT_DIR` directory.

**KEY_DIR**

A directory path string relative to the server. **This folder stores your public keys and should be outside of your `public_html` folder so that your public keys are not accessible from the web.**


### API

In each `targets/$name.enabled.php` file you can define `before_deploy` and `after_deploy` hook functions.

```php
function before_deploy ($params) {
  // run tasks to do before the deploy process begins here
  // For example you may want to delete the dest dir to start each deploy fresh
}
```

```php
function after_deploy ($params) {
  // run tasks to do after the deploy has completed
  // For example you may want to generate a sitemap or robots.txt here
}
```

**$params**

```php
array(
  "name" => string,
  "repo" => string,
  "branch" => string,
  "src_dir" => string,
  "dest_dir" => string,
  "timeout" => int,
  "hash" => string,
  "signature" => string,
  "source" => string,
  "dest" => string,
);
```

Refers to the POST params with the addition of `source` and `dest` fields.

**source**

The folder representing where the repo to download for extraction.

**dest**

The folder representing where the repo will deploy to.


### Utils

The following utils are available to use in the `before_deploy` and `after_deploy` hook functions.

```php
bool xcopy ( string $source, string $dest [, int $permissions = 0755 ] );
```

Recursively copies files from the `$source` to the `$dest` directory with the provided permissions or `0755` by default.

**$source**

Source directory name to copy.

**$dest**

Destination directory to recursively copy files into.

**$permissions**

Ocal integer of the permissions [mode](http://us3.php.net/manual/en/function.chmod.php) to apply to each file copied. Defaults to `0755`.


```php
bool xrmdir ( string $dir );
```

Recursively deletes a directory and all its files from the filesystem.

**$dir**

Target directory string to remove.

## Credits
The OpenSSL auth code was heavily inspired by [jasny's php-pubkey](https://github.com/jasny/php-pubkey).
