<?php

namespace CatLab\Lang2Csv\Console;

use CatLab\Lang2Csv\FileWriter;
use Illuminate\Console\Command;
use File;

/**
 * Class ImportLanguages
 *
 * Import a csv
 *
 * @package CatLab\Lang2Csv\Console
 */
class ImportLanguages extends Command
{
    /**
     * Vendor folder name
     */
    const VENDOR_FOLDER_NAME = 'vendor';

    /**
     * Path separator
     */
    const DIRECTORY_SEPARATOR = DIRECTORY_SEPARATOR;

    /**
     * Translation file extension
     */
    const TRANSLATION_FILE_EXTENSION = '.php';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:import {csvFile} {--target=} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import translations from a csv file. Warning, overwrites translation files!';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $file = $this->argument('csvFile');;

        $targetFolder = $this->option('target');
        if (!$targetFolder) {
            $targetFolder = resource_path('lang');
        }

        $force = $this->option('force');

        if (!is_readable($file)) {
            $this->error('Provided file is not readable');
            return;
        }

        if (!$force && !$this->confirm('This action will overwrite all your localization files. Are you sure?')) {
            return;
        }

        $languages = $this->readCsv($file);

        // For each language, write the language folder
        foreach ($languages as $language => $records) {
            $this->writeLanguage($language, $records, $targetFolder);
        }
    }

    /**
     * Read csv and return an array with each language, containing an assoc array with all translations
     * @param $file
     * @return array
     */
    protected function readCsv($file)
    {
        $languages = [];
        $firstRow = true;

        $columnToLanguageMap = [];

        if (($handle = fopen($file, "r")) !== false) {
            while (($data = fgetcsv($handle)) !== false) {

                $translationKey = array_shift($data);

                if ($firstRow) {
                    $firstRow = false;
                    // Prepare the column names
                    foreach ($data as $k => $v) {
                        if (!empty($v)) {
                            $columnToLanguageMap[$k] = $v;
                            $languages[$v] = [];
                        }
                    }
                } else {
                    foreach ($data as $k => $v) {
                        if (!empty($v) && isset($columnToLanguageMap[$k])) {
                            $language = $columnToLanguageMap[$k];
                            $languages[$language][$translationKey] = $v;
                        }
                    }
                }
            }
            fclose($handle);
        }

        return $languages;
    }

    protected function writeLanguage($language, $languageData, $targetFolder)
    {
        $this->info('Writing ' . count($languageData) . ' translations for ' . $language);

        // Reverse the array_dot function
        $expandedTranslations = array();
        foreach ($languageData as $key => $value) {
            array_set($expandedTranslations, $key, $value);
        }

        // Array now contains multidimensional array


        // Look for "vendor" package
        if (isset($expandedTranslations[self::VENDOR_FOLDER_NAME])) {
            $this->processVendorFolder(
                self::VENDOR_FOLDER_NAME,
                $language,
                $expandedTranslations[self::VENDOR_FOLDER_NAME],
                $targetFolder
            );

            // Unset value so that it is ignored from here on
            unset($expandedTranslations[self::VENDOR_FOLDER_NAME]);
        }

        $targetFolder = $targetFolder . self::DIRECTORY_SEPARATOR . $language . self::DIRECTORY_SEPARATOR;
        $this->writeLanguageFolderToDirectory($expandedTranslations, $targetFolder);
    }

    /**
     * Process the vendor folder
     * @param $packages
     * @param $language
     * @param $data
     * @param $targetFolder
     */
    protected function processVendorFolder($packages, $language, array $data, $targetFolder)
    {
        // Special processing
        if (!is_array($data)) {
            $this->warn('Vendor folder does not contain array. Skipping');
            return;
        }

        foreach ($data as $package => $translations) {

            if (!is_array($translations)) {
                $this->warn('Vendor package ' . $package . self::DIRECTORY_SEPARATOR . $package . 'does not contain array. Skipping');
                continue;
            }

            $targetFolder =
                $targetFolder . self::DIRECTORY_SEPARATOR .
                self::VENDOR_FOLDER_NAME . self::DIRECTORY_SEPARATOR .
                $package . self::DIRECTORY_SEPARATOR .
                $language . self::DIRECTORY_SEPARATOR;

            $this->writeLanguageFolderToDirectory($translations, $targetFolder);
        }
    }

    /**
     * @param $languageData
     * @param $targetFolder
     */
    protected function writeLanguageFolderToDirectory(array $languageData, $targetFolder)
    {
        $this->output->writeln('Writing ' . count($languageData) . ' files to ' . $targetFolder);

        // Make sure directory exists
        if (
            !File::exists($targetFolder) &&
            !File::makeDirectory($targetFolder, 0775, true)
        ) {
            $this->error('Failed creating folder ' . $targetFolder);
            return;
        }

        foreach ($languageData as $file => $content) {
            $this->writeLanguageFile($content, $targetFolder . $file . self::TRANSLATION_FILE_EXTENSION);
        }
    }

    /**
     * Write a single translation file.
     * @param array $translations
     * @param $targetFile
     */
    protected function writeLanguageFile(array $translations, $targetFile)
    {
        $this->output->writeln('Writing ' . count($translations) . ' translations to ' . $targetFile);

        $writer = new FileWriter($targetFile);
        $writer->write($translations);
        $writer->save();
    }
}
