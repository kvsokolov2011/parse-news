<?php

namespace Cher4geo35\ParseNews\Traits;

use App\Image;
use App\Meta;
use App\News;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;



trait ParseImage
{

    /**
     * @return void
     *
     * Очистка кэша
     */
    public function clearCache(){
        Cache::put('summaryJobs', 0);
        Cache::put('completedJobs', 0);
        Cache::put('resultParseNews', '');
        Cache::put('errorParseNews', '');
    }

    /**
     * @param $image_news
     * @return string
     *
     * Получение ссылки и очистка ее от мусора
     */
    public function getAndClearLink($image_news){
        $temp_link_image = trim($image_news->textContent . PHP_EOL);
        $url = pathinfo($temp_link_image);
        return $url['dirname'] . '/' . $url['basename'];
    }

    /**
     * @param $link
     * @return mixed
     *
     * Получаем ширину картинки по ссылке
     */
    public function getWidthImage($link){
        return getimagesize ($link)[0];
    }

    /**
     * @param $link
     * @return mixed|string
     *
     * Получаем размер картинки по ссылке
     */
    public function getSizeImage($link){
        return array_change_key_case(get_headers($link,1))['content-length'];
    }

    /**
     * @param $link
     * @return void
     *
     * Проверяем, что файл является изображением
     */
    public function checkImage($link){
        $imgExts = array("gif", "jpg", "jpeg", "png", "tiff", "tif");
        $ext = pathinfo($link, PATHINFO_EXTENSION);
        if (!in_array($ext, $imgExts)) {
            $this->addError("Полученный файл не является изображением");
        }
    }

    /**
     * @param $date
     * @return string
     *
     * Преобразование полученной строки даты в формат БД
     */
    public function stringToTime($date){
        $date = preg_replace('/,/', "", $date);
        $ru_month = array( 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря' );
        $number_month = array( '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12' );
        $date = str_replace($ru_month, $number_month, $date);
        preg_match_all('/\d+/', $date, $arrDate);
        $date = $arrDate[0][2]."-".$arrDate[0][1]."-".$arrDate[0][0]." 08:00:00";
        return $date;
    }

    /**
     * @param $image_uri
     * @return string
     *
     * Создание директории
     */
    public function createDirectory($image_uri){
        $directory = public_path('storage/'.$image_uri);
        if (!file_exists($directory)){
            if (!mkdir($directory, 0755, true)) {
                $this->addError('Не удалось создать директорию: '.$image_uri);
            }
        }
        return $directory;
    }

    /**
     * @param $link
     * @param $directory
     * @return string
     *
     *Получаем файл по ссылке и сохраняем его в указанную директорию
     */
    public function putFile($link, $directory){
        $contents = file_get_contents($link);
        if(!$contents){
            $this->addError('Файл по ссылке '.$link.' не удалось получить');
            return false;
        }
        $ext = pathinfo($link, PATHINFO_EXTENSION);
        $image_name = pathinfo($link, PATHINFO_FILENAME).'.'.$ext;
        if( file_put_contents($directory.'/'.$image_name, $contents) )  return $image_name;
        $this->addError('Файл '.$image_name.' не удалось сохранить');
        return false;
    }

    /**
     * @param $link
     * @param $dir_uri
     * @return false
     *
     * Создаем(если не создана) директорию в указанном месте помещаем в нее файл по ссылке
     *  Получаем адрес хранения и имя для записи в БД
     */
    public function uploadImages($link, $dir_uri){
        if($link != "Не найдено."){
            $directory = $this->createDirectory($dir_uri);
            $image_name = $this->putFile($link, $directory);
            return Image::create([
                'path' => $dir_uri.'/'.$image_name,
                'name' => $image_name,
            ]);
        } else {
            $this->addError('В результате парсинга картинка не найдена');
            return false;
        }
    }

    /**
     * @return void
     *
     * Очистка всех новостей, картинок, галерей, мета связанных с новостями
     */
    public function clearDBNewsAndFiles(){
        $news = News::all();
        foreach($news as $item){
            $item->image()->delete();
            $item->images()->delete();
            $item->delete();
        }
        $this->clearDir(public_path('storage/gallery/news'));
        $this->clearDir(public_path('storage/news/main'));
        $meta = Meta::query()
            ->where('page', 'news')
            ->where('metable_type', 'App/news')->get();
        foreach ($meta as $item){
            $meta->delete();
        }
    }

    /**
     * @return void
     *
     * Очистка очередей
     */
    public function clearDBJobs(){
        $queue = ['list', 'listdb', 'single', 'singledb', 'image_db', 'gallery_db'];
        foreach ($queue as $item){
            DB::table('jobs')->where('queue', $item)->delete();
        }
    }

    /**
     * @return void
     *
     * Очистка проваленных очередей
     */
    public function clearDBFailedJobs(){
        $queue = ['list', 'listdb', 'single', 'singledb', 'image_db', 'gallery_db'];
        foreach ($queue as $item){
            DB::table('failed_jobs')->where('queue', $item)->delete();
        }
    }

    /**
     * @param $dir
     * @return void
     *
     * Очистка директории от файлов
     */
    public function clearDir($dir){
        if ($files = glob($dir . '/*')){
            foreach($files as $file):
                if(!is_dir($file)) unlink($file);
            endforeach;
        }
    }

    /**
     * @param $eval
     * @return string|void
     *
     * Получение строки мета, очистка от лишних пробелов
     */
    public function getMetaContent($eval){
        if( $eval->length !=0){
            foreach($eval as $item){
                return trim($item->textContent . PHP_EOL);
            }
        }
    }

    /**
     * @param $error
     * @return void
     *
     * Фиксация ошибок
     */
    public function addError($error){
        if(Cache::get('errorParseNews') != '') {
            Cache::put('errorParseNews', Cache::get('errorParseNews').'<br>'.$error);
        } else {
            Cache::put('errorParseNews', $error);
        }

    }

}
