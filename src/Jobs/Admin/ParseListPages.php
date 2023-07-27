<?php

namespace Cher4geo35\ParseNews\Jobs\Admin;

use App\Meta;
use Cher4geo35\ParseNews\Traits\ParseImage;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ParseListPages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use ParseImage;

    protected $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return array|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {
        $data = $this->data;
        //Парсим одну страницу всего списка новостей и помещаем в БД
        try {
            $client = new \GuzzleHttp\Client(['base_uri' => $data->link_site, 'timeout' => 2.0, 'connect_timeout' => 5, ]);
            $response = $client->request('GET', $data->uri_news . $data->uri_paginator, ['verify' => false]);
        } catch (Exception $e) {
            return view("parse-news::admin.parse-news.index",['content' => "Проблема с парсингом на страницу списка новостей!"]);
        }

        $htmlString = (string) $response->getBody();
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($htmlString);
        $xpath = new DOMXPath($doc);

        $eval_title_news = $xpath->evaluate($data->path_title);
        $eval_link_page_news = $xpath->evaluate($data->path_link);
        $eval_short_news = $xpath->evaluate($data->path_short);

        //meta
        $eval_meta_title_news = $xpath->evaluate($data->path_meta_title);
        $eval_meta_description_news = $xpath->evaluate($data->path_meta_description);
        $eval_meta_keywords_news = $xpath->evaluate($data->path_meta_keywords);

        if($data->source_image == 'list'){
            $eval_image_news = $xpath->evaluate($data->path_image_list);
        }


        if($eval_link_page_news->length != 0){
            foreach ($eval_link_page_news as $key => $link_page_news) {
                $link = trim($eval_link_page_news[$key]->textContent.PHP_EOL);
                $slug = explode('/', $link);

                $slug = end($slug);
                $title = $eval_title_news[$key] ? trim($eval_title_news[$key]->textContent.PHP_EOL) : "Не найдено.";
                $short = $eval_short_news[$key] ? trim($eval_short_news[$key]->textContent.PHP_EOL) : "Не найдено.";

                //Сохраняем картинку новости из списка новостей
                if($data->source_image == 'list'){
                    $image_db = (object)[
                        "slug" => $slug,
                        "link_image" => $eval_image_news[$key] ? $this->getAndClearLink($eval_image_news[$key]) : "Не найдено.",
                    ];
                    ParseImageToDB::dispatch($image_db)->onQueue('image_db');//Сохранение картинки в БД
                }

                $listdb = (object)[
                                    "slug" => $slug,
                                    "title" => $title,
                                    "short" => $short,
                                    "meta_title_news" => $this->getMetaContent($eval_meta_title_news),
                                    "meta_description_news" => $this->getMetaContent($eval_meta_description_news),
                                    "meta_keywords_news" => $this->getMetaContent($eval_meta_keywords_news),
                                ];
                ParseListPagesToDB::dispatch($listdb)->onQueue('listdb');//Запись title, short, slug в БД

                $single = (object)[
                    "slug" => $slug,
                    "link" => $link,
                    "data" => $data,
                ];
                ParseSinglePage::dispatch($single)->onQueue('single');//Парсинг страницы новости
            }

        } else {
            $news = "Ссылки на страницы новостей не найдены, slug не определен!";
        }
    }
}
