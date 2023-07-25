<?php

namespace Cher4geo35\ParseNews;

use Cher4geo35\ParseNews\Console\Commands\ParseNewsMakeCommand;
use Illuminate\Support\ServiceProvider;

class ParseNewsServiceProvider extends ServiceProvider
{

    public function boot()
    {
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
            __DIR__ . '/resources/sass' => resource_path('sass/vendor'),
        ], 'public');
    }

    public function register()
    {

    }
}
