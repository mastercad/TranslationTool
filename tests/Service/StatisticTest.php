<?php

/**
 * Tests for statistic service.
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
use App\Service\Statistic;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Tests for statistic service.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      File available since Release 1.0.0
 */
class StatisticTest extends WebTestCase
{
    public function testStatisticGeneration()
    {
        $client = static::createClient();
        /** @var TranslatorInterface $translator */
        $translator = $client->getContainer()->get('translator');
        $translationsDirectory = $client->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            $this->assertTrue(File::rmDirRecursive($translationsDirectory));
        }

        $this->assertTrue(mkdir($translationsDirectory, 0777, true));

        copy(
            __DIR__.'/../Service/Xliff/files/source.en.xlf',
            $translationsDirectory.'/source.en.xlf'
        );
        copy(
            __DIR__.'/../Service/Xliff/files/source.de.xlf',
            $translationsDirectory.'/source.de.xlf'
        );
        copy(
            __DIR__.'/../Service/Xliff/files/file_with_missing_trans.de.xlf',
            $translationsDirectory.'/file_with_missing_trans.de.xlf'
        );
        copy(
            __DIR__.'/../Service/Xliff/files/file_with_missing_and_empty_trans.de.xlf',
            $translationsDirectory.'/file_with_missing_and_empty_trans.de.xlf'
        );
        $statisticService = new Statistic($translator);
        $translationFiles = $statisticService->setSourceLanguage('en')
            ->setSourceFiles(
                File::collectTranslationFiles($translationsDirectory, 'en')
            )
            ->setTranslationsDirectory($translationsDirectory)
            ->generate();

        $expectation = [
            'source' => [
                'de' => [
                    'translated' => 1,
                    'items' => 6,
                    'same' => 3,
                    'empty' => 2,
                ],
            ],
        ];

        $this->assertEquals($expectation, $translationFiles);
    }
}
