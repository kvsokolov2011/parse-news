#Description
**Парсинг новостей сайтов 4geo в portedcheese/site-news пакет новостей**

## Установка
php artisan vendor:publish --provider="Cher4geo35\ParseNews\ParseNewsServiceProvider" --tag=public --force
php artisan make:parse-news {--all : Run all}
                      {--menu : Config menu}
                      {--config : Make config}
                      {--controllers : Export controllers}
                      {--jobs : Export jobs}
                      {--vue : Export vue files}
                      {--scss : Export scss files}
                      
