<?php

namespace CatLab\Lang2Csv;

use CatLab\Lang2Csv\Console\ImportLanguages;
use Illuminate\Support\ServiceProvider;
use CatLab\Lang2Csv\Console\ExportLanguages;

/**
 * Class Lang2CsvServiceProvider
 * @package CatLab\Lang2Csv
 */
class Lang2CsvServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands(
            ExportLanguages::class,
            ImportLanguages::class
        );
    }
}