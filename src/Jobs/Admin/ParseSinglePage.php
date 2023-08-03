<?php

namespace Cher4geo35\ParseNews\Jobs\Admin;


use Cher4geo35\ParseNews\Traits\ParseImage;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ParseSinglePage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use ParseImage;
    protected $slug, $link, $client, $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($single)
    {
        $this->slug = $single->slug;
        $this->link = $single->link;
        $this->data = $single->data;
    }

    /**
     * @return array|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|string|string[]
     */
    public function handle()
    {
        $data = $this->data;

        try {
            $client = new \GuzzleHttp\Client(['base_uri' => $this->link, 'timeout' => 2.0, 'connect_timeout' => 5, ]);
            $response = $client->request('GET', '', ['verify' => false]);
        } catch (Exception $e) {
            return view("parse-news::admin.parse-news.index",['content' => "Проблема с парсингом ссылки на страницу новости!"]);
        }

        $htmlString = (string) $response->getBody();
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($htmlString);
        $xpath = new DOMXPath($doc);

        $eval_gallery_news = $xpath->evaluate($data->path_gallery);

        //meta
        $eval_meta_title_news = $xpath->evaluate($data->path_meta_title);
        $eval_meta_description_news = $xpath->evaluate($data->path_meta_description);
        $eval_meta_keywords_news = $xpath->evaluate($data->path_meta_keywords);

        $link_images_gallery = [];
        if($eval_gallery_news->length != 0){
            foreach($eval_gallery_news as $link_image_gallery){
                $link_images_gallery[] = $this->getAndClearLink($link_image_gallery);
            }
        } else {
            // TODO Обработать ошибку
            $link_images_gallery = 'Не найдено.';
        }

        //Сохраняем описание, дату новости
        $pagedb = (object)[
            "description" => $this->getDescription($doc, $xpath, $data->path_description),
            "date" => $this->getDate($xpath, $data->path_date),
            "slug" => $this->slug,
            "meta_title_news" => $this->getMetaContent($eval_meta_title_news),
            "meta_description_news" => $this->getMetaContent($eval_meta_description_news),
            "meta_keywords_news" => $this->getMetaContent($eval_meta_keywords_news),
        ];
        Cache::put('summaryJobs', Cache::get('summaryJobs') +1);
        ParseSinglePageToDB::dispatch($pagedb)->onQueue('singledb');//Запись title, short, slug в БД

        //Сохраняем картинку новости со страницы
        if($data->source_image == 'page'){
            $image_db = (object)[
                "slug" => $this->slug,
                "link_image" => $this->getLinkImage($xpath, $data->path_image),
            ];
            Cache::put('summaryJobs', Cache::get('summaryJobs') +1);
            ParseImageToDB::dispatch($image_db)->onQueue('image_db');//Сохранение картинки в БД
        }

        //Сохраняем галерею новости
        $gallery_db = (object)[
            "slug" => $this->slug,
            "link_images_gallery" => $link_images_gallery,
        ];
        Cache::put('summaryJobs', Cache::get('summaryJobs') +1);
        ParseGalleryToDB::dispatch($gallery_db)->onQueue('gallery_db');//Сохранение галереи в БД

        Cache::put('completedJobs', Cache::get('completedJobs', 0)+1 );

    }

    private function getDescription($doc, $xpath, $path){
        $description_news = "";
        $nodes = $xpath->query($path);
        foreach($nodes as $full_name) {
            $description_news .= trim($doc->saveHTML($full_name));
        }
        return preg_replace('/<img[^>]+>/', "", $description_news);
    }

    private  function  getLinkImage($xpath, $path){
        $eval_image_news = $xpath->evaluate($path);
        if( $eval_image_news->length > 0){
            $first = true;
            foreach ($eval_image_news as $image_news) {
                $temp_link_image = $this->getAndClearLink($image_news);
                $this->checkImage($temp_link_image);

                if ($first) {
                    $link_image = $temp_link_image;
                    $first = false;
                }
                if($this->getSizeImage($temp_link_image) > $this->getSizeImage($link_image)
                    && $this->getWidthImage($temp_link_image) > $this->getWidthImage($link_image)){
                    $link_image = $temp_link_image;
                }
            }
        } else {
            // TODO Обработать ошибку
            $link_image = "Не найдено.";
        }
        return $link_image;
    }

    private function getDate($xpath, $path){
        $eval_date_news = $xpath->evaluate($path);
        if( $eval_date_news->length > 0){
            foreach ($eval_date_news as $date_news){
                $date = $date_news->textContent.PHP_EOL;
                return $this->stringToTime($date);
            }
        } else {
            return "Не найдено.";
        }
    }
}
