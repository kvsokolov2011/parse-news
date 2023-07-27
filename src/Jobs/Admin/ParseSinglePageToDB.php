<?php

namespace Cher4geo35\ParseNews\Jobs\Admin;

use App\Meta;
use App\News;
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
        try {
            $news = News::query()->where("slug", $this->pagedb->slug)->firstOrFail();
            $news->description = $this->pagedb->description;
            $news->created_at = $this->pagedb->date;
            $news->published_at = $this->pagedb->date;
            $news->updated_at = $this->pagedb->date;
            $news->save();

        } catch (Exception $e) {
            $news = new News;
            $news->description = $this->pagedb->description;
            $news->slug = $this->pagedb->slug;
            $news->created_at = $this->pagedb->date;
            $news->published_at = $this->pagedb->date;
            $news->updated_at = $this->pagedb->date;
            $news->save();
        }

        $this->updateMeta($this->pagedb->meta_title_news, 'title', $news->id);
        $this->updateMeta($this->pagedb->meta_description_news, 'description', $news->id);
        $this->updateMeta($this->pagedb->meta_keywords_news, 'keywords', $news->id);
    }

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
