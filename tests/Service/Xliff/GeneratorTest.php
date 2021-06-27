<?php

/**
 * Tests for generator service.
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

use App\Service\Xliff\Generator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for generator service.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class GeneratorTest extends TestCase
{
    /**
     * test, if valid xml document is generated.
     */
    public function testGenerateXML()
    {
        $generatorService = new Generator();
        $generatorService
            ->setSourceFileName('file.ext')
            ->setApplyHashFromTokenAsId(true)
            ->setApplyTokenAsResName(true)
            ->setTranslationLanguage('en')
            ->addTransUnit('key1', ['translation' => 'value1'])
            ->addTransUnit(
                'key2',
                [
                    'translation' => 'was vor dem test tag<tag>testeintrag für den test des escapings</tag>bisschen '.
                        'was dahinter!',
                ]
            )->finishContent();

        $expectation = '<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
    <file source-language="en" target-language="en" datatype="plaintext" original="file.ext">
        <header>
            <tool tool-company="byte-artist" tool-id="byte-artist-xliff-translation-tool" tool-name="byte-artist '.
                'Xliff Translation Tool"/>
        </header>
        <body>
            <trans-unit id="c2add694bf942dc77b376592d9c862cd" resname="key1">
                <source>key1</source>
                <target>value1</target>
            </trans-unit>
            <trans-unit id="78f825aaa0103319aaa1a30bf4fe3ada" resname="key2">
                <source>key2</source>
                <target><![CDATA[was vor dem test tag<tag>testeintrag für den test des escapings</tag>bisschen '.
                    'was dahinter!]]></target>
            </trans-unit>
       </body>
    </file>
</xliff>';

        $this->assertXmlStringEqualsXmlString($expectation, $generatorService->getContent());
    }

    /**
     * test, if empty document is also generated.
     */
    public function testGenerateXmlWithEmptyContent()
    {
        $generatorService = new Generator();
        $generatorService
            ->setSourceFileName('file.ext')
            ->finishContent();

        $expectation = '<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
    <file source-language="en" target-language="" datatype="plaintext" original="file.ext">
        <header>
            <tool tool-company="byte-artist" tool-id="byte-artist-xliff-translation-tool" tool-name="byte-artist '.
                'Xliff Translation Tool"/>
        </header>
        <body/>
    </file>
</xliff>';

        $this->assertXmlStringEqualsXmlString($expectation, $generatorService->getContent());
    }
}
