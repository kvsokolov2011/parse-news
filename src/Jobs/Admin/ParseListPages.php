<?php

namespace Cher4geo35\ParseNews\Jobs\Admin;

use Cher4geo35\ParseNews\Models\ProgressParseNews;
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
        try {
            $client = new \GuzzleHttp\Client(['base_uri' => $data->link_site, 'timeout' => 5.0, 'connect_timeout' => 10, ]);
            $response = $client->request('GET', $data->uri_news . $data->uri_paginator, ['verify' => false]);
        } catch (Exception $e) {
            ProgressParseNews::errorParseNewsAdd('Проблема парсинга страницы списка новостей: '.$data->uri_news . $data->uri_paginator);
            exit;
        }

        $htmlString = (string) $response->getBody();
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($htmlString);
        $xpath = new DOMXPath($doc);

        $eval_link_page_news = $xpath->evaluate($data->path_link);
        $eval_short_news = $xpath->evaluate($data->path_short);

        //meta
        $eval_meta_title_news = $xpath->evaluate($data->path_meta_title);
        $eval_meta_description_news = $xpath->evaluate($data->path_meta_description);
        $eval_meta_keywords_news = $xpath->evaluate($data->path_meta_keywords);
        $eval_image_news = $xpath->evaluate($data->path_image_list);

        $first_page_of_list = true;
        if($eval_link_page_news->length != 0){
            foreach ($eval_link_page_news as $key => $link_page_news) {
                $link = trim($eval_link_page_news[$key]->textContent.PHP_EOL);
                $slug = explode('/', $link);

                $slug = end($slug);

                $shortTxt = $eval_short_news[$key] ? trim($eval_short_news[$key]->textContent.PHP_EOL) : "Не найдено.";
                preg_match("/^(.*?[?!.])(?=\s*[A-ZА-ЯЁ]|$)/", $shortTxt, $shortArr);
                $short = "Не найдено.";
                if (isset($shortArr[0])) $short = $shortArr[0];
                if($short == "Не найдено.") ProgressParseNews::errorParseNewsAdd("Short <a target='_blank' href='".$link."'>". $link ."</a> не найден");

                //Сохраняем картинку новости из списка новостей
                    $link_image =  $eval_image_news[$key] ? $this->getAndClearLink($eval_image_news[$key]) : "Не найдено.";
                    if($link_image != "Не найдено."){
                        $image_db = (object)[
                            "slug" => $slug,
                            "link_image" => $link_image,
                        ];

                        ParseImageToDB::dispatch($image_db)->onQueue('image_db');
                    }

                $listdb = (object)[
                                    "slug" => $slug,
                                    "short" => $this->remove_emoji($short),
                                    "meta_title_news" => $this->getMetaContent($eval_meta_title_news),
                                    "meta_description_news" => $this->getMetaContent($eval_meta_description_news),
                                    "meta_keywords_news" => $this->getMetaContent($eval_meta_keywords_news),
                                    "page_list" => $data->link_site.$data->uri_news.$data->uri_paginator,
                                    "first_page_of_list" => $first_page_of_list,
                                ];
                ParseListPagesToDB::dispatch($listdb)->onQueue('listdb');//Запись title, short, slug в БД
                $first_page_of_list = false;

                $single = (object)[
                    "slug" => $slug,
                    "link" => $link,
                    "data" => $data,
                ];

                ParseSinglePage::dispatch($single)->onQueue('single');//Парсинг страницы новости
            }
        } else {
            ProgressParseNews::errorParseNewsAdd('Не найдены ссылки на страницы новостей: '.$data->uri_paginator);
        }
    }
}
