<div class="form-group">
    <label for="path_meta_title">Мета title: <span class="text-danger">*</span></label>
    <input type="text"
           id="path_meta_title"
           name="path_meta_title"
           value='//title'
           required
           class="form-control{{ $errors->has('path_meta_title') ? ' border-danger' : '' }}">
    @if ($errors->has('path_meta_title'))
        <span class="text-danger" role="alert">
            <strong>{{ $errors->first('path_meta_title') }}</strong>
        </span>
    @endif
</div>

<div class="form-group">
    <label for="path_meta_description">Мета description: <span class="text-danger">*</span></label>
    <input type="text"
           id="path_meta_description"
           name="path_meta_description"
           value='//meta[{{ chr(64) }}name="description"]/{{ chr(64) }}content'
           required
           class="form-control{{ $errors->has('path_meta_description') ? ' border-danger' : '' }}">
    @if ($errors->has('path_meta_description'))
        <span class="text-danger" role="alert">
            <strong>{{ $errors->first('path_meta_description') }}</strong>
        </span>
    @endif
</div>

<div class="form-group">
    <label for="path_meta_keywords">Мета keywords: <span class="text-danger">*</span></label>
    <input type="text"
           id="path_meta_keywords"
           name="path_meta_keywords"
           value='//meta[{{ chr(64) }}name="keywords"]/{{ chr(64) }}content'
           required
           class="form-control{{ $errors->has('path_meta_keywords') ? ' border-danger' : '' }}">
    @if ($errors->has('path_meta_keywords'))
        <span class="text-danger" role="alert">
            <strong>{{ $errors->first('path_meta_keywords') }}</strong>
        </span>
    @endif
</div>
