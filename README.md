translation tool
================
[![Quality Assurance](https://github.com/mastercad/TranslationTool/actions/workflows/quality_assurance.yml/badge.svg)](https://github.com/mastercad/TranslationTool/actions/workflows/quality_assurance.yml) [![Tag Release](https://github.com/mastercad/TranslationTool/actions/workflows/tag_release.yml/badge.svg)](https://github.com/mastercad/TranslationTool/actions/workflows/tag_release.yml) [![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=mastercad_TranslationTool&metric=alert_status)](https://sonarcloud.io/dashboard?id=mastercad_TranslationTool) [![Deploy Application](https://github.com/mastercad/translation-tool/actions/workflows/deploy_application.yml/badge.svg)](https://github.com/mastercad/translation-tool/actions/workflows/deploy_application.yml)


A Symfony project created on December 27, 2016, 12:49 pm.

Steps to install Translation Tool:

required packages:
------------------
php xml
php zip (base in spl version 5.x)

for php-xml execute following command
apt install php-xml

For PHP7.x, the zip packet must be installed separately
apt install php7.0-zip

checkout project:
-----------------

git clone git@github.com:mastercad/translation-tool.git
cd translation-tool

composer install

yarn install
yarn run encore --production

bin/console assets:install

start server:
-------------

php bin/console server:start

start server in debug mode:
---------------------------
php -dxdebug.remote_autostart=On bin/console server:start

stop server:
------------
php bin/console server:stop

run project with php built in server
------------------------------------
http://10.140.1.71:8000/

php -S 0.0.0.0:8000 web/app.php

initial start with static translation:
--------------------------------------
- archive the whole translation folder (*.xlf files)
- import the zip archive with translation tool (@TODO)
- call static translation page of translation tool and select the source language, the needed translation file and the translation yout want to translate

troubleshooting:
----------------
- Problem:
    Cache directory "/app/var/cache/prod" is not writable

- Solution:
    php /app/bin/console c:w --env=prod

- Problem:
    [2017-10-30 19:55:47] request.CRITICAL: Uncaught PHP Exception Symfony\Component\Debug\Exception\FatalErrorException: "Error: Class Twig_Loader_Filesystem contains 1 abstract method and must therefore be declared abstract or implement the remaining methods (Twig_LoaderInterface::getSource)" at /app/vendor/twig/twig/lib/Twig/Loader/Filesystem.php line 17 {"exception":"[object] (Symfony\\Component\\Debug\\Exception\\FatalErrorException(code: 0): Error: Class Twig_Loader_Filesystem contains 1 abstract method and must therefore be declared abstract or implement the remaining methods (Twig_LoaderInterface::getSource) at /app/vendor/twig/twig/lib/Twig/Loader/Filesystem.php:17)"} []
    [2017-10-30 19:55:47] request.CRITICAL: Exception thrown when handling an exception (Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException: Circular reference detected for service "twig.controller.exception", path: "twig.controller.exception -> twig -> twig.loader". at /app/var/cache/prod/classes.php line 3440) {"exception":"[object] (Symfony\\Component\\DependencyInjection\\Exception\\ServiceCircularReferenceException(code: 0): Circular reference detected for service \"twig.controller.exception\", path: \"twig.controller.exception -> twig -> twig.loader\". at /app/var/cache/prod/classes.php:3440)"} []
    [2017-10-30 19:55:47] php.CRITICAL: Uncaught Exception: Circular reference detected for service "twig.controller.exception", path: "twig.controller.exception -> twig -> twig.loader". {"exception":"[object] (Symfony\\Component\\DependencyInjection\\Exception\\ServiceCircularReferenceException(code: 0): Circular reference detected for service \"twig.controller.exception\", path: \"twig.controller.exception -> twig -> twig.loader\". at /app/var/cache/prod/classes.php:3440, Symfony\\Component\\Debug\\Exception\\FatalErrorException(code: 0): Error: Class Twig_Loader_Filesystem contains 1 abstract method and must therefore be declared abstract or implement the remaining methods (Twig_LoaderInterface::getSource) at /app/vendor/twig/twig/lib/Twig/Loader/Filesystem.php:17)"} []

- Solution
    php /app/bin/console c:w --env=prod
