<?php

namespace Cher4geo35\ParseNews\Jobs\Admin;

use App\News;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ParseSinglePageToDB implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    }
}
