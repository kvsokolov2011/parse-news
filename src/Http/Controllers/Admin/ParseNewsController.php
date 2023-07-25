<?php

namespace Cher4geo35\ParseNews\Http\Controllers\Admin;


use App\News;
use Cher4geo35\ParseNews\Jobs\Admin\ParseListPages;
use Cher4geo35\ParseNews\Traits\ParseImage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;

class ParseNewsController extends BaseController
{
    use ParseImage;

    /**
     * @var
     */
    private $data;


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view("parse-news::admin.parse-news.index");
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create(Request $request)
    {
        $check = $this->validateInput($request);
        if($check) return view("parse-news::admin.parse-news.index",['content' => $check]);
        $this->clearDBNewsAndFiles();

        //Перебор страниц
        for($i=1; $i <= $this->data->last_page_number; $i++){
            $dataPage = clone $this->data;
            $dataPage->uri_paginator = $dataPage->uri_paginator.$i;
            ParseListPages::dispatch($dataPage)->onQueue('list');//Парсим одну страницу всего списка новостей и помещаем в БД
        }
        return view("parse-news::admin.parse-news.index",['content' => "Запрос на парсинг выполнен"]);
    }

    /**
     * @param $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|int
     */
    private function validateInput($request){
        $validator = Validator::make($request->all(), [
            "link_site" => "required|url|min:2",
            "uri_news" => "required|min:2",
            "uri_paginator" => "min:2",
            "last_page_number" => "integer|numeric|min:1",
        ]);
        if ($validator->fails()) {
            return redirect(route('admin.parse-news.index'))
                ->withErrors($validator->errors())
                ->withInput();
        }
        $link_site = trim($request->link_site);
        $uri_news = trim($request->uri_news);
        $uri_paginator = trim($request->uri_paginator);
        $last_page_number = trim($request->last_page_number);
        $source_image = $request->source_image;

        $path_title = trim($request->path_title);
        $path_link = trim($request->path_link);
        $path_short = trim($request->path_short);
        $path_image = trim($request->path_image);
        $path_image_list = trim($request->path_image_list);
        $path_description = trim($request->path_description);
        $path_date = trim($request->path_date);
        $path_gallery = trim($request->path_gallery);


        //Проверка валидности адресов
        if(!$this->isValidURL($link_site)) return "Не валидный адрес сайта!";
        if(!$this->isValidURL($link_site.$uri_news)) return  "Не валидная ссылка на страницу новости!";
        if(!$this->isValidURL($link_site.$uri_news.$uri_paginator."1")) return  "Не валидный пагинатор!";
        for($i=1; $i <= $last_page_number; $i++){
            if(!$this->isValidURL($link_site.$uri_news.$uri_paginator.$i)) return  "Не валидный номер последней страницы!";
        }

        $this->data = (object)[
            "link_site" => $request->link_site,
            "uri_news" => $request->uri_news,
            "uri_paginator" => $request->uri_paginator,
            "last_page_number" => $last_page_number,
            "source_image" => $source_image,
            "path_title" => $path_title,
            "path_link" => $path_link,
            "path_short" => $path_short,
            "path_image" => $path_image,
            "path_image_list" => $path_image_list,
            "path_description" => $path_description,
            "path_date" => $path_date,
            "path_gallery" => $path_gallery,
        ];
        return false;
    }

    private function isValidURL($url) {
        $file_headers = @get_headers($url);
        if($file_headers){
            if (strpos($file_headers[0], "200 OK") > 0) return true;
        }
        return false;
    }
}
