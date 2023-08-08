<div class="d-flex flex-column flex-md-row">
    <div class="form-group">
        <label for="link_site">Ссылка на сайт: </label>
        <input type="text"
               id="link_site"
               name="link_site"
               value="https://xn--80aabffge6atsmb6aa.xn--p1ai" {{--   "{{ old('link_site') }}"--}}
               required
               class="form-control{{ $errors->has('link_site') ? ' border-danger' : '' }}">
        @if ($errors->has('link_site'))
            <span class="text-danger" role="alert">
                                <strong>{{ $errors->first('link_site') }}</strong>
                            </span>
        @endif
    </div>

    <div class="form-group ml-md-3">
        <label for="uri_news">Ссылка на страницу новости: </label>
        <input type="text"
               id="uri_news"
               name="uri_news"
               value="/news" {{--   "{{ old('uri_news') }}"--}}
               required
               class="form-control{{ $errors->has('uri_news') ? ' border-danger' : '' }}">
        @if ($errors->has('uri_news'))
            <span class="text-danger" role="alert">
                                <strong>{{ $errors->first('uri_news') }}</strong>
                            </span>
        @endif
    </div>

    <div class="form-group ml-md-3">
        <label for="uri_paginator">Paginator: </label>
        <input type="text"
               id="uri_paginator"
               name="uri_paginator"
               value="/page" {{--   "{{ old('uri_paginator') }}"--}}
               class="form-control{{ $errors->has('uri_paginator') ? ' border-danger' : '' }}">
        @if ($errors->has('uri_paginator'))
            <span class="text-danger" role="alert">
                                <strong>{{ $errors->first('uri_paginator') }}</strong>
                            </span>
        @endif
    </div>

</div>

<div class="d-flex flex-column flex-md-row">
    <div class="form-group">
        <label for="first_page_number">Номер первой страницы: </label>
        <input type="text"
               id="first_page_number"
               name="first_page_number"
               value="1" {{--{{ old('first_page_number') }}"--}}
               placeholder="3"
               class="form-control{{ $errors->has('first_page_number') ? ' border-danger' : '' }}">
        @if ($errors->has('first_page_number'))
            <span class="text-danger" role="alert">
                                <strong>{{ $errors->first('first_page_number') }}</strong>
                            </span>
        @endif
    </div>

    <div class="form-group ml-md-3">
        <label for="last_page_number">Номер последней страницы: </label>
        <input type="text"
               id="last_page_number"
               name="last_page_number"
               value="1" {{--{{ old('last_page_number') }}"--}}
               placeholder="3"
               class="form-control{{ $errors->has('last_page_number') ? ' border-danger' : '' }}">
        @if ($errors->has('last_page_number'))
            <span class="text-danger" role="alert">
                                <strong>{{ $errors->first('last_page_number') }}</strong>
                            </span>
        @endif
    </div>
</div>

<div class="form-group">
    <label for="source_image">Источник главного изображения</label>
    <select name="source_image">
        <option value='page'>Страница Новости</option>
        <option value='list'>Список новостей</option>
    </select>
    @if ($errors->has('source_image'))
        <span class="text-danger" role="alert">
            <strong>{{ $errors->first('source_image') }}</strong>
        </span>
    @endif
</div>


