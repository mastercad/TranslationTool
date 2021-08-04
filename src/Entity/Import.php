<?php

/**
 * Import Entity.
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

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entity class for Import.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class Import
{
    /**
     * @Assert\NotBlank(message="Please, upload the translation package zip file.")
     * @Assert\File(mimeTypes={ "application/zip", "zip" })
     */
    private $translationPackage;

    /**
     * @return mixed
     */
    public function getTranslationPackage()
    {
        return $this->translationPackage;
    }

    /**
     * @param mixed $translationPackage
     */
    public function setTranslationPackage($translationPackage): Import
    {
        $this->translationPackage = $translationPackage;

        return $this;
    }
}
