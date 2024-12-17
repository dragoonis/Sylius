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
    #[Doc('static')]
    public function static(Directory $dir): Container
    {
        return dag()
            ->container()
            // FROM ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine
            ->from('ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine')
            // COPY --chown=sylius:sylius . /srv/sylius
            ->withMountedDirectory('/srv/sylius', $dir, 'sylius:sylius')
            // RUN vendor/bin/phpspec
            ->withExec(['vendor/bin/phpspec'])
            // RUN vendor/bin/phpunit
            ->withExec(['vendor/bin/phpstan']);
    }

    #[DaggerFunction]
    #[Doc('static')]
    public function static2(Directory $dir): Container
    {
        return dag()
            ->container()
            ->from('ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine')
            ->withMountedDirectory('/srv/sylius', $dir, 'sylius:sylius')
            ->withExec(['vendor/bin/phpspec'])
            ->withExec(['vendor/bin/phpstan']);
    }

    public function base(Directory $dir): Container
    {
        return dag()
            ->container()
            ->from('ghcr.io/sylius/sylius-php:8.2-fixuid-xdebug-alpine')
            ->withMountedDirectory('/srv/sylius', $dir, 'sylius:sylius')
    }

}
