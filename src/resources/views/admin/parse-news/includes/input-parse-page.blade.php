<div class="form-group">
    <label for="path_description">Путь к полному описанию новости: <span class="text-danger">*</span></label>
    <input type="text"
           id="path_description"
           name="path_description"
           value='//div[{{ chr(64) }}class="default-style"]//div[{{ chr(64) }}class="lp-element lp-text1"]'
           required
           class="form-control{{ $errors->has('path_description') ? ' border-danger' : '' }}">
    @if ($errors->has('path_description'))
        <span class="text-danger" role="alert">
           <strong>{{ $errors->first('path_description') }}</strong>
        </span>
    @endif
</div>

<div class="form-group">
    <label for="path_date">Путь к дате создания новости: <span class="text-danger">*</span></label>
    <input type="text"
           id="path_date"
           name="path_date"
           value='//div[{{ chr(64) }}class="post visit-news"]//div[{{ chr(64) }}class="post_date"]'
           required
           class="form-control{{ $errors->has('path_date') ? ' border-danger' : '' }}">
    @if ($errors->has('path_date'))
        <span class="text-danger" role="alert">
           <strong>{{ $errors->first('path_date') }}</strong>
        </span>
    @endif
</div>

<div class="form-group">
    <label for="path_image">Путь к картинке новости: <span class="text-danger">*</span></label>
    <input type="text"
           id="path_image"
           name="path_image"
           value='//div[{{ chr(64) }}class="lp-element lp-text1"]//img/{{ chr(64) }}src'
           required
           class="form-control{{ $errors->has('path_image') ? ' border-danger' : '' }}">
    @if ($errors->has('path_image'))
        <span class="text-danger" role="alert">
            <strong>{{ $errors->first('path_image') }}</strong>
        </span>
    @endif
</div>

<div class="form-group">
    <label for="path_gallery">Путь к картинкам галереи: <span class="text-danger">*</span></label>
    <input type="text"
           id="path_gallery"
           name="path_gallery"
           value='//div[{{ chr(64) }}class="lp-element lp-photoslider3"]//div//a[{{ chr(64) }}class="previewphoto4s lp-slideshow-tile-image"]/{{ chr(64) }}href'
           required
           class="form-control{{ $errors->has('path_gallery') ? ' border-danger' : '' }}">
    @if ($errors->has('path_gallery'))
        <span class="text-danger" role="alert">
            <strong>{{ $errors->first('path_gallery') }}</strong>
        </span>
    @endif
</div>
