<?php

declare(strict_types=1);

namespace DennisKoster\LighthouseTranslatable\Providers;

use DennisKoster\LighthouseTranslatable\GraphQL\Scalars\TranslatableString;
use Illuminate\Contracts\Events\Dispatcher;
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
    }
}
