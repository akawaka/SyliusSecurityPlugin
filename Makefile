phpunit:
	vendor/bin/phpunit

phpspec:
	vendor/bin/phpspec run --ansi --no-interaction -f dot

phpstan:
	vendor/bin/phpstan analyse

install:
	composer install --no-interaction --no-scripts

backend:
	tests/Application/bin/console sylius:install --no-interaction
	tests/Application/bin/console sylius:fixtures:load default --no-interaction
	tests/Application/bin/console akawaka:security:grant-super-admin sylius@example.com

frontend:
	(cd tests/Application && yarn install --pure-lockfile)
	(cd tests/Application && yarn build)

behat:
	vendor/bin/behat --colors --strict --no-interaction -vvv -f progress

init: install backend frontend

ci: init phpstan phpspec phpunit behat

integration: init behat

static: install phpspec phpunit phpstan
