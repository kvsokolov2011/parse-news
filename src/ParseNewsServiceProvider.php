<?php

namespace Cher4geo35\ParseNews;

use Cher4geo35\ParseNews\Console\Commands\ParseNewsMakeCommand;
use Illuminate\Support\ServiceProvider;

class ParseNewsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Подгрузка миграций.
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

         // Подгрузка шаблонов.
         $this->loadViewsFrom(__DIR__ . '/resources/views', 'parse-news');

        // Подгрузка роутов.
        $this->loadRoutesFrom(__DIR__ . '/routes/admin.php');

        // Console.
        if ($this->app->runningInConsole()) {
            $this->commands([
                ParseNewsMakeCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/resources/js/components' => resource_path('js/components/vendor/parse-news'),
        ], 'public');
    }

    public function register()
    {

    }
}
