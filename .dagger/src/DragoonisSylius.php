<?php

declare(strict_types=1);

namespace DaggerModule;

use Dagger\Attribute\DaggerFunction;
use Dagger\Attribute\DaggerObject;
use Dagger\Attribute\Doc;
use Dagger\Container;
use Dagger\Directory;
use Dagger\Service;

use function Dagger\dag;

#[DaggerObject]
#[Doc('A generated module for DragoonisSylius functions')]
class DragoonisSylius
{

    public function version(Directory $dir): Container
    {
        return dag()
            ->container()
            // FROM ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine
            ->from('ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine')
            // RUN php -v
            ->withExec(['php', '-v']);
    }


    #[DaggerFunction]
    #[Doc('static')]
    public function phpspec(Directory $dir): Container
    {
        return dag()
            ->container()
            ->from('ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine')
            // COPY --chown=sylius:sylius . /srv/sylius
            ->withMountedDirectory('/srv/sylius', $dir, 'sylius:sylius')
            ->withExec(['vendor/bin/phpspec'])
            ->withExec(['vendor/bin/phpstan']);
    }

    #[DaggerFunction]
    #[Doc('phpstan')]
    public function phpstan(Directory $dir): Container
    {
        return dag()
            ->container()
            ->from('ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine')
            ->withMountedDirectory('/srv/sylius', $dir, 'sylius:sylius')
            ->withExec(['vendor/bin/phpstan']);
    }

    #[DaggerFunction]
    #[Doc('static')]
    public function static(Directory $dir): Container
    {
        return dag()
            ->container()
            ->from('ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine')
            ->withMountedDirectory('/srv/sylius', $dir, 'sylius:sylius')
            ->withExec(['vendor/bin/phpspec'])
            ->withExec(['vendor/bin/phpstan']);

    }

    #[DaggerFunction]
    #[Doc('static')]
    public function phpspecOut(Directory $dir): string
    {
        return dag()
            ->container()
            ->from('ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine')
            // COPY --chown=sylius:sylius . /srv/sylius
            ->withMountedDirectory('/srv/sylius', $dir, 'sylius:sylius')
            ->withExec(['vendor/bin/phpspec'])
            ->withExec(['vendor/bin/phpstan'])
            ->stdout();
    }

    #[DaggerFunction]
    // @todo - create a base --with-exec=vendor/bin/phpstan demo.
    public function base(Directory $dir): Container
    {
        return dag()
            ->container()
            ->from('ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine')
            ->withMountedDirectory('/srv/sylius', $dir, 'sylius:sylius');
    }

    

    #[DaggerFunction]
    #[Doc('phpspec-with-base')]
    public function phpspecWithBase(Directory $dir): Container
    {
        return $this->base($dir)->withExec(['vendor/bin/phpspec']);
    }

    #[DaggerFunction]
    #[Doc('phpunit-with-env')]
    public function phpunitWithEnv(Directory $dir): Container
    {
        return $this->base($dir)
            ->withEnvVariable('APP_ENV', 'test_cached')
            ->withEnvVariable('PHP_DATE_TIMEZONE', 'Europe/Warsaw')
            ->withEnvVariable('APP_DEBUG', '0')
            ->withExec(['vendor/bin/phpunit']);
    }


    #[DaggerFunction]
    #[Doc('integration-base')]
    public function integrationBase(
        #[Doc('The directory to mount')]
        Directory $dir,
    ): Container
    {
        return dag()
            ->container()
            ->from('ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine')
            ->withMountedDirectory('/srv/sylius', $dir, 'sylius:sylius')

            ->withUser('root')
            ->withExec(['mv', '.docker/test/php.ini', '/usr/local/etc/php/php-cli.ini'])
            ->withExec(['apk', '--update', 'add', 'make', 'nodejs', 'npm', 'yarn'])
            ->withUser('sylius')

            ->withEnvVariable('APP_ENV', 'test_cached')
            ->withEnvVariable('PHP_DATE_TIMEZONE', 'Europe/Warsaw')
            ->withEnvVariable('APP_DEBUG', '0')
            ->withEnvVariable('DATABASE_URL', 'mysql://root:mysql@mysql/sylius_%kernel.environment%')
            ->withEnvVariable('SYLIUS_MESSENGER_TRANSPORT_MAIN_DSN', 'sync://')
            ->withEnvVariable('SYLIUS_MESSENGER_TRANSPORT_MAIN_FAILED_DSN', 'sync://')
            ->withEnvVariable('SYLIUS_MESSENGER_TRANSPORT_CATALOG_PROMOTION_REMOVAL_DSN', 'sync://')
            ->withEnvVariable('SYLIUS_MESSENGER_TRANSPORT_CATALOG_PROMOTION_REMOVAL_FAILED_DSN', 'sync://');
    }



    #[DaggerFunction]
    #[Doc('behat-cli')]
    public function behatCli(
        #[Doc('The directory to mount')]
        Directory $dir,
    ): Container
    {
        return $this->integrationBase($dir)
            ->withServiceBinding('mysql', $this->mysql())
            ->withExec(['make', 'init'])
            ->withExec(['make', 'behat-cli']);
    }

    #[DaggerFunction]
    #[Doc('behat-non-js')]
    public function behatNonJs(
        #[Doc('The directory to mount')]
        Directory $dir,
    ): Container
    {
        return $this->base($dir)
            ->withServiceBinding('mysql', $this->mysql())
            ->withExec(['make', 'init'])
            ->withExec(['make', 'behat-non-js']);
    }

    #[DaggerFunction]
    #[Doc('behat-js')]
    public function behatJs(
        #[Doc('The directory to mount')]
        Directory $dir,
    ): Container
    {
        return $this->integrationBase($dir)
            ->withServiceBinding('mysql', $this->mysql('5.6'))
            ->withExec(['make', 'init'])
            ->withExec(['make', 'behat-js']);
    }

    #[DaggerFunction]
    #[Doc('behat-js-postgres')]
    public function behatJsPostgres(
        #[Doc('The directory to mount')]
        Directory $dir,
    ): Container
    {
        $container = $this->integrationBase($dir);
        $container = $this->setUpPostgres($container);

        return $container
            ->withServiceBinding('mysql', $this->postgres('5.6')) // @todo - add version
            ->withExec(['make', 'init'])
            ->withExec(['make', 'behat-js']);
    }
    

    private function mysql(string $version = '5.7'): Service
    {
        return dag()->container()
            ->from("mysql:$version")
            ->withEnvVariable('MYSQL_ROOT_PASSWORD', 'mysql')
            ->withExposedPort(3306)
            ->asService();
    }

    // @todo - postgres version? docker image syntax
    private function postgres(string $version = '5.7'): Service
    {
        return dag()->container()
            ->from("postgres:$version")
            ->withEnvVariable('MYSQL_ROOT_PASSWORD', 'mysql')
            ->withExposedPort(3306)
            ->asService();
    }

    // @todo - DATABASE_URL decorator function

    private function setUpMySQL(Container $container): Container
    {
        return $container->withEnvVariable(
            'DATABASE_URL', 'mysql://root:mysql@mysql/sylius_%kernel.environment%'
        );
    }

    private function setUpPostgres(Container $container): Container
    {
        return $container->withEnvVariable(
            'DATABASE_URL', "pgsql://postgres:postgres@127.0.0.1/sylius?charset=utf8&serverVersion=$version" // @todo - add version
        );
    }

}
