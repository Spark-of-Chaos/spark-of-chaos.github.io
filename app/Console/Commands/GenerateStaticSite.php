<?php

namespace App\Console\Commands;

use Backstage\Models\Domain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use voku\helper\HtmlMin;

class GenerateStaticSite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'site:generate {--output=public/static/}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a static version of production sites';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching public content...');

        $domains = Domain::with('site.contents')
            ->where('environment', 'production')
            ->get();

        $urls = $domains->pluck('site.contents')
            ->flatten()
            ->filter(function ($content) {
                return $content->public == true;
            })
            ->pluck('url')
            ->map(function ($url) {
                return 'https://' . str_replace('https://', '', $url);
            })
            ->unique();

        foreach ($domains as $domain) {

            $this->info('Running npm build...');
            exec('npm run build');
            File::copyDirectory( './public/build', $this->option('output') . $domain->name .'/build');
            // vite build --outDir='. $this->option('output') . $domain->name .'/build', $output, $returnVar);

            // Get default filesystem disk
            $defaultDisk = config('filesystems.default');
            // Get the root and copy to output dir
            $root = config("filesystems.disks.{$defaultDisk}.root");
            $this->info("Copying assets from {$root} to {$this->option('output')}" . $domain->name);

            File::copyDirectory( $root, $this->option('output') . $domain->name);
            // Remove the .gitignore file if exists
            if (File::exists($this->option('output') . $domain->name . '/.gitignore')) {
                File::delete($this->option('output') . $domain->name . '/.gitignore');
            }

            // if ($returnVar !== 0) {
            //     $this->error('npm build failed.');
            //     foreach ($output as $line) {
            //         $this->error($line);
            //     }
            //     return 1;
            // }

            $this->info('npm build completed successfully.');

            
            $domainUrls = $urls->map(function ($url) use ($domain) {
                if ($url == config('app.url')) {
                    return 'https://' . $domain->name . '/index.html';
                }
                // $url = str_replace(config('app.url') . '/', '', $url);
                $url = str_replace(config('app.url'), $domain->name, $url);
                return 'https://'. $url .'.html';
            });

            foreach ($domain->site->contents as $page) {

                if ($page->public == false) {
                    continue;
                }

                $slug = $page->slug;
                $this->info("Processing page: {$page->url}");
                $path = empty($page->path) || $page->path == '/' ? 'index' : $page->path;
                $outputDir = $this->option('output') . $domain->name . '/' . $path . '.html';

                $response = Http::withoutVerifying()
                    ->get($page->url);
                if ($response->successful()) {
                    $content = $response->body();
                    $content = (new HtmlMin())->minify($content); 

                    $content = str_replace($urls, $domainUrls, $content);
                    $parsedUrl = parse_url(config('app.url'));
                    $originalDomain = $parsedUrl['host'] ?? config('app.url');
                    $content = str_replace($originalDomain, $domain->name, $content);

                    $path = empty($page->path) ? 'index' : $page->path;
                    
                    if (!is_dir(dirname($outputDir))) {
                        mkdir(dirname($outputDir), 0755, true);
                    }
                    file_put_contents($outputDir, $content);
                    $this->info("Saved static page to: {$outputDir}");
                } else {
                    $this->error("Failed to fetch page: {$slug}");
                }
            }
        }

        return 0;
    }
}
