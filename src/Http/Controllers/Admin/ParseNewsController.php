<?php

namespace Cher4geo35\ParseNews\Http\Controllers\Admin;

use Cher4geo35\ParseNews\Jobs\Admin\ParseListPages;
use Cher4geo35\ParseNews\Models\ProgressParseNews;
use Cher4geo35\ParseNews\Traits\ParseImage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ParseNewsController extends BaseController
{
    use ParseImage;

    const QUEUE = ['list', 'listdb', 'single', 'singledb', 'image_db', 'gallery_db'];

    /**
     * @var
     */
    private $data;

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     *
     * Страница импорта новостей
     */
    public function index()
    {
        if( $this->queueIsNotEmpty() ){
            session()->flash('status', 'Идет процесс импорта новостей. Попробуйте позже.');
            return view("parse-news::admin.parse-news.index");
        }
        ProgressParseNews::clearProgress();
        return view("parse-news::admin.parse-news.index");
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     *
     * Очистка проваленных очередей
     */
    public function failedJobs()
    {
        $this->clearDBFailedJobs();
        return redirect()
            ->route("admin.parse-news.index")
            ->with('success', 'Очереди с ошибками очищены');
    }

    /**
     * @return array
     *
     * Контроллер для компонента ProgressBar.vue
     *
     */
    public function getProgress(){
        $lastJobs = $this->queueIsNotEmpty();
        if(ProgressParseNews::summaryJobs() != 0){
            $progress = 100 * (ProgressParseNews::summaryJobs() - $lastJobs)/ProgressParseNews::summaryJobs();
            if( $this->jobsFailed() )  {
                $this->getErrorsFailedJobs();
                ProgressParseNews::errorParseNewsAdd('Ошибка обработчика очередей. Импорт прекращен.');
                $this->clearDBJobs();
            }
            if($progress >= 100 && !$this->jobsFailed() ){
                ProgressParseNews::resultParseNewsAdd('Импорт выполнен.');
            } else {
                ProgressParseNews::resultParseNewsAdd('При импорте возникли проблемы');
            }
            return ['width' => $progress,
                    'result' => ProgressParseNews::resultParseNews(),
                    'error' => ProgressParseNews::errorParseNews(),
                    'lastJobs' => $lastJobs ];
        } else {
            return ['width' => 0,
                    'result' => ProgressParseNews::resultParseNews(),
                    'error' => ProgressParseNews::errorParseNews(),
                'lastJobs' => $lastJobs ];
        }
    }

    /**
     * @param Request $request
     * @return array|true[]
     *
     * Парсим страницы новостей
     */
    public function create(Request $request)
    {
        $check = $this->validateInput($request);
        if($check){
            return [
                'success' => false,
                'result' => $check,
            ];
        }
        $this->clearDBNewsAndFiles();
        ProgressParseNews::clearProgress();
        //Перебор страниц
        if($this->data->first_page_number <= $this->data->last_page_number){
            for($i=$this->data->first_page_number; $i <= $this->data->last_page_number; $i++){
                $dataPage = clone $this->data;
                $dataPage->uri_paginator = $dataPage->uri_paginator.$i;
                ProgressParseNews::summaryJobsIncrement();
                ParseListPages::dispatch($dataPage)->onQueue('list');
            }
        } else {
            ProgressParseNews::errorParseNewsAdd('Неверно заданы номера страниц');
            return [
                'success' => false,
                'result' => 'Неверно заданы номера страниц',
            ];
        }

        return [
            'success' => true
        ];
    }

    /**
     * @return bool
     *
     * количество необработанных очередей - есть необработанные очереди
     */
    private function queueIsNotEmpty(){
        $count = 0;
        foreach (self::QUEUE as $item) {
            $jobs = DB::table('jobs')
                ->where('queue', $item)->get();
            if(count($jobs)) $count = $count + count($jobs);
        }
        if($count) return $count;
        return false;
    }

    /**
     * @return bool
     *
     * true - в базе есть проваленные очереди
     */
    private function jobsFailed(){
        foreach (self::QUEUE as $item) {
            $failed_jobs = DB::table('failed_jobs')
                ->where('queue', $item)->get();
            if(count($failed_jobs)) return true;
        }
        return false;
    }

    /**
     * @param Request $request
     * @return false|string
     *
     * Проверка адресов, упаковка исходных данных в массив
     */
    private function validateInput(Request $request){
        $link_site = trim($request->link_site);
        $uri_news = trim($request->uri_news);
        $uri_paginator = trim($request->uri_paginator);
        $first_page_number = trim($request->first_page_number);
        $last_page_number = trim($request->last_page_number);

        //Проверка валидности адресов
        if(!$this->isValidURL($link_site)) return "Не валидный адрес сайта!";
        if(!$this->isValidURL($link_site.$uri_news)) return  "Не валидная ссылка на страницу новости!";
        if(!$this->isValidURL($link_site.$uri_news.$uri_paginator."1")) return  "Не валидный пагинатор!";

        $this->data = (object)[
            "link_site" => $link_site,
            "uri_news" => $uri_news,
            "uri_paginator" => $uri_paginator,
            "first_page_number" => $first_page_number,
            "last_page_number" => $last_page_number,
            "source_image" => $request->source_image,
            "path_title" => trim($request->path_title),
            "path_link" => trim($request->path_link),
            "path_short" => trim($request->path_short),
            "path_image" => trim($request->path_image),
            "path_image_list" => trim($request->path_image_list),
            "path_description" => trim($request->path_description),
            "path_date" => trim($request->path_date),
            "path_gallery" => trim($request->path_gallery),
            "path_meta_title" => trim($request->path_meta_title),
            "path_meta_description" => trim($request->path_meta_description),
            "path_meta_keywords" => trim($request->path_meta_keywords),
        ];
        return false;
    }

    /**
     * @param $url
     * @return bool
     *
     * true - URL валидный
     */
    private function isValidURL($url) {
        $file_headers = @get_headers($url);
        if($file_headers){
            if (strpos($file_headers[0], "200 OK") > 0) return true;
        }
        return false;
    }

    private function getErrorsFailedJobs(){
        foreach (self::QUEUE as $item) {
            $failed_jobs = DB::table('failed_jobs')
                ->where('queue', $item)->get();
            foreach($failed_jobs as $job){
                if(preg_match('/Stack trace:/', $job->exception) ){
                    $error = explode('Stack trace:', $job->exception)[0];
                }
                ProgressParseNews::errorParseNewsAdd('Ошибка обработки очереди: <i>'.$error.'</i>');
            }
        }
        $this->clearDBFailedJobs();
        $this->clearDBJobs();
        return true;
    }
}
