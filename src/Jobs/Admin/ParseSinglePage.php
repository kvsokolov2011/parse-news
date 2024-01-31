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

        //Сохраняем картинку новости со страницы
            $link_image = $this->getLinkImage($xpath, $data->path_image, $doc);
            if($link_image != ''){
                $image_db = (object)[
                    "slug" => $this->slug,
                    "link_image" => $link_image,
                ];
                //Сохранение картинки в БД
                ParseImageToDB::dispatch($image_db)->onQueue('image_db');
            }

        $this->galleryStorage($xpath, $data, $doc);
    }

    /**
     * @param $xpath
     * @param $data
     * @param $doc
     * @return false|void
     *
     * Поиск и сохранение картинок галереи страницы новостей
     */
    private function galleryStorage($xpath, $data, $doc){
        $html_with_links = $this->getHtml($xpath, $data->path_gallery, $doc);
        if(!$html_with_links) return false;

        //Извлекаем href
        $all_find_links = [];
        preg_match_all('/<a.*?href=["\'](.*?)["\'].*?>/i', $html_with_links, $matches);
        $all_find_links = array_merge($all_find_links, $matches[1]);
        preg_match_all('/src="(.*?)"/i', $html_with_links, $matches);
        $all_find_links = array_merge($all_find_links, $matches[1]);

        $link_images_gallery = [];
        if($all_find_links != []) {
            foreach ($all_find_links as $item) {
                $lnk = trim($item);
                $lnk_arr = explode(".", $lnk);
                $lnk_ext = explode('?', end($lnk_arr))[0];
                if (in_array($lnk_ext, $this->img_exts)) {
                    if ($this->getWidthImage($lnk) > $this->data->min_width_image && $this->getSizeImage($lnk) > $this->data->min_size_image) {
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
        if($this->routeProcessing($xpath, $path, $doc)) return $this->routeProcessing($xpath, $path, $doc);
        ProgressParseNews::errorParseNewsAdd("Описание новости не найдено: <a target='_blank' href='".$link."'>".$link."</a>");
        return '<h3>Описание новости не найдено</h3>';
    }

    /**
     * @param $nodes
     * @param $doc
     * @return array|string|string[]|null
     *
     * Обработка маршрута поиска
     */
    private function routeProcessing($xpath, $path, $doc){
        $nodes = $xpath->query($path);
        $description_var = '';
        if($nodes && count($nodes)) {
            foreach($nodes as $node) {
                $description_var .= $this->remove_emoji(trim($doc->saveHTML($node)));
            }
            //Очистка описания от ненужных тегов
            $description_var = preg_replace('/<a.*href="\/redirect.*?">.*?<\/a>/', "", $description_var);
            $description_var = preg_replace('/<iframe.*<\/iframe>/', "", $description_var);
            $description_var = preg_replace('/<form.*<\/form>/', "", $description_var);
            $description_var = preg_replace('/\s?<span[^>]*?style="display:none;">.*?<\/span>\s?/si', "", $description_var);
            $description_var = preg_replace('/<img[^>]+>/', "", $description_var);
            $description_var = trim(preg_replace('/(class|style|id|lang|rel) *= *((" *.*? *")|(\' *.*? *\'))/i',"",$description_var));
            $tags = ['div', 'td', 'table', 'tbody', 'tr'];
            foreach($tags as $tag){
                $description_var = preg_replace('/<\/?'.trim($tag).'( .*?>|>)/', "", $description_var);
            }
            $en_tags = ['p', 'span', 'strong', 'ul', 'ol', 'h2', 'h3', 'h4' , 'h5'];
            foreach($en_tags as $tag){
                $description_var = preg_replace('/<'.trim($tag).'.*?>/', "<".trim($tag).">", $description_var);
            }

            try{
                $description_var = trim(preg_replace('/(http)s?:\/\/.*?\/contacts/i', route('site.contact.page') ,$description_var));
            } catch ( Exception $e) {
                $description_var = trim(preg_replace('/(http)s?:\/\/.*?\/contacts/i', route('home') ,$description_var));
            }

            $description_var = preg_replace('/<a.*href="http.*:\/\/4geo.*?">(.*)?<\/a>/', "$1", $description_var);
            $str = str_replace('&nbsp;', ' ', htmlentities($description_var));
            $description_var = html_entity_decode($str);
            return trim($description_var);
        }
        return false;
    }

    private function getHtml($xpath, $path, $doc){
        $nodes = $xpath->query($path);
        $result = '';
        if($nodes && count($nodes)) {
            foreach ($nodes as $node) {
                $result .= $this->remove_emoji(trim($doc->saveHTML($node)));
            }
            //Очистка описания от ненужных тегов
            $result = preg_replace('/<table.*class="lp-grid-table".*<\/table>/', "", $result);
            $result = preg_replace('/&amp;/', "&", $result);
            $result = preg_replace('/<img.*width=["\'][0-6][0-9]["\'].*height=["\'][0-6][0-9]["\'].*?>/', "", $result);
            $result = preg_replace('/<img.*height=["\'][0-6][0-9]["\'].*width=["\'][0-6][0-9]["\'].*?>/', "", $result);
            $result = preg_replace('/<img.*height=["\'][0-6][0-9]["\'].*?>/', "", $result);
            $result = preg_replace('/<img.*width=["\'][0-6][0-9]["\'].*?>/', "", $result);
            return $result;
        }
        return false;
    }

    /**
     * @param $xpath
     * @param $path
     * @return false|string
     *
     * Поиск ссылки на основную картинку на странице
     */
    private  function  getLinkImage($xpath, $path, $doc){
        $html_with_src = $this->getHtml($xpath, $path, $doc);
        if(!$html_with_src) return false;
        //Извлекаем href
        $all_find_links = [];
        preg_match_all('/<img.*?src=["\'](.*?)["\'].*?>/i', $html_with_src, $matches);
        $all_find_links = array_merge($all_find_links, $matches[1]);

        $first = true;
        $link_image = '';
        foreach ($all_find_links as $link){
            $temp_link = $this->clearLink($link);
            if($this->checkImage($temp_link) != 'false') continue;
            if ($first) {
                $link_image = $temp_link;
                $first = false;
            }
            if($this->getSizeImage($temp_link) > $this->getSizeImage($link_image)
                && $this->getWidthImage($temp_link) > $this->getWidthImage($link_image)){
                $link_image = $temp_link;
            }
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
