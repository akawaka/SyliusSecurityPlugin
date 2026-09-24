<p align="center">
    <a href="https://sylius.com" target="_blank">
        <picture>
          <source media="(prefers-color-scheme: dark)" srcset="https://media.sylius.com/sylius-logo-800-dark.png">
          <source media="(prefers-color-scheme: light)" srcset="https://media.sylius.com/sylius-logo-800.png">
          <img alt="Sylius Logo." src="https://media.sylius.com/sylius-logo-800.png">
        </picture>
    </a>
</p>

<h1 align="center">Sylius Security Plugin</h1>

<p align="center">Restricts access to the Sylius administration contexts with administrator roles.</p>

## Features

- Each admin context (Catalog, Sales, Customers, Marketing, Configuration, Administration) is protected by two roles:
  `ROLE_ADMIN_<CONTEXT>_READ` (index/show pages) and `ROLE_ADMIN_<CONTEXT>_WRITE` (every other action, implies READ).
- `ROLE_SUPER_ADMIN` grants access to everything.
- **Deny by default**: an administrator without any context role only reaches the dashboard.
- Every administrator can still edit their own account ("My account"), without being able to change their own roles
  unless they have write access to the Administration context.
- Administrators can only change their own password: on another administrator's form, the password field is replaced
  by a "Reset password" button sending them the Sylius password reset email.
- Main menu entries the administrator cannot access are hidden.
- A roles multi-select replaces the Sylius Plus placeholder on the administrator form.
  Only super admins can grant `ROLE_SUPER_ADMIN`; roles not managed by the plugin (`ROLE_API_ACCESS`...) are preserved.

## Installation

1. Require the plugin and register the bundle in `config/bundles.php`:

    ```php
    AKAWAKA\SyliusSecurityPlugin\AkawakaSyliusSecurityPlugin::class => ['all' => true],
    ```

1. Import the plugin configuration **after** the Sylius configuration (e.g. at the end of `config/packages/_sylius.yaml`):

    ```yaml
    imports:
        - { resource: "@AkawakaSyliusSecurityPlugin/config/config.yaml" }
    ```

1. Import the plugin admin routes (`config/routes/akawaka_sylius_security.yaml`):

    ```yaml
    akawaka_sylius_security_admin:
        resource: "@AkawakaSyliusSecurityPlugin/config/routes/admin.yaml"
        prefix: /%sylius_admin.path_name%
    ```

1. Grant the super admin role to at least one administrator, otherwise nobody can manage roles anymore:

    ```bash
    bin/console akawaka:security:grant-super-admin admin@example.com
    ```

## Configuration

Admin routes are mapped to contexts by route name prefix; the longest matching prefix wins, public prefixes included.
Sylius admin routes (`sylius_admin_*`) that are neither public nor mapped are reserved to super admins; other routes
(e.g. your own admin routes) are not restricted until you map them. Defaults are in `src/DependencyInjection/Configuration.php`:
configured public prefixes are added to the default ones, and configured contexts are merged with the default ones by code
(redefining a default context replaces its whole prefix list).

```yaml
akawaka_sylius_security:
    super_admin_role: ROLE_SUPER_ADMIN
    # GET/HEAD requests on matching routes only require the READ role
    read_route_pattern: '/_(index|show|history|statistics)$/'
    public_route_prefixes: [app_admin_help]
    contexts:
        catalog:
            route_prefixes: [sylius_admin_product, sylius_admin_taxon, sylius_admin_inventory, app_admin_brand]
        # a new context gets ROLE_ADMIN_<CODE>_READ / _WRITE roles;
        # add akawaka_sylius_security.ui.context.<code> to your translations
        suppliers:
            route_prefixes: [app_admin_supplier]
```

### Limitations

- The admin API (`/api/v2/admin`) is not restricted yet.
- Grid buttons (create, edit, delete) are still displayed to READ-only administrators; the actions themselves are denied.

## Development

### Quickstart Installation

### Traditional

1. From the plugin skeleton root directory, run the following commands:

    ```bash
    $ (cd tests/Application && yarn install)
    $ (cd tests/Application && yarn build)
    $ (cd tests/Application && APP_ENV=test bin/console assets:install public)

    $ (cd tests/Application && APP_ENV=test bin/console doctrine:database:create)
    $ (cd tests/Application && APP_ENV=test bin/console doctrine:schema:create)
    # Optionally load data fixtures
    $ (cd tests/Application && APP_ENV=test bin/console sylius:fixtures:load --no-interaction)
    ```

    To be able to set up a plugin's database, remember to configure your database credentials in `tests/Application/.env` and `tests/Application/.env.test`.

1. Run your local server:

      ```bash
      symfony server:ca:install
      symfony server:start --dir=tests/Application/public
      ```

1. Open your browser and navigate to `https://localhost:8000`.

### Docker

1. Execute `docker compose up -d`

2. Initialize plugin `docker compose exec app make init`

3. See your browser `open localhost`

### Usage

### Running plugin tests

  - PHPUnit

    ```bash
    vendor/bin/phpunit
    ```

  - PHPSpec

    ```bash
    vendor/bin/phpspec run
    ```

  - Behat (non-JS scenarios)

    ```bash
    vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"
    ```

  - Behat (JS scenarios)
 
    1. [Install Symfony CLI command](https://symfony.com/download).
 
    2. Start Headless Chrome:
    
      ```bash
      google-chrome-stable --enable-automation --disable-background-networking --no-default-browser-check --no-first-run --disable-popup-blocking --disable-default-apps --allow-insecure-localhost --disable-translate --disable-extensions --no-sandbox --enable-features=Metal --headless --remote-debugging-port=9222 --window-size=2880,1800 --proxy-server='direct://' --proxy-bypass-list='*' http://127.0.0.1
      ```
    
    3. Install SSL certificates (only once needed) and run test application's webserver on `127.0.0.1:8080`:
    
      ```bash
      symfony server:ca:install
      APP_ENV=test symfony server:start --port=8080 --dir=tests/Application/public --daemon
      ```
    
    4. Run Behat:
    
      ```bash
      vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"
      ```
    
  - Static Analysis
      
    - PHPStan
    
      ```bash
      vendor/bin/phpstan analyse -c phpstan.neon -l max src/  
      ```

  - Coding Standard
  
    ```bash
    vendor/bin/ecs check
    ```

### Opening Sylius with your plugin

- Using `test` environment:

    ```bash
    (cd tests/Application && APP_ENV=test bin/console sylius:fixtures:load)
    (cd tests/Application && APP_ENV=test bin/console server:run -d public)
    ```
    
- Using `dev` environment:

    ```bash
    (cd tests/Application && APP_ENV=dev bin/console sylius:fixtures:load)
    (cd tests/Application && APP_ENV=dev bin/console server:run -d public)
    ```
