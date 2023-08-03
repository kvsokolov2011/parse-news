<?php

namespace Cher4geo35\ParseNews\Jobs\Admin;

use App\Image;
use App\News;
use Cher4geo35\ParseNews\Traits\ParseImage;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
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
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $news = News::query()->where("slug", $this->image_db->slug)->firstOrFail();
            $news->clearImage();
            $image = $this->uploadImages($this->image_db->link_image, 'news/main');
            $news->image()->associate($image);
            $news->save();

        } catch (Exception $e) {
            $news = new News;
            $news->slug = $this->image_db->slug;
            $news->clearImage();
            $image = $this->uploadImages($this->image_db->link_image, 'news/main' );
            $news->image()->associate($image);
            $news->save();
        }
        Cache::put('completedJobs', Cache::get('completedJobs', 0)+1 );
    }
}
