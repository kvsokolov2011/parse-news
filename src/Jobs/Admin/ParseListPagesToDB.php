<?php

namespace Cher4geo35\ParseNews\Jobs\Admin;

use App\News;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ParseListPagesToDB implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
            $news->title = $this->listdb->title;
            $news->short = $this->listdb->short;
            $news->save();

        } catch (Exception $e) {
            $news = new News;
            $news->title = $this->listdb->title;
            $news->slug = $this->listdb->slug;
            $news->short = $this->listdb->short;
            $news->save();
        }
    }
}
