<?php

namespace Cher4geo35\ParseNews\Console\Commands;

use App\Menu;
use App\MenuItem;
use PortedCheese\BaseSettings\Console\Commands\BaseConfigModelCommand;

class ParseNewsMakeCommand extends BaseConfigModelCommand
{
    protected $scssIncludes = [
        "app" => ["parse-news"],
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:parse-news
                    {--all : Run all}
                    {--menu : Config menu}
                    {--models : Export models}
                    {--config : Make config}
                    {--controllers : Export controllers}
                    {--vue : Export vue files}
                    {--jobs : Export jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $vendorName = 'Cher4geo35';
    protected $packageName = "ParseNews";

    protected $models = ['ProgressParseNews'];

    protected $controllers = [
        "Admin" => ["ParseNewsController"]
    ];

    protected $jobs = [
        "Admin" => ["ParseImageToDB", "ParseListPages", "ParseListPagesToDb", "ParseSinglePge", "ParseSinglePageToDB", "ParseGalleryToDB"]
    ];

    protected $vueFolder = "parse-news";

    protected $vueIncludes = [
        'admin' => [
            'progress-bar' => "ProgressBar",
        ],
    ];

    protected $configName = "parse-news";
    protected $configTitle = "Парсинг новостей";
    protected $configTemplate = "parse-news::admin.settings";
    protected $configValues = [
        'pager' => 20,
        'path' => 'parse-news',
    ];

    protected $ruleRules = [
        [
            "title" => "Парсинг новостей",
            "slug" => "parse-news",
            "policy" => "ParseNewsPolicy",
        ],
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $all = $this->option("all");

        if ($this->option("menu") || $all) {
            $this->makeMenu();
        }

        if ($this->option("vue") || $all) {
            $this->makeVueIncludes('admin');
        }

        if ($this->option("models") || $all) {
            $this->exportModels();
        }

        if ($this->option("controllers") || $all) {
            $this->exportControllers("Admin");
        }

        if ($this->option("jobs") || $all) {
            $this->exportJobs("Admin");
        }

        if ($this->option("config") || $all) {
            $this->makeConfig();
        }

//        if ($this->option("scss") || $all) {
//            $this->makeScssIncludes('app');
//        }

//        if ($this->option("policies") || $all) {
//            $this->makeRules();
//        }
    }

    protected function makeMenu()
    {
        try {
            $menu = Menu::query()
                ->where('key', 'admin')
                ->firstOrFail();
        }
        catch (\Exception $e) {
            return;
        }

        $title = "Парсинг новостей";
        $itemData = [
            'title' => $title,
            'template' => "parse-news::admin.parse-news.menu",
            'url' => "#",
            'ico' => 'fas fa-angle-double-right',
            'menu_id' => $menu->id,
        ];

        try {
            $menuItem = MenuItem::query()
                ->where("menu_id", $menu->id)
                ->where('title', $title)
                ->firstOrFail();
            $menuItem->update($itemData);
            $this->info("Элемент меню '$title' обновлен");
        }
        catch (\Exception $e) {
            MenuItem::create($itemData);
            $this->info("Элемент меню '$title' создан");
        }
    }

    protected function exportJobs($place)
    {
        if (empty($this->jobs[$place])) {
            $this->info("$place not found in jobs");
            return;
        }
        foreach ($this->jobs[$place] as $job) {
            if (file_exists(app_path("Jobs/Vendor/{$this->packageName}/{$place}/{$job}.php"))) {
                if (! $this->confirm("The [{$place}/$job.php] job already exists. Do you want to replace it?")) {
                    continue;
                }
            }

            if (! is_dir($directory = app_path("Jobs/Vendor/{$this->packageName}/{$place}"))) {
                mkdir($directory, 0755, true);
            }

            try {
                file_put_contents(
                    app_path("Jobs/Vendor/{$this->packageName}/{$place}/{$job}.php"),
                    $this->compileJobStub($place, $job)
                );

                $this->info("[{$place}/$job.php] created");
            }
            catch (\Exception $e) {
                $this->error("Failed put job");
            }
        }
    }

    protected function compileJobStub($place, $job)
    {
        return str_replace(
            ['{{vndName}}','{{namespace}}', '{{pkgName}}', "{{place}}", "{{name}}"],
            [$this->vendorName, $this->getAppNamespace(), $this->packageName, $place, $job],
            file_get_contents(__DIR__ . "/stubs/make/jobs/StubJob.stub")
        );
    }
}
