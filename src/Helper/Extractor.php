<?php

/**
 * Static Helper class to extract information from given file.
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

namespace App\Helper;

/**
 * Helper to extract information from given file name.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class Extractor
{
    public const AREA = 'area';
    public const LANGUAGE = 'language';
    public const EXTENSION = 'extension';

    /**
     * @param string $filePathName
     *
     * @return string
     */
    public static function extractLanguageFromFileName($filePathName): ?string
    {
        $fileInformation = static::extractFileInformation($filePathName);

        return $fileInformation[static::LANGUAGE];
    }

    /**
     * @param string $filePathName
     */
    public static function extractFileInformation($filePathName): array
    {
        $fileName = basename($filePathName);
        $regEx = '/(?P<'.static::AREA.'>[a-z0-9_]+)\.'.
                 '(?P<'.static::LANGUAGE.'>[a-z_]+)\.'.
                 '(?P<'.static::EXTENSION.'>[a-z0-9]{3,5})$/i';

        if (preg_match($regEx, $fileName, $matches)) {
            return $matches;
        }

        return [
            static::LANGUAGE => null,
            static::AREA => null,
            static::EXTENSION => null,
        ];
    }
}
