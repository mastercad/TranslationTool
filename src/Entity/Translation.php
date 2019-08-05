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
 * @package    App\Entity
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    GIT: $Id$
 * @link       http://pear.php.net/package/PackageName
 * @since      File available since Release 1.0.0
 */
namespace App\Entity;

/**
 * Entity class for translation.
 *
 * @package    App\Entity
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
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

    /**
     * @return array
     */
    public function getTranslationData(): array
    {
        return $this->translationData;
    }

    /**
     * @param array $translationData
     *
     * @return Translation
     */
    public function setTranslationData(array $translationData): Translation
    {
        $this->translationData = $translationData;

        return $this;
    }

    /**
     * @return array
     */
    public function getSourceData(): array
    {
        return $this->sourceData;
    }

    /**
     * @param array $sourceData
     *
     * @return Translation
     */
    public function setSourceData(array $sourceData): Translation
    {
        $this->sourceData = $sourceData;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExportFileName(): ?string
    {
        return $this->exportFileName;
    }

    /**
     * @param string|null $exportFileName
     *
     * @return Translation
     */
    public function setExportFileName($exportFileName): Translation
    {
        $this->exportFileName = $exportFileName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTranslationLanguage(): ?string
    {
        return $this->translationLanguage;
    }

    /**
     * @param string|null $translationLanguage
     *
     * @return Translation
     */
    public function setTranslationLanguage($translationLanguage): Translation
    {
        $this->translationLanguage = $translationLanguage;

        return $this;
    }
}
