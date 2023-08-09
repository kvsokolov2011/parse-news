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

class ParseSinglePageToDB implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use ParseImage;

    protected $pagedb;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($pagedb)
    {
        $this->pagedb = $pagedb;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $j=1;
        while(News::query()->where("slug", $this->pagedb->slug)->first() == null){
            sleep(1);
            if($j > 300) {
                ProgressParseNews::errorParseNewsAdd('Ошибка сохранения страницы <b> ' . $this->pagedb->slug . ' </b>. (проверьте скорость интернет соединения)');
                exit;
            }
            $j++;
        }
            $news = News::query()->where("slug", $this->pagedb->slug)->first();
            $news->title = $this->pagedb->title;
            $news->description = $this->pagedb->description;
            if($this->pagedb->date != 'Не найдено.'){
                $news->created_at = $this->pagedb->date;
                $news->published_at = $this->pagedb->date;
                $news->updated_at = $this->pagedb->date;
            }
            $news->save();

        $this->pagedb->meta_title_news != "Не найдено." ? $this->updateMeta($this->pagedb->meta_title_news, 'title', $news->id) : ProgressParseNews::errorParseNewsAdd('Мета title страницы <b>'.$this->pagedb->slug.'</b> не найдено');
        $this->pagedb->meta_description_news != "Не найдено." ? $this->updateMeta($this->pagedb->meta_description_news, 'description', $news->id) : ProgressParseNews::errorParseNewsAdd('Мета description страницы <b>'.$this->pagedb->slug.'</b> не найдено');
        $this->pagedb->meta_keywords_news != "Не найдено." ? $this->updateMeta($this->pagedb->meta_keywords_news, 'keywords', $news->id) : ProgressParseNews::errorParseNewsAdd('Мета keywords страницы<b>'.$this->pagedb->slug.'</b> не найдено');
    }

    /**
     * @param $content
     * @param $name
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|void
     *
     * Обновляем мета в таблице
     */
    private function updateMeta($content, $name, $id){
        if($content != null){
            try{
                $meta = Meta::query()
                    ->where('metable_id', $id)
                    ->where('name', $name)
                    ->firstOrFail();
                if($meta->content != $content){
                    $meta->update([
                        'content' => $content,
                        'separated' => 0,
                    ]);
                }
            } catch (Exception $e) {
                $result = Meta::getModel('news', $id, $name);
                if (!$result['success']) {
                    return redirect()
                        ->back()
                        ->with('danger', $result['message']);
                }
                $model = $result['model'];
                $meta = Meta::create([
                    'name' => $name,
                    'content' => $content
                ]);
                $meta->metable()->associate($model);
                $meta->save();
            }
        }
    }
}
