<?php

declare(strict_types=1);

namespace DennisKoster\LighthouseTranslatable\Providers;

use DennisKoster\LighthouseTranslatable\GraphQL\Scalars\TranslatableString;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\ServiceProvider;
use Nuwave\Lighthouse\Events\RegisterDirectiveNamespaces;
use Nuwave\Lighthouse\Schema\TypeRegistry;

class LighthouseTranslatableProvider extends ServiceProvider
{
    public function boot(
        Dispatcher $dispatcher,
        TypeRegistry $typeRegistry,
    ): void {
        $typeRegistry->register(new TranslatableString());

        $dispatcher->listen(
            RegisterDirectiveNamespaces::class,
            fn () => [
                'DennisKoster\\LighthouseTranslatable\\Directives',
            ],
        );

        $this->publishes([
            __DIR__ . '/../config/lighthouse-translatable.php' => config_path('lighthouse-translatable.php'),
        ]);
    }

    public function register(): void
    {
        $this->app->make(Factory::class)->addExtension('graphql-stub', 'blade');

        $this->mergeConfigFrom(
            __DIR__ . '/../config/lighthouse-translatable.php', 'lighthouse-translatable'
        );
    }
}
