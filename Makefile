.DEFAULT_GOAL := help


help:
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'


##
## Commands
##---------------------------------------------------------------------------

clean:		## Clean all created artifacts
clean:
	git clean --exclude=.idea/ -fdx

cs:		## Fix CS
cs: vendor-bin/php-cs-fixer/vendor/bin/php-cs-fixer
	php vendor-bin/php-cs-fixer/vendor/bin/php-cs-fixer fix

build:		## Build the PHAR
build:
	# Cleanup existing artefacts
	rm -f fintsofx.phar

	# Remove unnecessary packages
	composer install --no-dev --prefer-dist

	# Re-dump the loader to account for the prefixing
	# and optimize the loader
	composer dump-autoload --classmap-authoritative --no-dev

	# Build the PHAR
	box build $(args)

	# Install back all the dependencies
	composer install


##
## Tests
##---------------------------------------------------------------------------

test:		## Run all the tests
test: tu

tu:		## Run the unit tests
tu: vendor/bin/phpunit
	php vendor/bin/phpunit

tc:		## Run the unit tests with code coverage
tc: vendor/bin/phpunit
	phpdbg -qrr vendor/bin/phpunit --coverage-html=dist/coverage --coverage-text

tm:		## Run Infection
tm:	vendor/bin/phpunit
	phpdbg -qrr vendor/bin/infection

##
## Rules from files
##---------------------------------------------------------------------------

composer.lock:
	composer update

vendor: composer.lock
	composer install

vendor/bamarni: composer.lock
	composer install

vendor/bin/phpunit: composer.lock
	composer install

vendor-bin/php-cs-fixer/vendor/bin/php-cs-fixer: vendor/bamarni
	composer bin php-cs-fixer install

fintsofx.phar: src vendor
	$(MAKE) build
