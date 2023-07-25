<div class="my-5">
    @if(!empty($content))
        @if(is_array($content))
            @foreach($content as $key => $news)
                <h2 class="mt-5 mb-4">{{ $key }}</h2>
                @if(is_array($news))
                    @foreach($news as $item)
                        <table class="mb-5">
                            <tr>
                                <td class="p-2 border border-dark">title</td>
                                <td class="p-2 border border-dark">{{ $item['title'] }}</td>
                            </tr>
                            <tr>
                                <td class="p-2 border border-dark">main_image</td>
                                <td class="p-2 border border-dark">
                                    @if($item['image'] != "Не найдено.")
                                        <img src="{{ $item['image'] }}" style="height: 5rem;">
                                    @else
                                        Не найдено.
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="p-2 border border-dark">link</td>
                                <td class="p-2 border border-dark">
                                    @if($item['link'] != "Не найдено.")
                                        <a href="{{ $item['link'] }}" class="mb-3">{{ $item['link'] }}</a>
                                    @else
                                        Не найдено.
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="p-2 border border-dark">short</td>
                                <td class="p-2 border border-dark">{{ $item['short'] }}</td>
                            </tr>
                            <tr>
                                <td class="p-2 border border-dark">description</td>
                                <td class="p-2 border border-dark">{!! $item['description'] !!}</td>
                            </tr>
                        </table>
                    @endforeach
                @else
                    {{ $news }}
                @endif
            @endforeach
        @else
            {{ $content }}
        @endif
    @endif
</div>
