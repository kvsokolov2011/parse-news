<?php

namespace Cher4geo35\ParseNews\Jobs\Admin;

use App\Meta;
use App\News;
use Cher4geo35\ParseNews\Models\ProgressParseNews;
use Cher4geo35\ParseNews\Traits\ParseImage;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ParseListPagesToDB implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use ParseImage;

    protected $listdb;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($listdb)
    {
        $this->listdb = $listdb;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $news = News::query()->where("slug", $this->listdb->slug)->firstOrFail();
            $news->short = $this->listdb->short;
            $news->save();
        } catch (Exception $e) {
            $news = new News;
            $news->slug = $this->listdb->slug;
            $news->short = $this->listdb->short;
            $news->save();
        }

        $this->listdb->meta_title_news != 'Не найдено.' ? $this->updateMeta($this->listdb->meta_title_news, 'title') : ProgressParseNews::errorParseNewsAdd('Мета title страницы  <b>'. $this->listdb->slug.'</b> не найдено');
        $this->listdb->meta_description_news != 'Не найдено.' ? $this->updateMeta($this->listdb->meta_description_news, 'description') : ProgressParseNews::errorParseNewsAdd('Мета description  страницы  <b>'. $this->listdb->slug.'</b> не найдено');
        $this->listdb->meta_keywords_news != 'Не найдено.' ? $this->updateMeta($this->listdb->meta_keywords_news, 'keywords') : ProgressParseNews::errorParseNewsAdd('Мета keywords  страницы  <b>'. $this->listdb->slug.'</b> не найдено');
    }

    /**
     * @param $content
     * @param $name
     * @return void
     *
     * Сохраняем Мета в БД
     */
    private function updateMeta($content, $name){
        if($content != null){
            try{
                $meta = Meta::query()
                    ->where('name', $name)
                    ->where('page', 'news')
                    ->firstOrFail();
                $meta->update([
                    'separated' => 0,
                    'content' => $content,
                ]);

            } catch (Exception $e) {
                Meta::create([
                    'content' => $content,
                    'page' => 'news',
                    'name' => $name,
                ]);
            }
        }
    }
}
