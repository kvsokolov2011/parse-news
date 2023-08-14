# Импорт страниц новостей

**Парсинг новостей сайтов 4geo в portedcheese/site-news пакет новостей**

## Установка
    php artisan migrate
    php artisan vendor:publish --provider="Cher4geo35\ParseNews\ParseNewsServiceProvider" --tag=public --force
    php artisan make:parse-news {--all : Run all}
                          {--menu : Config menu}
                          {--models : Export models}
                          {--policies : Export and create rules}
                          {--config : Make config}
                          {--controllers : Export controllers}
                          {--jobs : Export jobs}
                          {--vue : Export vue files}

## Queues
Добавить в Supervisor:
 - **Последовательное выполнение очередей**
   php artisan queue:work --queue=list,listdb,single,singledb,image_db,gallery_db --timeout=85
  - **Отладка:**
    php artisan queue:listen --queue=list,listdb,single,singledb,image_db,gallery_db --timeout=85

### Components

progress-bar:

    <progress-bar url="{{ route('admin.parse-news.get-progress') }}">
    </progress-bar>

### Versions
