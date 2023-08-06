# Импорт страниц новостей

**Парсинг новостей сайтов 4geo в portedcheese/site-news пакет новостей**

## Установка
    php artisan migrate
    php artisan vendor:publish --provider="Cher4geo35\ParseNews\ParseNewsServiceProvider" --tag=public --force
    php artisan make:parse-news {--all : Run all}
                          {--menu : Config menu}
                          {--models : Export models}
                          {--config : Make config}
                          {--controllers : Export controllers}
                          {--jobs : Export jobs}
                          {--vue : Export vue files}
                          {--scss : Export scss files}

## Queues
Добавить в Supervisor:
**Последовательное выполнение очередей** 
        php artisan queue:work --queue=list,single,listdb,singledb,image_db,gallery_db --timeout=300
**паралельное выполнение очередей**
       php artisan queue:work --queue=list  &    php artisan queue:work --queue=single,listdb,singledb,image_db &  php artisan queue:work --queue=gallery_db --timeout=300
**Отладка:**
  php artisan queue:listen --queue=list  &    php artisan queue:listen --queue=single,listdb,singledb,image_db &  php artisan queue:listen --queue=gallery_db --timeout=300

### Заметки
- возможно потребуется увеличить время php artisan queue:work --timeout=60 для очереди --queue=gallery_db (не успевают загрузиться фото, срабатывает timeout очереди)

### Components

progress-bar:

    <progress-bar url="{{ route('admin.parse-news.get-progress') }}">
    </progress-bar>

### Versions
