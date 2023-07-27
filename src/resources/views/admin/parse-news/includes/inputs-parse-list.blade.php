<div class="form-group">
    <label for="path_title">Путь к title новости: <span class="text-danger">*</span></label>
    <input type="text"
           id="path_title"
           name="path_title"
           value='//div[{{ chr(64) }}class="col-md-12 news-item marginbottom20"]//div//h4//strong//a'
           required
           class="form-control{{ $errors->has('path_title') ? ' border-danger' : '' }}">
    @if ($errors->has('path_title'))
        <span class="text-danger" role="alert">
                                <strong>{{ $errors->first('path_title') }}</strong>
                            </span>
    @endif
</div>

<div class="form-group">
    <label for="path_link">Путь к ссылке на страницу новости: <span class="text-danger">*</span></label>
    <input type="text"
           id="path_link"
           name="path_link"
           value='//div[{{ chr(64) }}class="col-md-12 news-item marginbottom20"]//div//h4//strong//a/@href'
           required
           class="form-control{{ $errors->has('path_link') ? ' border-danger' : '' }}">
    @if ($errors->has('path_link'))
        <span class="text-danger" role="alert">
                                <strong>{{ $errors->first('path_link') }}</strong>
                            </span>
    @endif
</div>

<div class="form-group">
    <label for="path_short">Путь к Short новости: <span class="text-danger">*</span></label>
    <input type="text"
           id="path_short"
           name="path_short"
           value='//div[{{ chr(64) }}class="col-md-12 news-item marginbottom20"]//div//div//div[{{ chr(64) }}class="post-decription marginbottom15"]'
           required
           class="form-control{{ $errors->has('path_short') ? ' border-danger' : '' }}">
    @if ($errors->has('path_short'))
        <span class="text-danger" role="alert">
                                <strong>{{ $errors->first('path_short') }}</strong>
                            </span>
    @endif
</div>

<div class="form-group">
    <label for="path_image_list">Путь к картинке новости: <span class="text-danger">*</span></label>
    <input type="text"
           id="path_image_list"
           name="path_image_list"
           value='//div[{{ chr(64) }}class="col-xs-12 col-sm-12 col-md-3"]//a[{{ chr(64) }}class="thumbnail news-img"]//img/{{ chr(64) }}src'
           required
           class="form-control{{ $errors->has('path_image_list') ? ' border-danger' : '' }}">
    @if ($errors->has('path_image_list'))
        <span class="text-danger" role="alert">
            <strong>{{ $errors->first('path_image_list') }}</strong>
        </span>
    @endif
</div>




