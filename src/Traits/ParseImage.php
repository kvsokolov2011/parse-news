<?php

namespace Cher4geo35\ParseNews\Traits;

use App\Image;
use App\Meta;
use App\News;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait ParseImage
{

    public function getAndClearLink($image_news){
        $temp_link_image = trim($image_news->textContent . PHP_EOL);
        $url = pathinfo($temp_link_image);
        return $url['dirname'] . '/' . $url['basename'];
    }

    public function getWidthImage($link){
        return getimagesize ($link)[0];
    }

    public function getSizeImage($link){
        return array_change_key_case(get_headers($link,1))['content-length'];
    }

    public function checkImage($link){
        $imgExts = array("gif", "jpg", "jpeg", "png", "tiff", "tif");
        $ext = pathinfo($link, PATHINFO_EXTENSION);
        if (!in_array($ext, $imgExts)) {
            // TODO Обработать ошибку
            // Файл не является изображением
        }
    }

    public function stringToTime($date){
        $date = preg_replace('/,/', "", $date);
        $ru_month = array( 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря' );
        $number_month = array( '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12' );
        $date = str_replace($ru_month, $number_month, $date);
        preg_match_all('/\d+/', $date, $arrDate);
        $date = $arrDate[0][2]."-".$arrDate[0][1]."-".$arrDate[0][0]." 08:00:00";
        return $date;
    }

    public function createDirectory($image_uri){
        $directory = public_path('storage/'.$image_uri);
        if (!file_exists($directory)){
            if (!mkdir($directory, 0755, true)) {
                // TODO Обработать ошибку
                //Не удалось создать директорию
            }
        }
        return $directory;
    }

    public function putFile($link, $directory){
        $contents = file_get_contents($link);
        $ext = pathinfo($link, PATHINFO_EXTENSION);
        $image_name = pathinfo($link, PATHINFO_FILENAME).'.'.$ext;
        file_put_contents($directory.'/'.$image_name, $contents);
        return $image_name;
    }

    public function uploadImages($link, $dir_uri){
        if($link != "Не найдено."){
            $directory = $this->createDirectory($dir_uri);
            $image_name = $this->putFile($link, $directory);
            return Image::create([
                'path' => $dir_uri.'/'.$image_name,
                'name' => $image_name,
            ]);
        } else {
            // TODO Обработать ошибку
            //Картинка не найдена
        }
    }

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

        DB::table('failed_jobs')->delete();
    }

    public function clearDir($dir){
        if ($files = glob($dir . '/*')){
            foreach($files as $file):
                if(!is_dir($file)) unlink($file);
            endforeach;
        }
    }

    public function getMetaContent($eval){
        if( $eval->length !=0){
            foreach($eval as $item){
                return trim($item->textContent . PHP_EOL);
            }
        }
    }
}
