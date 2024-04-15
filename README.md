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

Vytvoení systémového tokenu na základě uživatelského přihlášení:

```php
$api = new ApiClient( 'your-software/1.0' );

$api->loginUser( $username, $password );
```
