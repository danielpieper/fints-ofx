.DEFAULT_GOAL := help


help:
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'


##
## Commands
##---------------------------------------------------------------------------

clean:		## Clean all created artifacts
clean:
	git clean --exclude=.idea/ -fdx

build:		## Build the PHAR
build:
	# Cleanup existing artefacts
	rm -f dist/fints-ofx.phar

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
test: tu md cs cpd

tu:		## Run the unit tests
tu: vendor/bin/phpunit
	php vendor/bin/phpunit

tc:		## Run the unit tests with code coverage
tc: vendor/bin/phpunit
	phpdbg -qrr vendor/bin/phpunit --coverage-html=dist/coverage --coverage-text

tm:		## Run Infection
tm:	vendor/bin/phpunit
	phpdbg -qrr vendor/bin/infection

md:		## Run mess detector
md:	vendor/bin/phpmd
	php vendor/bin/phpmd src/ text phpmd.xml

cs:		## Run code sniffer
cs:	vendor/bin/phpcs
	php vendor/bin/phpcs --standard=PSR2 --extensions=php --colors -np src/

cpd:		## Run copy paste detector
cpd:	vendor/bin/phpcpd
	php vendor/bin/phpcpd src/

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

vendor/bin/phpcpd: composer.lock
	composer install

vendor/bin/phpcs: composer.lock
	composer install

vendor/bin/phpmd: composer.lock
	composer install

fints-ofx.phar: src vendor
	$(MAKE) build
