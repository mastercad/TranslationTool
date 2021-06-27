<?php

/**
 * Tests for diff service.
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

namespace Tests\AppBundle\Service\Xliff;

use App\Service\Xliff\Diff;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Translation\Translator;

/**
 * Tests for diff service.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class DiffTest extends WebTestCase
{
    /**
     * @var Translator
     */
    protected static $translator;

    public static function setUpBeforeClass(): void
    {
        $kernel = static::createKernel();
        $kernel->boot();
        self::$translator = $kernel->getContainer()->get('translator');
    }

    public function testDiffWithMissingAndSameTranslations()
    {
        $diffService = new Diff(static::$translator);
        $diffService->setSourceFilePathName(__DIR__.'/files/source.en.xlf')
            ->setTranslationFilePathName(__DIR__.'/files/file_with_missing_trans.de.xlf')
            ->diff();

        $this->assertSame(6, $diffService->getItemCount());
        $this->assertSame(2, $diffService->getTranslatedCount());
        $this->assertSame(2, $diffService->getEmptyCount());
        $this->assertSame(2, $diffService->getSameCount());
    }

    public function testDiffWithMissingAndEmptyAndSameTranslations()
    {
        $diffService = new Diff(static::$translator);
        $diffService->setSourceFilePathName(__DIR__.'/files/source.en.xlf')
            ->setTranslationFilePathName(__DIR__.'/files/file_with_missing_and_empty_trans.de.xlf')
            ->diff();

        $this->assertSame(6, $diffService->getItemCount());
        $this->assertSame(2, $diffService->getTranslatedCount());
        $this->assertSame(3, $diffService->getEmptyCount());
        $this->assertSame(1, $diffService->getSameCount());
    }

    public function testDiffWithMissingSourceFile()
    {
        $this->expectException(\Symfony\Component\Filesystem\Exception\FileNotFoundException::class);
        $this->expectExceptionMessage('File not_there.xlf not Found!');

        $diffService = new Diff(static::$translator);
        $diffService->setSourceFilePathName(__DIR__.'/files/not_there.xlf')
            ->setTranslationFilePathName(__DIR__.'/files/file_with_missing_trans.de.xlf')
            ->diff();
    }

    public function testDiffWithMissingTranslationFile()
    {
        $this->expectException(\Symfony\Component\Filesystem\Exception\FileNotFoundException::class);
        $this->expectExceptionMessage('File translation_not_there.xlf not Found!');

        $diffService = new Diff(static::$translator);
        $diffService->setSourceFilePathName(__DIR__.'/files/source.en.xlf')
            ->setTranslationFilePathName(__DIR__.'/files/translation_not_there.xlf')
            ->diff();
    }

    public function testDiffWithMissingTranslationFileInOtherLanguage()
    {
        $this->expectException(\Symfony\Component\Filesystem\Exception\FileNotFoundException::class);
        $this->expectExceptionMessage('Datei translation_not_there.xlf nicht gefunden!');

        $translator = clone static::$translator;
        $translator->setLocale('de');
        $diffService = new Diff($translator);
        $diffService->setSourceFilePathName(__DIR__.'/files/source.en.xlf')
            ->setTranslationFilePathName(__DIR__.'/files/translation_not_there.xlf')
            ->diff();
    }
}
