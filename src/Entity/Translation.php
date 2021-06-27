<?php

/**
 * Entity for translation.
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

namespace App\Entity;

/**
 * Entity class for translation.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class Translation
{
    /**
     * @var array
     */
    private $translationData = [];

    /**
     * @var array
     */
    private $sourceData = [];

    /**
     * @var string|null
     */
    private $exportFileName;

    /**
     * @var string|null
     */
    private $translationLanguage;

    public function getTranslationData(): array
    {
        return $this->translationData;
    }

    public function setTranslationData(array $translationData): Translation
    {
        $this->translationData = $translationData;

        return $this;
    }

    public function getSourceData(): array
    {
        return $this->sourceData;
    }

    public function setSourceData(array $sourceData): Translation
    {
        $this->sourceData = $sourceData;

        return $this;
    }

    public function getExportFileName(): ?string
    {
        return $this->exportFileName;
    }

    /**
     * @param string|null $exportFileName
     */
    public function setExportFileName($exportFileName): Translation
    {
        $this->exportFileName = $exportFileName;

        return $this;
    }

    public function getTranslationLanguage(): ?string
    {
        return $this->translationLanguage;
    }

    /**
     * @param string|null $translationLanguage
     */
    public function setTranslationLanguage($translationLanguage): Translation
    {
        $this->translationLanguage = $translationLanguage;

        return $this;
    }
}
