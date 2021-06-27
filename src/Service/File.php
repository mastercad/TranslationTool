<?php

/**
 * Static service for file operations.
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

use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Static service for file operations.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class File
{
    /**
     * collect translation files for given language, en is default.
     *
     * @param $sourceDirectory
     * @param string $translationLanguage
     */
    public static function collectTranslationFiles($sourceDirectory, $translationLanguage = 'en'): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDirectory)
        );

        $regexFileIterator = new RegexIterator($iterator, '#\.'.$translationLanguage.'\.xlf$#');
        $fileNames = [];

        /** @var SplFileInfo $fileInfo */
        foreach ($regexFileIterator as $fileInfo) {
            $fileName = $fileInfo->getFilename();
            $fileNames[$fileName] = $fileName;
        }

        return $fileNames;
    }

    /**
     * search correspondent translation files for given filename.
     *
     * @param $fileName
     * @param $sourceDirectory
     */
    public static function searchCurrentTranslationFiles($fileName, $sourceDirectory): array
    {
        $fileInformation = explode('.', $fileName);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDirectory)
        );

        $regexFileIterator = new RegexIterator($iterator, '#'.$fileInformation[0].'\.[a-z]{2}\.xlf$#i');
        $fileNames = [];

        /** @var SplFileInfo $fileInfo */
        foreach ($regexFileIterator as $fileInfo) {
            $fileNames[] = $fileInfo->getFilename();
        }

        return $fileNames;
    }

    /**
     * move uploadedFile to destination Dir and returns unique file name.
     *
     * @param UploadedFile $sourceFile
     * @param string       $destinationDir
     */
    public static function moveUniqueUploadedFile($sourceFile, $destinationDir): string
    {
        $sourceFileName = md5(uniqid('translation_tool', true)).'.'.$sourceFile->guessExtension();
        $sourceFile->move(
            $destinationDir,
            $sourceFileName
        );

        return $sourceFileName;
    }

    /**
     * check, if given directory is empty.
     *
     * @param string $directory
     * @param bool   $ignoreHiddenFiles
     */
    public static function isDirEmpty($directory, $ignoreHiddenFiles = false): ?bool
    {
        if (!is_readable($directory)) {
            return null;
        }

        foreach (new DirectoryIterator($directory) as $file) {
            if (false === $file->isDot()
                && (
                    false === $ignoreHiddenFiles
                    || (
                        true === $ignoreHiddenFiles
                        && 0 !== strpos($file->getFilename(), '.')
                    )
                )
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * removes given directory recursive.
     *
     * @param $dir
     */
    public static function rmDirRecursive($dir): bool
    {
        foreach (scandir($dir, SCANDIR_SORT_NONE) as $file) {
            if ('.' === $file
                || '..' === $file
            ) {
                continue;
            }

            if (is_dir($dir.'/'.$file)) {
                static::rmDirRecursive($dir.'/'.$file);
            } else {
                unlink($dir.'/'.$file);
            }
        }

        return rmdir($dir);
    }

    /**
     * clean dir, also recursive delete folder.
     *
     * @param $dir
     */
    public static function cleanDir($dir): bool
    {
        foreach (scandir($dir, SCANDIR_SORT_NONE) as $file) {
            if ('.' === $file
                || '..' === $file
            ) {
                continue;
            }

            if (is_dir($dir.'/'.$file)) {
                static::rmDirRecursive($dir.'/'.$file);
            } else {
                unlink($dir.'/'.$file);
            }
        }

        return true;
    }
}
