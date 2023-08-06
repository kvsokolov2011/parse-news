<form method="post"
      class="col-12 needs-validation"
      enctype="multipart/form-data"
      action="{{ route('admin.parse-news.create') }}">
    @csrf

    <h2 class="mt-3 mb-3 h4">URL, URI страницы новостей 4geo</h2>

    @include("parse-news::admin.parse-news.includes.inputs-url-uri")

    <div id="btn-parse" class="{{ session()->has('status')?'d-none':'' }}">
        <div id="btn-parse" class="btn-group mt-2" role="group">
            <button type="submit" class="btn btn-success">Импорт новостей</button>
        </div>
        <div class="ml-3 btn-group mt-2" role="group">
            <a href="{{route('admin.parse-news.failed-jobs')}}" class="btn btn-success">Очистить очереди с ошибками</a>
        </div>
    </div>

    <progress-bar url="{{ route('admin.parse-news.get-progress') }}"></progress-bar>

    <h2 class="mt-5 mb-3 h4">Парсинг страницы со списком новостей 4geo</h2>
    @include("parse-news::admin.parse-news.includes.inputs-parse-list")

    <h2 class="mt-5 mb-3 h4">Парсинг страницы новости 4geo</h2>
    @include("parse-news::admin.parse-news.includes.input-parse-page")

    <h2 class="mt-5 mb-3 h4">Парсинг meta 4geo</h2>
    @include("parse-news::admin.parse-news.includes.inputs_meta")
</form>
