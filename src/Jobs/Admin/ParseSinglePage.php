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
     * @return false|void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {
        $data = $this->data;
        try {
            $client = new \GuzzleHttp\Client(['base_uri' => $this->link, 'timeout' => 5.0, 'connect_timeout' => 10, ]);
            $response = $client->request('GET', '', ['verify' => false]);
        } catch (Exception $e) {
            ProgressParseNews::errorParseNewsAdd("Проблема с парсингом страницы новости: <a  target='_blank' href='$this->link'>". $this->link."</a>");
            exit;
        }

        $htmlString = (string) $response->getBody();
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($htmlString);
        $xpath = new DOMXPath($doc);

        $eval_title_news = $xpath->evaluate($data->path_title);
        $title = '';
        foreach ($eval_title_news as $title_news){
            $title = $title_news ? trim($title_news->textContent.PHP_EOL) : "Не найдено.";
            break;
        }
        if($title == "Не найдено.") ProgressParseNews::errorParseNewsAdd("Заголовок <a target='_blank' href='".$this->link."'>". $this->link ."</a> не найден");

        //meta
        $eval_meta_title_news = $xpath->evaluate($data->path_meta_title);
        $eval_meta_description_news = $xpath->evaluate($data->path_meta_description);
        $eval_meta_keywords_news = $xpath->evaluate($data->path_meta_keywords);

        $meta_keywords_news = $this->getMetaContent($eval_meta_keywords_news);
        if($meta_keywords_news == "Не найдено."){
            $eval_meta_keywords_news = $xpath->evaluate("//strong[contains(@class, 'news-tag')]");
            if($eval_meta_keywords_news){
                $meta_keywords_news = '';
                foreach($eval_meta_keywords_news as $item){
                    !$meta_keywords_news ? $meta_keywords_news .= trim($item->textContent . PHP_EOL) : $meta_keywords_news .= ", ".trim($item->textContent . PHP_EOL);
                }
            }
        }
//        print_r($meta_keywords_news);

        //Сохраняем описание, дату новости
        $pagedb = (object)[
            "title" => $title,
            "description" => $this->getDescription($doc, $xpath, $data->path_description, $this->link),
            "date" => $this->getDate($xpath, $data->path_date, $this->link),
            "slug" => $this->slug,
            "meta_title_news" => $this->getMetaContent($eval_meta_title_news),
            "meta_description_news" => $this->getMetaContent($eval_meta_description_news),
            "meta_keywords_news" => $meta_keywords_news,
        ];
        ParseSinglePageToDB::dispatch($pagedb)->onQueue('singledb');//Запись title, short, slug в БД
//print_r($pagedb->meta_keywords_news);
        //Сохраняем картинку новости со страницы
        if($data->source_image == 'page'){
            $link_image = $this->getLinkImage($xpath, $data->path_image, $this->link);
            if($link_image){
                $image_db = (object)[
                    "slug" => $this->slug,
                    "link_image" => $link_image,
                ];
                //Сохранение картинки в БД
                ParseImageToDB::dispatch($image_db)->onQueue('image_db');
            }
        }

        $this->galleryStorage($xpath, $data);
    }

    /**
     * @param $xpath
     * @param $data
     * @return void
     *
     * Поиск и сохранение картинок галереи страницы новостей
     */
    private function galleryStorage($xpath, $data){
        $eval_gallery_news = [];
        $eval_gallery_news[] = $xpath->evaluate($data->path_gallery.'//@src');
        $eval_gallery_news[] = $xpath->evaluate($data->path_gallery.'//@href');
        $link_images_gallery = [];
        foreach ($eval_gallery_news as $item){
            foreach ($item as $gallery_news){
                $lnk = trim($gallery_news->textContent.PHP_EOL);
                $lnk_arr = explode(".", $lnk);
                if(in_array(end($lnk_arr), $this->img_exts)){
                    if($this->getWidthImage($lnk) > $this->data->min_width_image && $this->getSizeImage($lnk) > $this->data->min_size_image){
                        $link_images_gallery[] = $lnk;
                    }
                }
            }
        }
        //Сохраняем галерею новости
        if($link_images_gallery != []){
            $gallery_db = (object)[
                "slug" => $this->slug,
                "link_images_gallery" => $link_images_gallery,
            ];
            //Сохранение галереи в БД
            ParseGalleryToDB::dispatch($gallery_db)->onQueue('gallery_db');
        }
    }

    /**
     * @param $doc
     * @param $xpath
     * @param $path
     * @return array|string|string[]|null
     *
     *Получаем html код описания новостей без классов
     */
    private function getDescription($doc, $xpath, $path, $link){
        $description_news = "";
        $nodes = $xpath->query($path);
        if(count($nodes)) {
            foreach($nodes as $node) {
                $description_news .= trim($doc->saveHTML($node));
            }
            //Удаление картинок и ненужных атрибутов тегов
            $description_news = preg_replace('/<img[^>]+>/', "", $description_news);
            $description_news = preg_replace('/(class|style|id|lang|rel) *= *((" *.*? *")|(\' *.*? *\'))/i',"",$description_news);
            return $description_news;
        }
        ProgressParseNews::errorParseNewsAdd("Описание новости не найдено: <a target='_blank' href='".$link."'>".$link."</a>");
        return '<h3>Описание новости не найдено</h3>';
    }

    /**
     * @param $xpath
     * @param $path
     * @return false|string
     *
     * Поиск ссылки на основную картинку на странице
     */
    private  function  getLinkImage($xpath, $path, $link){
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
            ProgressParseNews::errorParseNewsAdd("Главная картинка на странице новости не найдена: <a target='_blank' href='".$link."'>".$link."</a>");
            return false;
        }
        return $link_image;
    }

    /**
     * @param $xpath
     * @param $path
     * @return string|void
     *
     * Парсим дату
     */
    private function getDate($xpath, $path, $link){
        $eval_date_news = $xpath->evaluate($path);
        if( $eval_date_news->length > 0){
            foreach ($eval_date_news as $date_news){
                $date = $date_news->textContent.PHP_EOL;
                return $this->stringToTime($date, $link);
            }
        } else {
            ProgressParseNews::errorParseNewsAdd("Дата опубликования новости <a target='_blank' href='".$link."'>".$link."</a> не найдена");
            return "Не найдено.";
        }
    }
}
