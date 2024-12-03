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
    #[DaggerFunction]
    #[Doc('Returns a container that echoes whatever string argument is provided')]
    public function containerEcho(string $stringArg): Container
    {
        return dag()
            ->container()
            ->from('alpine:latest')
            ->withExec(['echo', $stringArg]);
    }

    #[DaggerFunction]
    #[Doc('version')]
    public function version(): Container
    {
        return dag()
            ->container()
            ->from('ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine')
            ->withExec(['php', '-v']);
    }

    #[DaggerFunction]
    #[Doc('static')]
    public function base(
        #[Doc('The directory to mount')]
        Directory $dir,
    ): Container
    {
        return dag()
            ->container()
            ->from('ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine')
            ->withMountedDirectory('/srv/sylius', $dir, 'sylius:sylius')
            
            ->withEnvVariable('APP_ENV', 'test_cached')
            ->withEnvVariable('PHP_DATE_TIMEZONE', 'Europe/Warsaw')
            ->withEnvVariable('APP_DEBUG', '0')
            ->withEnvVariable('DATABASE_URL', 'mysql://root:mysql@mysql/sylius_%kernel.environment%')
            ->withEnvVariable('SYLIUS_MESSENGER_TRANSPORT_MAIN_DSN', 'sync://')
            ->withEnvVariable('SYLIUS_MESSENGER_TRANSPORT_MAIN_FAILED_DSN', 'sync://')
            ->withEnvVariable('SYLIUS_MESSENGER_TRANSPORT_CATALOG_PROMOTION_REMOVAL_DSN', 'sync://')
            ->withEnvVariable('SYLIUS_MESSENGER_TRANSPORT_CATALOG_PROMOTION_REMOVAL_FAILED_DSN', 'sync://')
            
            ->withUser('root')
            ->withExec(['mv', '.docker/test/php.ini', '/usr/local/etc/php/php-cli.ini'])
            ->withExec(['apk', '--update', 'add', 'make'])
            ->withUser('sylius');
    }

    #[DaggerFunction]
    #[Doc('static')]
    public function static(
        #[Doc('The directory to mount')]
        Directory $dir,
    ): Container
    {
        return $this->base($dir)
            ->withExec(['make', 'static']);
    }

    #[DaggerFunction]
    #[Doc('phpunit-integration')]
    public function phpunitIntegration(
        #[Doc('The directory to mount')]
        Directory $dir,
    ): Container
    {
        return $this->base($dir)
            ->withServiceBinding('mysql', $this->db())
            ->withUser('root')
            ->withExec(['apk', '--update', 'add', 'nodejs', 'npm', 'yarn'])
            ->withUser('sylius')
            ->withExec(['make', 'phpunit-integration']);
    }

    private function db(): Service
    {
        return dag()->container()
            ->from('mysql:5.7')
            ->withEnvVariable('MYSQL_ROOT_PASSWORD', 'mysql')
            ->withExposedPort(3306)
            ->asService();
    }

}
