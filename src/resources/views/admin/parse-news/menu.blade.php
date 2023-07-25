{{--@can("viewAny", \App\ParseNews::class)--}}
    <li class="nav-item">
        <a href="{{ route('admin.parse-news.index') }}"
           class="nav-link{{ strstr($currentRoute, 'admin.parse-news') !== FALSE ? ' active' : '' }}">
            @isset($ico)
                <i class="{{ $ico }}"></i>
            @endisset
            <span>Парсинг новостей</span>
        </a>
    </li>
{{--@endcan--}}

