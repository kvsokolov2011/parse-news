<?php

namespace Cher4geo35\ParseNews\Jobs\Admin;

use App\News;
use Cher4geo35\ParseNews\Models\ProgressParseNews;
use Cher4geo35\ParseNews\Traits\ParseImage;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ParseGalleryToDB implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use ParseImage;

    protected $gallery_db;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($gallery_db)
    {
        $this->gallery_db = $gallery_db;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $j=1;
        while(News::query()->where("slug", $this->gallery_db->slug)->first() == null){
            sleep(1);
            if($j > 300) {
                ProgressParseNews::errorParseNewsAdd('Ошибка сохранения галереи <b>' . $this->gallery_db->slug . '</b> (проверьте скорость интернет соединения)');
                exit;
            }
            $j++;
        }
            $news = News::query()->where("slug", $this->gallery_db->slug)->first();
            if($this->gallery_db->link_images_gallery != 'Не найдено.'){
                foreach ($this->gallery_db->link_images_gallery as $image_gallery){
                    $image_gallery = $this->uploadImages($image_gallery, 'gallery/news');
                    $news->images()->save($image_gallery);
                }
            }
            $news->save();
    }
}
