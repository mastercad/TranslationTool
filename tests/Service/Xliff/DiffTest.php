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
 * @package    App\Test\Service\Xliff
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    GIT: $Id$
 * @link       http://pear.php.net/package/PackageName
 * @since      File available since Release 1.0.0
 */
namespace Tests\AppBundle\Service\Xliff;

use App\Service\Xliff\Diff;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Translation\Translator;

/**
 * Tests for diff service.
 *
 * @package    App\Test\Service\Xliff
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @since      Class available since Release 1.0.0
 */
class DiffTest extends WebTestCase
{
    /**
     * @var Translator
     */
    protected static $translator;

    public static function setUpBeforeClass()
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

    /**
     * @expectedException \Symfony\Component\Filesystem\Exception\FileNotFoundException
     * @expectedExceptionMessage File not_there.xlf not Found!
     */
    public function testDiffWithMissingSourceFile()
    {
        $diffService = new Diff(static::$translator);
        $diffService->setSourceFilePathName(__DIR__.'/files/not_there.xlf')
            ->setTranslationFilePathName(__DIR__.'/files/file_with_missing_trans.de.xlf')
            ->diff();
    }

    /**
     * @expectedException \Symfony\Component\Filesystem\Exception\FileNotFoundException
     * @expectedExceptionMessage File translation_not_there.xlf not Found!
     */
    public function testDiffWithMissingTranslationFile()
    {
        $diffService = new Diff(static::$translator);
        $diffService->setSourceFilePathName(__DIR__.'/files/source.en.xlf')
            ->setTranslationFilePathName(__DIR__.'/files/translation_not_there.xlf')
            ->diff();
    }

    /**
     * @expectedException \Symfony\Component\Filesystem\Exception\FileNotFoundException
     * @expectedExceptionMessage Datei translation_not_there.xlf nicht gefunden!
     */
    public function testDiffWithMissingTranslationFileInOtherLanguage()
    {
        $translator = clone static::$translator;
        $translator->setLocale('de');
        $diffService = new Diff($translator);
        $diffService->setSourceFilePathName(__DIR__.'/files/source.en.xlf')
            ->setTranslationFilePathName(__DIR__.'/files/translation_not_there.xlf')
            ->diff();
    }
}
