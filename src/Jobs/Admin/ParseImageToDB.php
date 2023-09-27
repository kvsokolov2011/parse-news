<?php

namespace Cher4geo35\ParseNews\Jobs\Admin;

use App\News;
use Cher4geo35\ParseNews\Models\ProgressParseNews;
use Cher4geo35\ParseNews\Traits\ParseImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PortedCheese\BaseSettings\Traits\ShouldImage;

class ParseImageToDB implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use ShouldImage, ParseImage;

    protected $image_db;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($image_db)
    {
        $this->image_db = $image_db;
    }

    /**
     * @return void
     *
     *
     */
    public function handle()
    {
        $j=1;
        while(News::query()->where("slug", $this->image_db->slug)->first() == null){
            sleep(1);
            if($j > 80) {
                ProgressParseNews::errorParseNewsAdd('Картинку <b> ' . $this->image_db->slug . ' </b> не удалось сохранить. (проверьте скорость интернет соединения)');
                exit;
            }
            $j++;
        }
        $news = News::query()->where("slug", $this->image_db->slug)->first();
        $news->clearImage();
        $img = $this->uploadImages($this->image_db->link_image, 'news/main');
        if($img != false){
            $news->main_image = $img->id;
            $news->save();
        }
    }
}
