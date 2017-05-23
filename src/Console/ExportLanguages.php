<?php

namespace CatLab\Lang2Csv\Console;

use App\Http\Controllers\ExportController;
use Illuminate\Console\Command;

/**
 * Class ExportLanguages
 * @package CatLab\Lang2Csv\Console
 */
class ExportLanguages extends Command
{
    protected $controller;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all translation files to a single csv file.';

    private $parsedResources;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $languages = [
            'en',
            'nl',
            'fr'
        ];

        $this->parsedResources = [];
        $this->scanFolder('', resource_path('lang'));

        // Now turn into rows
        $rows = [];

        $keys = array_keys($this->parsedResources['keys']);
        foreach (array_keys($this->parsedResources['languages']) as $language) {
            if (array_search($language, $languages) === false) {
                $languages[] = $language;
            }
        }

        // Header row
        $row = [
            'Key'
        ];
        foreach ($languages as $language) {
            $row[] = $language;
        }
        $rows[] = $row;

        foreach ($keys as $key) {
            $row = [ $key ];
            foreach ($languages as $language) {
                $row[] = isset($this->parsedResources[$language][$key]) ? $this->parsedResources[$language][$key] : null;
            }
            $rows[] = $row;
        }

        $filename = storage_path('app') . '/translations.csv';
        $file = fopen($filename, 'w');

        // save each row of the data
        foreach ($rows as $row) {
            fputcsv($file, $row);
        }
        fclose($file);
    }

    /**
     * @param $prefix
     * @param $path
     */
    protected function scanFolder($prefix, $path)
    {
        $languages = scandir($path);
        foreach ($languages as $language) {

            switch ($language) {
                case '.':
                case '..':
                    break;

                case 'vendor':
                    $this->scanVendorFolder($prefix . 'vendor.', $path . '/' . $language);
                    break;

                default:
                    $this->scanLanguage($prefix, $path . '/' . $language, $language);
                    break;
            }

        }
    }

    /**
     * @param $prefix
     * @param $path
     */
    protected function scanVendorFolder($prefix, $path)
    {
        $vendors = scandir($path);
        foreach ($vendors as $vendor) {
            switch ($vendor) {
                case '.':
                case '..':
                    break;

                default:
                    $packages = scandir($path . '/' . $vendor);
                    foreach ($packages as $v) {
                        switch ($v) {
                            case '.':
                            case '..':

                                break;


                            default:
                                $this->scanFolder(
                                    $prefix . $vendor . '.' . $v . '.',
                                    $path . '/' . $vendor . '/' . $v
                                );
                                break;
                        }
                    }
                    break;
            }
        }
    }

    /**
     * @param $prefix
     * @param $path
     * @param $language
     */
    protected function scanLanguage($prefix, $path, $language)
    {
        $this->output->writeln('Scanning ' . str_replace(dirname(storage_path()), '', $path) . ' for ' . $language);

        if (!isset($this->parsedResources['languages'])) {
            $this->parsedResources['languages'] = [];
        }

        if (!isset($this->parsedResources[$language])) {
            $this->parsedResources[$language] = [];
            $this->parsedResources['languages'][$language] = true;
        }

        $parts = scandir($path);
        foreach ($parts as $v) {
            switch ($v) {
                case '.':
                case '..':
                    break;

                default:
                    // This is a language file
                    $atts = include ($path . '/' . $v);
                    $rootpath = $prefix . $this->dropExtension($v);

                    $files = array_dot([ $rootpath => $atts ]);
                    foreach ($files as $kk => $vv) {
                        // Arrays can only exist if they are empty, so skip.
                        if (is_array($vv)) {
                            continue;
                        }

                        $this->parsedResources['keys'][$kk] = true;
                        $this->parsedResources[$language][$kk] = $vv;
                    }

                    break;
            }
        }
    }

    /**
     * @param $filename
     * @return mixed
     */
    protected function dropExtension($filename)
    {
        $x = explode('.', $filename);
        array_pop($x);
        return implode($x, '.');
    }
}
