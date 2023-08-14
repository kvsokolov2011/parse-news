<?php

namespace Cher4geo35\ParseNews\Traits;

use App\Image;
use App\Meta;
use App\News;
use Cher4geo35\ParseNews\Models\ProgressParseNews;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;



trait ParseImage
{
    //Возможные расширения картинок
    public $img_exts = array("gif", "jpg", "jpeg", "png", "tiff", "tif");
    private $sumJobs = 0;
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
        if(isset(array_change_key_case(get_headers($link,1))['content-length'])) return array_change_key_case(get_headers($link,1))['content-length'];
        return 0;
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
        $ext = explode('?', $ext)[0];
        if (!in_array($ext, $imgExts)) {
            ProgressParseNews::errorParseNewsAdd("Полученный файл не является изображением: ".$link);
        }
    }

    /**
     * @param $date
     * @return string
     *
     * Преобразование полученной строки даты в формат БД
     */
    public function stringToTime($date, $link){
        if( preg_match('/(\d{1,2})(.+)(января|февраля|марта|апреля|мая|июня|июля|августа|сентября|ноября|декабря|октября)(,)(.+)(\d{4})/i',trim($date), $matches) ){
            $ru_month = array( 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря' );
            $number_month = array( '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12' );
            $month = str_replace($ru_month, $number_month, $matches[3]);
            $date = $matches[6]."-".$month."-".$matches[1]." 08:00:00";
            return $date;
        } else {
            ProgressParseNews::errorParseNewsAdd("Дата <a target='_blank' href='". $link ."' >".$link."</a> не соответствует шаблону");
            return 'Не найдено.';
        }
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
                ProgressParseNews::errorParseNewsAdd('Не удалось создать директорию: '.$image_uri);
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
        try{
            $contents = file_get_contents($link);
        } catch (Exception $e){
            ProgressParseNews::errorParseNewsAdd('Файл по ссылке '.$link.' не удалось получить');
            return false;
        }
        $ext = pathinfo($link, PATHINFO_EXTENSION);
        $image_name = pathinfo($link, PATHINFO_FILENAME).'.'.$ext;
        if( file_put_contents($directory.'/'.explode('?', $image_name)[0], $contents) )  return $image_name;
        ProgressParseNews::errorParseNewsAdd('Файл '.$image_name.' не удалось сохранить');
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
            if( (Image::where('path', 'news/main/'.explode('?', $image_name)[0])->first()) != null ) {
                //TODO желательно удалить сохраненный файл, но пока не мешает
                return false;
            }
            return Image::create([
                'path' => $dir_uri.'/'.explode('?', $image_name)[0],
                'name' => $image_name,
            ]);
        } else {
            ProgressParseNews::errorParseNewsAdd('В результате парсинга картинка не найдена: '.$link);
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
        if( $eval->length != 0){
            foreach($eval as $item){
                return $this->remove_emoji(trim($item->textContent . PHP_EOL));
            }
        }
        return "Не найдено.";
    }

    public function remove_emoji($string) {
        $symbols = "\x{1F100}-\x{1F1FF}"
            ."\x{1F300}-\x{1F5FF}"
            ."\x{1F600}-\x{1F64F}"
            ."\x{1F680}-\x{1F6FF}"
            ."\x{1F900}-\x{1F9FF}"
            ."\x{2600}-\x{26FF}"
            ."\x{2700}-\x{27BF}";

        return preg_replace('/['. $symbols . ']+/u', '', $string);
    }
}
