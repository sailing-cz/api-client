# api-client

Instalace klienta do vašeho PHP projektu:

```bash
composer require sailing-cz/api-client
```

Základní použití veřejných funkcí API:

```php
use Sailing\ApiClient\ApiClient;

$api = new ApiClient( 'your-software/1.0' );

$club = $api->getClubs();
```

Přihlášení uživatele k sailing.cz:

```php
$api = new ApiClient( 'your-software/1.0' );

$api->loginUser( $username, $password );

$members = $api->getMembers( '2103' );
```

Vytvoření systémového tokenu na základě uživatelského přihlášení:

```php
$api = new ApiClient( 'your-software/1.0' );

my $systemToken = $api->createSystemToken( $username, $password, 'my-new-secret', 'my-software-token-1' );

// store $systemToken in db or somewhere
```

Přístup pomocí systémového tokenu:

```php
$api = new ApiClient( 'your-software/1.0', FALSE, '2103' );

$api->loginSystem( $systemToken, $systemSecret );

$api->activateLicense( '2103-0847' );
```
