<?php
/**
 * Default Controller tests.
 *
 * PHP version 7
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @package    App\Test\Controller
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    GIT: $Id$
 * @link       http://pear.php.net/package/PackageName
 * @since      File available since Release 1.0.0
 */
namespace Tests\Controller;

use App\Service\File;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Default controller tests.
 *
 * @package    App\Test\Controller
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @since      Class available since Release 1.0.0
 */
class DefaultControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Translation Tool', $crawler->filter('.row h1')->text());
    }

    public function testLiveAction(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/live');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertNotEmpty($crawler->filter('input#live_sourceFile'));
        $this->assertNotEmpty($crawler->filter('input#live_translationFile'));
        $this->assertNotEmpty($crawler->filter('select#live_translationLanguage'));
    }

    public function testLiveActionWithSelection(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/live');
        $sourceFile = new UploadedFile(
            \dirname(__DIR__).'/Service/Xliff/files/source.en.xlf',
            'source.en.xlf',
            'text/plain',
            9988
        );
        $translationFile = new UploadedFile(
            \dirname(__DIR__).'/Service/Xliff/files/source.de.xlf',
            'source.de.xlf',
            'text/plain',
            9988
        );

        $uploadDirectory = $client->getKernel()->getContainer()->getParameter('upload_directory');

        $form = $crawler->filter('button#live_translate')->form();
        $form['live[sourceFile]']->upload($sourceFile);
        $form['live[translationFile]']->upload($translationFile);
        $crawler = $client->submit($form);

        $redirectInformation = $this->extractInformationFromRedirecting($crawler->text());

        $this->assertNotEmpty($redirectInformation);
        $this->assertEquals('source.de.xlf', $redirectInformation['exportFileName']);

        $this->assertFileExists($uploadDirectory.'/'.$redirectInformation['from']);
        $this->assertFileExists($uploadDirectory.'/'.$redirectInformation['to']);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertContains(
            'Redirecting to /en/translate-overview?from',
            $crawler->text()
        );
    }

    public function testLiveActionWithoutSelectionFail(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/live');
        $uploadFile = new UploadedFile(
            \dirname(__DIR__).'/Service/Xliff/files/source.en.xlf',
            'source.en.xlf',
            'text/plain',
            9988
        );

        $form = $crawler->filter('button#live_translate')->form();
        $form['live[sourceFile]']->upload($uploadFile);
        $crawler = $client->submit($form);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertNotEmpty($crawler->filter('input#live_sourceFile'));
        $this->assertNotEmpty($crawler->filter('input#live_translationFile'));
        $this->assertNotEmpty($crawler->filter('select#live_translationLanguage'));
        $this->assertContains(
            'Please select a TranslationFile or Select a TranslationLanguage!',
            $crawler->filter('.alert-danger')->text()
        );
        $this->assertFalse($client->getResponse()->isRedirect('/en/translate-overview'));
    }

    public function testLiveActionWithoutSelectionInGermanFail(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/de/live');
        $uploadFile = new UploadedFile(
            \dirname(__DIR__).'/Service/Xliff/files/source.en.xlf',
            'source.en.xlf',
            'text/plain',
            9988
        );

        $form = $crawler->filter('button#live_translate')->form();
        $form['live[sourceFile]']->upload($uploadFile);
        $crawler = $client->submit($form);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertNotEmpty($crawler->filter('input#live_sourceFile'));
        $this->assertNotEmpty($crawler->filter('input#live_translationFile'));
        $this->assertNotEmpty($crawler->filter('select#live_translationLanguage'));
        $this->assertContains(
            'Bitte wählen sie eine Übersetzungsdatei oder eine Sprache, in die übersetzt werden soll, aus!',
            $crawler->filter('.alert-danger')->text()
        );
        $this->assertFalse($client->getResponse()->isRedirect('/en/translate-overview'));
    }

    public function testImportAction(): void
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            File::cleanDir($translationsDirectory);
        }

        $crawler = $client->request('GET', '/en/import');
        $uploadFile = new UploadedFile(
            dirname(__DIR__).'/Service/Xliff/files/files.zip',
            'files.zip',
            'application/zip',
            9988
        );

        $form = $crawler->filter('button#import_upload')->form();
        $form['import[translation_package]']->upload($uploadFile);
        $crawler = $client->submit($form);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $files = [];
        foreach (new \DirectoryIterator($translationsDirectory) as $file) {
            $files[] = $file->getFilename();
        }

        $expectations = [
            0 => '.',
            1 => '..',
            2 => 'source.en.xlf',
            3 => 'languages.json',
            4 => 'file_with_missing_and_empty_trans.de.xlf',
            5 => 'tokens.json',
            6 => 'file_with_missing_trans.de.xlf',
        ];

        $result = array_diff(array_flip($expectations), array_flip($files));
        $this->assertSame(0, count($result));
        $this->assertContains('Translation file import successfully!', $crawler->text());
        $this->assertFalse($client->getResponse()->isRedirect('/en/translate-overview'));
    }

    public function testImportActionWithInvalidFile(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/import');
        $uploadFile = new UploadedFile(
            dirname(__DIR__).'/Service/Xliff/files/source.en.xlf',
            'source.en.xlf',
            'text/plain',
            9988
        );

        $form = $crawler->filter('button#import_upload')->form();
        $form['import[translation_package]']->upload($uploadFile);
        $crawler = $client->submit($form);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains(
            'The mime type of the file is invalid ("text/xml"). Allowed mime types are "application/zip", "zip".',
            $crawler->filter('.alert-danger')->text()
        );
        $this->assertFalse($client->getResponse()->isRedirect('/en/translate-overview'));
    }

    public function testStaticActionWithoutSelection(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/static');

        $form = $crawler->filter('button#static_translate')->form();
        $crawler = $client->submit($form);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains(
            'Please select Source Language, Translation Language and the File what you want to Translate!',
            $crawler->filter('.alert-danger')->text()
        );
        $this->assertFalse($client->getResponse()->isRedirect('/en/translate-overview'));
    }

    public function testStaticAction(): void
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            $this->assertTrue(File::cleanDir($translationsDirectory));
        }

        copy(
            __DIR__.'/../Service/Xliff/files/source.en.xlf',
            $translationsDirectory.'/source.en.xlf'
        );
        copy(
            __DIR__.'/../Service/Xliff/files/source.en.xlf',
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

        $crawler = $client->request('GET', '/en/static');
        $form = $crawler->filter('button#static_translate')->form([
            'static[source_language]' => 'en',
            'static[translation_language]' => 'de',
            'static[source_file]' => 'source.en.xlf',
        ]);

        $crawler = $client->submit($form);
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $redirectInformation = $this->extractInformationFromRedirecting($crawler->text());

        $this->assertEquals('source.en.xlf', $redirectInformation['from']);
        $this->assertEquals('source.de.xlf', $redirectInformation['to']);
        $this->assertEquals('de', $redirectInformation['lang']);
        $this->assertEquals('source.de.xlf', $redirectInformation['exportFileName']);
        $this->assertEquals('stats', $redirectInformation['action']);

        $this->assertContains(
            'Redirecting to /en/translate-overview?from=source.en.xlf&exportFileName=source.de.xlf&to=source.de.xlf&lang=de&sourceLanguage=en&action=stats',
            $crawler->text()
        );
        $this->assertTrue(
            $client->getResponse()->isRedirect('/en/translate-overview?from=source.en.xlf&exportFileName=source.de.xlf&to=source.de.xlf&lang=de&sourceLanguage=en&action=stats')
        );
    }

    public function testTranslateOverviewAction(): void
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            $this->assertTrue(File::cleanDir($translationsDirectory));
        }

        copy(
            __DIR__.'/../Service/Xliff/files/source.en.xlf',
            $translationsDirectory.'/source.en.xlf'
        );
        copy(
            __DIR__.'/../Service/Xliff/files/source.en.xlf',
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
        $client->request(
            'GET',
            '/en/translate-overview?from=source.en.xlf&exportFileName=source.de.xlf&to=source.de.xlf&lang=de&sourceLanguage=en&&action=stats'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testTranslateOverviewActionWithMissingFile(): void
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            $this->assertTrue(File::cleanDir($translationsDirectory));
        }

        copy(
            __DIR__.'/../Service/Xliff/files/source.en.xlf',
            $translationsDirectory.'/source.en.xlf'
        );
        copy(
            __DIR__.'/../Service/Xliff/files/source.en.xlf',
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
        $crawler = $client->request(
            'GET',
            '/en/translate-overview?from=das_ist_ein_test.test&exportFileName=source.de.xlf&to=source.de.xlf&lang=de&sourceLanguage=en&action=stats'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('File das_ist_ein_test.test not Found!', $crawler->text());
    }

    public function testTranslateOverviewActionFromUpload(): void
    {
        $client = static::createClient();
        $uploadDirectory = $client->getKernel()->getContainer()->getParameter('upload_directory');

        if (file_exists($uploadDirectory)) {
            $this->assertTrue(File::cleanDir($uploadDirectory));
        }

        copy(
            __DIR__.'/../Service/Xliff/files/source.en.xlf',
            $uploadDirectory.'/source_upload.en.xlf'
        );
        copy(
            __DIR__.'/../Service/Xliff/files/source.de.xlf',
            $uploadDirectory.'/source_upload.de.xlf'
        );
        copy(
            __DIR__.'/../Service/Xliff/files/file_with_missing_trans.de.xlf',
            $uploadDirectory.'/file_with_missing_trans.de.xlf'
        );
        copy(
            __DIR__.'/../Service/Xliff/files/file_with_missing_and_empty_trans.de.xlf',
            $uploadDirectory.'/file_with_missing_and_empty_trans.de.xlf'
        );
        $crawler = $client->request(
            'GET',
            '/en/translate-overview?from=source_upload.en.xlf&exportFileName=source_upload.de.xlf&to=source_upload.de.xlf&sourceLanguage=en&lang=de'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(6, $crawler->filterXPath("//input[contains(@class, 'translation')]"));
        $this->assertCount(4, $crawler->filterXPath("//input[contains(@class, 'translation-empty')]"));
        $this->assertCount(3, $crawler->filterXPath("//input[contains(@class, 'translation-same')]"));
    }

    public function testExportWithoutOverwrite(): void
    {
        $client = static::createClient();
        $currentDate = date('Y-m-d');

        $attributes = [
            'created' => $currentDate,
            'creator' => 1,
        ];
        $client->request('GET', '/en/export', [
                'form' => [
                    'translation_language' => 'de',
                    'key1' => [
                        'translation' => 'value1',
                        'attributes' => json_encode($attributes),
                    ],
                    'key2' => [
                        'translation' => 'was vor dem test tag<tag>testeintrag für den test des escapings</tag>bisschen was dahinter!',
                        'attributes' => json_encode($attributes),
                    ],
                    'overwrite_translation_file' => false,
                ],
            ]
        );

        $extraDataString = base64_encode(json_encode($attributes));

        $expectation = '<?xml version="1.0"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file datatype="plaintext" original="file.ext" source-language="en" target-language="de">
    <header>
      <tool tool-company="byte-artist" tool-id="byte-artist-xliff-translation-tool" tool-name="byte-artist Xliff Translation Tool"/>
    </header>
    <body>
      <trans-unit extradata="'.$extraDataString.'" id="c2add694bf942dc77b376592d9c862cd" resname="key1">
        <source>key1</source>
        <target>value1</target>
      </trans-unit>
      <trans-unit extradata="'.$extraDataString.'" id="78f825aaa0103319aaa1a30bf4fe3ada" resname="key2">
        <source>key2</source>
        <target>was vor dem test tag&lt;tag&gt;testeintrag f&#xfc;r den test des escapings&lt;/tag&gt;bisschen was dahinter!</target>
      </trans-unit>
    </body>
  </file>
</xliff>
';
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertXmlStringEqualsXmlString($expectation, $client->getResponse()->getContent());
    }

    public function testExportWithOverwrite(): void
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            $this->assertTrue(File::cleanDir($translationsDirectory));
        }

        $currentDate = date('Y-m-d H:i:s');
        $attributes = ['created' => $currentDate, 'creator' => 1];

        $client->request('GET', '/en/export', [
                'form' => [
                    'export_file_name' => 'test_export.de.xlf',
                    'translation_language' => 'de',
                    'key1' => [
                        'translation' => 'value1',
                        'attributes' => json_encode($attributes),
                    ],
                    'key2' => [
                        'translation' => 'was vor dem test tag<tag>testeintrag für den test des escapings</tag>bisschen was dahinter!',
                        'attributes' => json_encode($attributes),
                    ],
                    'overwrite_translation_file' => true,
                ],
            ]
        );
        $extraDataString = base64_encode(json_encode($attributes));

        $expectation = '<?xml version="1.0"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file datatype="plaintext" original="file.ext" source-language="en" target-language="de">
    <header>
      <tool tool-company="byte-artist" tool-id="byte-artist-xliff-translation-tool" tool-name="byte-artist Xliff Translation Tool"/>
    </header>
    <body>
      <trans-unit extradata="'.$extraDataString.'" id="c2add694bf942dc77b376592d9c862cd" resname="key1">
        <source>key1</source>
        <target>value1</target>
      </trans-unit>
      <trans-unit extradata="'.$extraDataString.'" id="78f825aaa0103319aaa1a30bf4fe3ada" resname="key2">
        <source>key2</source>
        <target>was vor dem test tag&lt;tag&gt;testeintrag f&#xfc;r den test des escapings&lt;/tag&gt;bisschen was dahinter!</target>
      </trans-unit>
    </body>
  </file>
</xliff>';

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Translation saved', $client->getResponse()->getContent());
        $this->assertTrue(is_readable($translationsDirectory.'/test_export.de.xlf'));
        $this->assertXmlStringEqualsXmlString($expectation, file_get_contents($translationsDirectory.'/test_export.de.xlf'));
    }

    public function testExportTranslationsAction()
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            $this->assertTrue(File::cleanDir($translationsDirectory));
        }

        copy(
            __DIR__.'/../Service/Xliff/files/source.en.xlf',
            $translationsDirectory.'/source.en.xlf'
        );
        copy(
            __DIR__.'/../Service/Xliff/files/source.en.xlf',
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
        $crawler = $client->request(
            'GET',
            '/en/translate-overview?from=das_ist_ein_test.test&exportFileName=source.de.xlf&to=source.de.xlf&sourceLanguage=en&lang=de&action=stats'
        );

        $client->request('GET', '/en/export-translations');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/zip', $client->getResponse()->headers->get('content-type'));
        $this->assertEquals('attachment; filename=translations.zip', $client->getResponse()->headers->get('content-disposition'));
    }

    public function testExportWithoutFiles()
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            $this->assertTrue(File::cleanDir($translationsDirectory));
        }
        $client->request('GET', '/en/export-translations');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains(
            'Please import first Translation File Archive to use this Functionality!',
            $client->getResponse()->getContent()
        );
    }

    public function testClearTranslationsActionWithFiles()
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            $this->assertTrue(File::cleanDir($translationsDirectory));
        }

        copy(
            __DIR__.'/../Service/Xliff/files/source.en.xlf',
            $translationsDirectory.'/source.en.xlf'
        );
        copy(
            __DIR__.'/../Service/Xliff/files/source.en.xlf',
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

        $client->request('GET', '/en/clear-translations');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertFalse(file_exists($translationsDirectory));
        $this->assertContains('Translations successfully deleted', $client->getResponse()->getContent());
    }

    public function testClearTranslationsActionWithoutFiles()
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            $this->assertTrue(File::cleanDir($translationsDirectory));
        }

        $client->request('GET', '/en/clear-translations');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertFalse(file_exists($translationsDirectory));
        $this->assertContains('Translations successfully deleted', $client->getResponse()->getContent());
    }

    public function testClearTranslationsActionWithoutDirectory()
    {
        $client = static::createClient();
        $translationsDirectory = $client->getKernel()->getContainer()->getParameter('translations_directory');

        if (file_exists($translationsDirectory)) {
            $this->assertTrue(File::cleanDir($translationsDirectory));
        }

        $client->request('GET', '/en/clear-translations');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertFalse(file_exists($translationsDirectory));
        $this->assertContains('Translations successfully deleted', $client->getResponse()->getContent());
    }

    /**
     * extract redirecting information from string, using named groups.
     *
     * @param string $content
     *
     * @return array
     */
    private function extractInformationFromRedirecting($content)
    {
        $regEx = 'Redirecting to .*?'.
            '\?from=(?P<from>[\.a-z0-9]+)'.
            '\&exportFileName=(?P<exportFileName>[\.a-z0-9]+)'.
            '(?:\&to=(?P<to>[\.a-z0-9]+))?'.
            '(?:\&lang=(?P<lang>[a-z]+))?'.
            '(?:\&sourceLanguage=(?P<sourceLanguage>[a-z]+))?'.
            '(?:\&action=(?P<action>[a-z\-]+))?';

        if (preg_match('/'.$regEx.'/', $content, $matches)) {
            return $matches;
        }

        return [];
    }
}
