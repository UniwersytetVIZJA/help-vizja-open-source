build:
	docker-compose -f docker-compose.yaml build

fixload:
	@php bin/console doctrine:database:drop --force -q
	@php bin/console doctrine:database:create -q
	@make migall
	@php bin/console doctrine:fixtures:load -n

migall:
	@make migdiff
	@make migpush

migdiff:
	@php bin/console doctrine:cache:clear-metadata -n
	@php bin/console doctrine:migrations:diff --allow-empty-diff -n

migpush:
	@php bin/console doctrine:migrations:migrate --allow-no-migration -n
	@rm -f migrations/*

restart:
	@make stop
	@make start

start:
	@docker-compose -f docker-compose.yaml up -d

stop:
	@docker-compose -f docker-compose.yaml down --remove-orphans

clear:
	@php bin/console cache:clear

fixtures:
	@php bin/console doctrine:fixtures:load -n

poedit:
	@php bin/console translation:extract en --force --format=po --domain=messages --prefix="NEW_"

cronApplication:
	@php bin/console app:clean-old-applications

cronOfficeRegistration:
	@php bin/console app:clean-old-office-registration

cronRegistration:
	@php bin/console app:clean-old-registration

cronOfficeRegisteredStudents:
	@php bin/console app:clean-null-office-registed-students

cronVerbisDataUpdate:
	@php bin/console app:update-data-verbis

cronOfficeRegistrationReminder:
	@php bin/console app:office-registration:send-reminders

cronRegistrationReminder:
	@php bin/console app:registration:send-reminders

phpdoc:
	@php /usr/local/bin/phpdoc run -d src -t docs

migration-diff:
	php bin/console doctrine:migrations:diff

migration-migrate:
	php bin/console doctrine:migrations:migrate
