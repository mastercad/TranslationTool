<?php

/**
 * Entity for live translations.
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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entity class for live translations.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class Live
{
    /**
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank(message="Please, upload the source xliff file.")
     * @Assert\File(mimeTypes={ "text/xml", "application/xml", "xlf" })
     */
    private $sourceFile;

    /**
     * @ORM\Column(type="string")
     */
    private $sourceFileName;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\File(mimeTypes={ "text/xml", "application/xml", "xlf" })
     */
    private $translationFile;

    /**
     * @ORM\Column(type="string")
     */
    private $translationFileName;

    /**
     * @ORM\Column(type="string")
     *
     * \\@Assert\NotBlank(message="Please, select a language to translate.")
     */
    private $translationLanguage;

    /**
     * @param string $sourceFile
     */
    public function setSourceFile($sourceFile): Live
    {
        $this->sourceFile = $sourceFile;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSourceFile()
    {
        return $this->sourceFile;
    }

    /**
     * @param string $sourceFileName
     */
    public function setSourceFileName($sourceFileName): Live
    {
        $this->sourceFileName = $sourceFileName;

        return $this;
    }

    public function getSourceFileName(): ?string
    {
        return $this->sourceFileName;
    }

    /**
     * @param string $translationFileName
     */
    public function setTranslationFileName($translationFileName): Live
    {
        $this->translationFileName = $translationFileName;

        return $this;
    }

    public function getTranslationFileName(): ?string
    {
        return $this->translationFileName;
    }

    /**
     * @param string $translationFile
     */
    public function setTranslationFile($translationFile): Live
    {
        $this->translationFile = $translationFile;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTranslationFile()
    {
        return $this->translationFile;
    }

    /**
     * @param string $translationLanguage
     */
    public function setTranslationLanguage($translationLanguage): Live
    {
        $this->translationLanguage = $translationLanguage;

        return $this;
    }

    public function getTranslationLanguage(): ?string
    {
        return $this->translationLanguage;
    }
}
