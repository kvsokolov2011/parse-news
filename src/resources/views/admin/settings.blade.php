<div class="form-group">
    <label for="path">Путь</label>
    <input type="text"
           id="path"
           name="data-path"
           value="{{ old("path", base_config()->get($name, "path", "parse-news")) }}"
           class="form-control @error("data-path") is-invalid @enderror">
    @error("data-path")
        <div class="invalid-feedback" role="alert">
            {{ $message }}
        </div>
    @enderror
</div>

<div class="form-group">
    <label for="data-pager">Пагинация</label>
    <input type="number"
           min="5"
           max="50"
           step="1"
           id="data-pager"
           name="data-pager"
           value="{{ old("data-pager", base_config()->get($name, "pager", 20)) }}"
           class="form-control @error("data-pager") is-invalid @enderror">
    @error("data-pager")
        <div class="invalid-feedback" role="alert">
            {{ $message }}
        </div>
    @enderror
</div>
