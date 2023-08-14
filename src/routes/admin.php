<?php

use App\ProgressParseNews;
use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'App\Http\Controllers\Vendor\ParseNews\Admin',
    'middleware' => ['web', 'management'],
    'as' => 'admin.',
    'prefix' => 'admin',
], function () {
    Route::get('/parse-news', 'ParseNewsController@index')
            ->name('parse-news.index');

    Route::post('/parse-news/create', 'ParseNewsController@create')
        ->name('parse-news.create');

    Route::get('/parse-news/get-progress', 'ParseNewsController@getProgress')
        ->name('parse-news.get-progress');
});
