<?php

/**
 * Service to generate statistics for translation files.
 *
 * PHP version 7
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    GIT: $Id$
 *
 * @see       http://pear.php.net/package/PackageName
 * @since      File available since Release 1.0.0
 */

namespace App\Service;

use App\Service\Xliff\Diff;

/**
 * Service to generate statistics for translation files.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class Statistic extends ServiceAbstract
{
    /**
     * directory with translation files.
     *
     * @var string|null
     */
    private $translationsDirectory;

    /**
     * target translation language.
     *
     * @var string|null
     */
    private $translationLanguage;

    /**
     * source translation language.
     *
     * @var string|null
     */
    private $sourceLanguage;

    /**
     * source translation files.
     *
     * @var array|null
     */
    private $sourceFiles;

    /**
     * generates statistic collection.
     */
    public function generate(): array
    {
        $statsCollection = [];
        foreach ($this->getSourceFiles() as $sourceFile) {
            $sourceFileInformation = explode('.', $sourceFile);
            $statsCollection[$sourceFileInformation[0]] = [];

            $currentTranslationFiles = File::searchCurrentTranslationFiles(
                $sourceFile,
                $this->getTranslationsDirectory()
            );
            foreach ($currentTranslationFiles as $currentTranslationFile) {
                $currentTranslationFileInformation = explode('.', $currentTranslationFile);

                if ($this->getSourceLanguage() !== $currentTranslationFileInformation[1]) {
                    $diffService = new Diff($this->getTranslator());
                    $diffService->setSourceFilePathName(
                        $this->getTranslationsDirectory().'/'.$sourceFile
                    )
                        ->setTranslationFilePathName(
                            $this->getTranslationsDirectory().'/'.$currentTranslationFile
                        )
                        ->setSourceData([])
                        ->setTranslationData([])
                        ->diff();

                    $statsCollection[$sourceFileInformation[0]][$currentTranslationFileInformation[1]] =
                        [
                            'translated' => $diffService->getTranslatedCount(),
                            'items' => $diffService->getItemCount(),
                            'same' => $diffService->getSameCount(),
                            'empty' => $diffService->getEmptyCount(),
                        ];
                }
            }
        }

        ksort($statsCollection);

        return $statsCollection;
    }

    public function getTranslationsDirectory(): ?string
    {
        return $this->translationsDirectory;
    }

    /**
     * @param string|null $translationsDirectory
     */
    public function setTranslationsDirectory($translationsDirectory): Statistic
    {
        $this->translationsDirectory = $translationsDirectory;

        return $this;
    }

    public function getTranslationLanguage(): ?string
    {
        return $this->translationLanguage;
    }

    /**
     * @param string|null $translationLanguage
     */
    public function setTranslationLanguage($translationLanguage): Statistic
    {
        $this->translationLanguage = $translationLanguage;

        return $this;
    }

    public function getSourceLanguage(): ?string
    {
        return $this->sourceLanguage;
    }

    /**
     * @param string|null $sourceLanguage
     */
    public function setSourceLanguage($sourceLanguage): Statistic
    {
        $this->sourceLanguage = $sourceLanguage;

        return $this;
    }

    public function getSourceFiles(): ?array
    {
        return $this->sourceFiles;
    }

    /**
     * @param array|null $sourceFiles
     */
    public function setSourceFiles($sourceFiles): Statistic
    {
        $this->sourceFiles = $sourceFiles;

        return $this;
    }
}
