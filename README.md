<p align="center">
    <img src="docs/logo-akawaka.png" style="width: 500px;">
</p>

<p align="center">
AKAWAKA is a software development company specializing in e-commerce platforms and custom web applications. 
We help businesses evolve or rebuild their platforms with a pragmatic approach: improving existing solutions or starting from scratch, notably with Sylius. 
Our team also handles data migration, SEO preservation, and business continuity. 
With strong expertise in PHP and Symfony, Akawaka builds web solutions tailored to our clients’ business needs.
</p>

<h1 align="center">Sylius Security Plugin</h1>

<p align="center">Restricts access to the Sylius administration contexts with administrator roles.</p>

## Compatibility

| Plugin | Sylius | PHP   |
|--------|--------|-------|
| 2.x    | ^2.0   | ^8.2  |
| 1.x    | ~1.14  | ^8.1  |

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

1. Add the [Akawaka Flex recipes](https://github.com/akawaka/symfony-recipes) endpoint to your project, then require the plugin:

    ```bash
    composer config --json extra.symfony.endpoint '["https://api.github.com/repos/akawaka/symfony-recipes/contents/index.json?ref=flex/main","flex://defaults"]'
    composer require akawaka/sylius-security-plugin
    ```

    Symfony Flex registers the bundle and creates `config/packages/akawaka_sylius_security.yaml` and
    `config/routes/akawaka_sylius_security.yaml` (see [Manual installation](#manual-installation-without-flex) for their content).

1. Create the administrator actions table:

    ```bash
    bin/console doctrine:migrations:migrate
    ```

1. Grant the super admin role to at least one administrator, otherwise nobody can manage roles anymore:

    ```bash
    bin/console akawaka:security:grant-super-admin admin@example.com
    ```

### Manual installation (without Flex)

Replace the first step above with:

1. Require the plugin and register the bundle in `config/bundles.php`:

    ```php
    AKAWAKA\SyliusSecurityPlugin\AkawakaSyliusSecurityPlugin::class => ['all' => true],
    ```

1. Import the plugin configuration **after** the Sylius configuration (e.g. in `config/packages/akawaka_sylius_security.yaml`,
   loaded after `config/packages/_sylius.yaml`):

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

```bash
make init            # start the containers (PHP, MySQL, nginx, mailhog) and install dependencies
make database-init   # create the test application database, run migrations and load the Sylius fixtures
make frontend        # build the assets of the test application (only to browse it)
make test            # ECS, PHPStan (level max), PHPUnit
```

Project run at [http://plugin.localhost](http://plugin.localhost)

## License

MIT.
