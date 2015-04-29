vendor/bin/phpcs src   --extensions=php,hh --standard=vendor/leaphub/phpcs-symfony2-standard/leaphub/phpcs/Symfony2/ruleset.xml -p --colors
vendor/bin/phpcs tests --extensions=php,hh --standard=vendor/leaphub/phpcs-symfony2-standard/leaphub/phpcs/Symfony2/ruleset.xml -p --colors

vendor/bin/phpcs src   --extensions=php,hh --standard=PSR2 -p --colors
vendor/bin/phpcs tests --extensions=php,hh --standard=PSR2 -p --colors