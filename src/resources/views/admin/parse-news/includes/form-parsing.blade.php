<form method="post"
      class="col-12 needs-validation"
      enctype="multipart/form-data"
      action="{{ route('admin.parse-news.create') }}">
    @csrf

    <h2 class="mt-3 mb-3 h4">URL, URI страницы новостей 4geo</h2>
    @include("parse-news::admin.parse-news.includes.inputs-url-uri")

    <div class="btn-group mt-2 {{ session()->has('status')?'d-none':'' }}"
         role="group">
        <button type="submit" class="btn btn-success">Выполнить парсинг</button>
    </div>

    <progress-bar url="{{ route('admin.parse-news.get-progress') }}"></progress-bar>

    @include("parse-news::admin.parse-news.includes.parsing-result")

    <h2 class="mt-5 mb-3 h4">Парсинг страницы со списком новостей 4geo</h2>
    @include("parse-news::admin.parse-news.includes.inputs-parse-list")

    <h2 class="mt-5 mb-3 h4">Парсинг страницы новости 4geo</h2>
    @include("parse-news::admin.parse-news.includes.input-parse-page")

    <h2 class="mt-5 mb-3 h4">Парсинг meta 4geo</h2>
    @include("parse-news::admin.parse-news.includes.inputs_meta")
</form>
