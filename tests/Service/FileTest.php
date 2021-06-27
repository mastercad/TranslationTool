<?php

/**
 * Tests for file service.
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

namespace Tests\AppBundle\Service;

use App\Service\File;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests for file service.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class FileTest extends WebTestCase
{
    public function testRmDirRecursive()
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (!file_exists($translationsDirectory)) {
            mkdir($translationsDirectory, 0777, true);
        }
        touch($translationsDirectory.'/testfile.txt');
        mkdir($translationsDirectory.'/subfolder/', 0777, true);
        touch($translationsDirectory.'/subfolder/test.log');

        $this->assertTrue(File::rmDirRecursive($translationsDirectory));
        $this->assertFalse(file_exists($translationsDirectory));
    }

    /**
     * @depends testRmDirRecursive
     */
    public function testDirIsEmptyTrue()
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            $this->assertTrue(rmdir($translationsDirectory));
        }

        $this->assertTrue(mkdir($translationsDirectory, 0777, true));
        $this->assertTrue(File::isDirEmpty($translationsDirectory));
    }

    public function testDirIsEmptyWithoutDir()
    {
        $this->assertNull(File::isDirEmpty(null));
    }

    /**
     * @depends testRmDirRecursive
     */
    public function testDirWithDottedFileIsEmptyFalse()
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            File::rmDirRecursive($translationsDirectory);
        }
        $this->assertTrue(mkdir($translationsDirectory, 0777, true));
        touch($translationsDirectory.'/.testdir.log');
        $this->assertFalse(File::isDirEmpty($translationsDirectory));
    }

    /**
     * @depends testRmDirRecursive
     */
    public function testDirWithRegularFileIsEmptyFalse()
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            File::rmDirRecursive($translationsDirectory);
        }
        $this->assertTrue(mkdir($translationsDirectory, 0777, true));
        touch($translationsDirectory.'/testdir.log');
        $this->assertFalse(File::isDirEmpty($translationsDirectory, true));
    }

    /**
     * @depends testRmDirRecursive
     */
    public function testDirWithDottedFileIsEmptyTrue()
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            File::rmDirRecursive($translationsDirectory);
        }
        $this->assertTrue(mkdir($translationsDirectory, 0777, true));
        touch($translationsDirectory.'/.testdir.log');
        $this->assertTrue(File::isDirEmpty($translationsDirectory, true));
    }

    public function testSearchCurrentTranslationFiles()
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            File::rmDirRecursive($translationsDirectory);
        }
        $this->assertTrue(mkdir($translationsDirectory, 0777, true));
        copy(__DIR__.'/Xliff/files/source.en.xlf', $translationsDirectory.'/source.en.xlf');
        copy(
            __DIR__.'/Xliff/files/file_with_missing_and_empty_trans.de.xlf',
            $translationsDirectory.'/file_with_missing_and_empty_trans.de.xlf'
        );
        copy(
            __DIR__.'/Xliff/files/file_with_missing_trans.de.xlf',
            $translationsDirectory.'/file_with_missing_trans.de.xlf'
        );

        $currentTranslationFiles = File::searchCurrentTranslationFiles('source.en.xlf', $translationsDirectory);
        $expectation = [
            'source.en.xlf',
        ];

        $this->assertEquals($expectation, $currentTranslationFiles);
    }
}
