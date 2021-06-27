<?php

/**
 * Static service to support choices in forms.
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

/**
 * Static service to support choices in forms.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class StaticChoiceLoader
{
    private static $translationsDirectory;

    /**
     * collects all source files by translation directory.
     */
    public static function searchSourceFiles(): array
    {
        $sourceFiles = File::collectTranslationFiles(static::$translationsDirectory, 'en');

        $choices = [];
        foreach ($sourceFiles as $fileName) {
            $fileNameInformation = explode('.', $fileName);
            $choices[$fileNameInformation[0]] = $fileName;
        }

        ksort($choices);

        return $choices;
    }

    /**
     * @param string $translationsDirectory
     */
    public static function setTranslationsDirectory($translationsDirectory): void
    {
        static::$translationsDirectory = $translationsDirectory;
    }
}
