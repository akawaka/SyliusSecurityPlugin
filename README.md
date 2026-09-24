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
- The Sylius Plus "Administration > Roles" menu placeholder is removed.
- A roles checkboxes table replaces the Sylius Plus placeholder on the administrator form.
  Only super admins can grant `ROLE_SUPER_ADMIN`; roles not managed by the plugin (`ROLE_ADMINISTRATION_ACCESS`...) are preserved.
- Generic roles (e.g. `ROLE_API_ACCESS`) can be configured to be assigned on the administrator form too.
- **Administrator actions history**: every page consulted and every modification made by an administrator, as well as
  their logins, failed logins and logouts, are saved in database (date, administrator, route, context, URL, HTTP status,
  IP, user agent). They can be browsed in "Configuration > Administrator actions" with the `ROLE_ADMIN_AUDIT_READ` role,
  and purged with the `akawaka:security:purge-admin-action-logs` command.

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

1. Create the administrator actions table:

    ```bash
    bin/console doctrine:migrations:migrate
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
    # generic roles displayed below "Super admin" on the administrator form, as role => label (translation key or text);
    # ROLE_ADMINISTRATION_ACCESS, the super admin role and context roles cannot be configured here
    roles:
        ROLE_API_ACCESS: akawaka_sylius_security.ui.role.api_access
    contexts:
        catalog:
            route_prefixes: [sylius_admin_product, sylius_admin_taxon, sylius_admin_inventory, app_admin_brand]
        # a new context gets ROLE_ADMIN_<CODE>_READ / _WRITE roles;
        # add akawaka_sylius_security.ui.context.<code> to your translations
        suppliers:
            route_prefixes: [app_admin_supplier]
    admin_action_log:
        enabled: true
        # false to only log modifications (non GET/HEAD requests), logins and logouts
        log_read_actions: true
        # default value of the purge command --days option
        retention_days: 90
        # read requests on these route prefixes are not logged, added to the default ones (partials, AJAX, profiler, the history itself...)
        excluded_route_prefixes: [app_admin_autocomplete]
        # firewalls whose failed logins are logged
        firewalls: [admin, new_api_admin_user]
```

### Purging the administrator actions history

Logs are never deleted automatically, schedule the purge command (e.g. with a daily cron):

```bash
# deletes the logs older than admin_action_log.retention_days (90 by default)
bin/console akawaka:security:purge-admin-action-logs
# or older than a given number of days
bin/console akawaka:security:purge-admin-action-logs --days=30
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
