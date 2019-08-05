<?php
/**
 * Generator for creating and extending XLIFF files from array.
 *
 * PHP version 7
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @package    App\Service\Xliff
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    GIT: $Id$
 * @link       http://pear.php.net/package/PackageName
 * @since      File available since Release 1.0.0
 */
namespace App\Service\Xliff;

use DOMDocument;
use DOMElement;
use function is_array;

/**
 * Service to diff source and translation file (if exists).
 *
 * @package    App\Service\Xliff
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @since      Class available since Release 1.0.0
 */
class Generator
{
    /**
     * @var array
     */
    private $validAttributes = [
        'id',
        'resname',
//        'created',
//        'creator',
//        'modified',
//        'modifier',
    ];

    private $extraAttributes = [
        'creator' => 'batt',
        'created' => 'batt',
        'modifier' => 'batt',
        'modified' => 'batt',
    ];

    /**
     * @var string
     */
    private $header = '';

    /**
     * @var string
     */
    private $content = '';

    /**
     * @var string
     */
    private $footer = '';

    /**
     * @var string
     */
    private $translationLanguage = '';

    /**
     * @var DOMDocument|null
     */
    private $xmlDocument;

    /**
     * @var DOMElement|null
     */
    private $xmlRoot;

    /**
     * @var DOMElement|null
     */
    private $xmlHeader;

    /**
     * @var DOMElement|null
     */
    private $xmlBody;

    /**
     * @var DOMElement|null
     */
    private $xmlFile;

    /**
     * @var null
     */
    private $sourceFileName;

    /**
     * Generate MD Hash from token and add as id?
     *
     * @var bool
     */
    private $applyHashFromTokenAsId = false;

    /**
     * apply token redundant as resname.
     *
     * @var bool
     */
    private $applyTokenAsResName = false;

    /**
     * add TransUnit entry to content.
     *
     * @param string $key
     * @param array  $value
     *
     * @return Generator
     */
    public function addTransUnit($key, $value): Generator
    {
        $transUnit = $this->useXmlDocument()->createElement('trans-unit');

        $transUnit = $this->extendAttributes($transUnit, $value);

        if (true === $this->isApplyHashFromTokenAsId()) {
            $transUnit->setAttribute('id', md5($key));
        }

        if (true === $this->isApplyTokenAsResName()) {
            $transUnit->setAttribute('resname', $key);
        }

        $transUnit->appendChild($this->createElement('source', $key));
        $transUnit->appendChild($this->createElement('target', $value['translation']));

        $this->useXmlBody()->appendChild($transUnit);

        return $this;
    }

    /**
     * Creates Element with given tag name and value.
     *
     * @param string $tagName
     * @param string $value
     *
     * @return DOMElement
     */
    private function createElement($tagName, $value): DOMElement
    {
        $tag = $this->useXmlDocument()->createElement($tagName);
        if ($this->checkCdataRequired($value)) {
            $tag->appendChild($this->useXmlDocument()->createCDATASection($value));
        } else {
            $tag->textContent = $value;
        }

        return $tag;
    }

    /**
     * Checks, if given String needs CDATA element for escaping.
     *
     * @param string $value
     *
     * @return bool
     */
    private function checkCdataRequired($value): bool
    {
        if (preg_match('/[\<|\>|\&]/', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Extends given DOMElement with given attributes and values from $element.
     *
     * @param DOMElement  $transUnit
     * @param array|string $element
     *
     * @return mixed
     */
    private function extendAttributes(DOMElement $transUnit, $element)
    {
        if (!isset($element['attributes'])
            || !is_array($element['attributes'])
        ) {
            return $transUnit;
        }

        if (null !== ($extraAttributes = $this->prepareExtraAttributes($element['attributes']))) {
            $attributesString = base64_encode(json_encode($extraAttributes));
            $transUnit->setAttribute('extradata', $attributesString);
        }

        foreach ($element['attributes'] as $attribute => $attributeValue) {
            if (in_array($attribute, $this->validAttributes)) {
//                $attribute = $this->extendAttributeWithNamespace($attribute);
                $transUnit->setAttribute($attribute, $attributeValue);
            }
        }

        return $transUnit;
    }

    /**
     * Returns given attributes filtered by extra attributes.
     *
     * @param array $attributes
     *
     * @return array|null
     */
    private function prepareExtraAttributes($attributes)
    {
        if (is_array($attributes)) {
            $attributes = array_diff_key($attributes, array_flip($this->validAttributes));

            return 0 < count($attributes) ? $attributes : null;
        }

        return null;
    }

    /**
     * Extends given Attribute with possible Namespace.
     *
     * @param string $attribute
     *
     * @return string
     */
    private function extendAttributeWithNamespace($attribute): string
    {
        if (array_key_exists($attribute, $this->extraAttributes)) {
            return $this->extraAttributes[$attribute].':'.$attribute;
        }

        return $attribute;
    }

    /**
     * Replace needed Key in DOM Element with given value.
     *
     * @param string $key
     * @param string $value
     *
     * @return bool
     */
    public function replaceTransUnit($key, $value): bool
    {
//        $transUnit = $this->useXmlDocument()->;
        return false;
    }

    /**
     * finalize prepared content for xml content output.
     *
     * @return Generator
     */
    public function finishContent(): Generator
    {
        $this->useXmlFile()->appendChild($this->useXmlHeader());
        $this->useXmlFile()->appendChild($this->useXmlBody());
        $this->useXmlRoot()->appendChild($this->useXmlFile());
        $this->useXmlDocument()->appendChild($this->useXmlRoot());

        $this->useXmlDocument()->preserveWhiteSpace = false;
        $this->useXmlDocument()->formatOutput = true;

        return $this->setContent($this->useXmlDocument()->saveXML());
    }

    /**
     * @return string
     */
    public function getHeader(): string
    {
        return $this->header;
    }

    /**
     * @param string $header
     *
     * @return Generator
     */
    public function setHeader(string $header): Generator
    {
        $this->header = $header;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return Generator
     */
    public function setContent($content): Generator
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getFooter(): string
    {
        return $this->footer;
    }

    /**
     * @param string $footer
     *
     * @return Generator
     */
    public function setFooter(string $footer): Generator
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * @return string
     */
    public function getTranslationLanguage(): string
    {
        return $this->translationLanguage;
    }

    /**
     * @param string $translationLanguage
     *
     * @return Generator
     */
    public function setTranslationLanguage(string $translationLanguage): Generator
    {
        $this->translationLanguage = $translationLanguage;

        return $this;
    }

    /**
     * @return DOMDocument|null
     */
    public function getXmlDocument(): ?DOMDocument
    {
        return $this->xmlDocument;
    }

    /**
     * @param DOMDocument|null $xmlDocument
     *
     * @return Generator
     */
    public function setXmlDocument(DOMDocument $xmlDocument): Generator
    {
        $this->xmlDocument = $xmlDocument;

        return $this;
    }

    /**
     * @return DOMDocument|null
     */
    public function useXmlDocument(): ?DOMDocument
    {
        if (!$this->getXmlDocument() instanceof DOMDocument) {
            $this->setXmlDocument(new DOMDocument('1.0', 'UTF-8'));
        }

        return $this->getXmlDocument();
    }

    /**
     * @return DOMElement|null
     */
    public function getXmlRoot(): ?DOMElement
    {
        return $this->xmlRoot;
    }

    /**
     * @param DOMElement|null $xmlRoot
     *
     * @return Generator
     */
    public function setXmlRoot(DOMElement $xmlRoot): Generator
    {
        $this->xmlRoot = $xmlRoot;

        return $this;
    }

    /**
     * @return DOMElement
     */
    public function useXmlRoot(): DOMElement
    {
        if (!$this->getXmlRoot() instanceof DOMElement) {
            $xmlRoot = $this->useXmlDocument()->createElement('xliff');
            $xmlRoot->setAttribute('xmlns', 'urn:oasis:names:tc:xliff:document:1.2');
//            $xmlRoot->setAttribute('xmlns:batt', 'http://translation-tool.byte-artist.de/xsd');
            $xmlRoot->setAttribute('version', '1.2');
            $this->setXmlRoot($xmlRoot);
        }

        return $this->getXmlRoot();
    }

    /**
     * @return DOMElement|null
     */
    public function getXmlFile(): ?DOMElement
    {
        return $this->xmlFile;
    }

    /**
     * @param DOMElement $xmlFile
     *
     * @return Generator
     */
    public function setXmlFile(DOMElement $xmlFile): Generator
    {
        $this->xmlFile = $xmlFile;

        return $this;
    }

    /**
     * @return DOMElement
     */
    public function useXmlFile(): DOMElement
    {
        if (!$this->getXmlFile() instanceof DOMElement) {
            $xmlFile = $this->useXmlDocument()->createElement('file');
            $xmlFile->setAttribute('source-language', 'en');
            $xmlFile->setAttribute('target-language', $this->getTranslationLanguage());
            $xmlFile->setAttribute('datatype', 'plaintext');

            if (!empty($this->getSourceFileName())) {
                $xmlFile->setAttribute('original', $this->getSourceFileName());
            } else {
                $xmlFile->setAttribute('original', 'file.ext');
            }
            $this->setXmlFile($xmlFile);
        }

        return $this->getXmlFile();
    }

    /**
     * @return DOMElement|null
     */
    public function getXmlHeader(): ?DOMElement
    {
        return $this->xmlHeader;
    }

    /**
     * @param DOMElement $xmlHeader
     *
     * @return Generator
     */
    public function setXmlHeader(DOMElement $xmlHeader): Generator
    {
        $this->xmlHeader = $xmlHeader;

        return $this;
    }

    /**
     * @return DOMElement
     */
    public function useXmlHeader(): DOMElement
    {
        if (!$this->getXmlHeader() instanceof DOMElement) {
            $xmlHeader = $this->useXmlDocument()->createElement('header');
            $toolElement = $this->useXmlDocument()->createElement('tool');
            $toolElement->setAttribute('tool-company', 'byte-artist');
            $toolElement->setAttribute('tool-id', 'byte-artist-xliff-translation-tool');
            $toolElement->setAttribute('tool-name', 'byte-artist Xliff Translation Tool');
            $xmlHeader->appendChild($toolElement);
            $this->setXmlHeader($xmlHeader);
        }

        return $this->getXmlHeader();
    }

    /**
     * @return DOMElement|null
     */
    public function getXmlBody(): ?DOMElement
    {
        return $this->xmlBody;
    }

    /**
     * @param DOMElement $xmlBody
     *
     * @return Generator
     */
    public function setXmlBody(DOMElement $xmlBody): Generator
    {
        $this->xmlBody = $xmlBody;

        return $this;
    }

    /**
     * @return DOMElement
     */
    public function useXmlBody(): ?DOMElement
    {
        if (!$this->getXmlBody() instanceof DOMElement) {
            $xmlBody = $this->useXmlDocument()->createElement('body');
            $this->setXmlBody($xmlBody);
        }

        return $this->getXmlBody();
    }

    public function getSourceFileName()
    {
        return $this->sourceFileName;
    }

    /**
     * @param null $sourceFileName
     *
     * @return Generator
     */
    public function setSourceFileName($sourceFileName): Generator
    {
        $this->sourceFileName = $sourceFileName;

        return $this;
    }

    /**
     * @return bool
     */
    public function isApplyHashFromTokenAsId(): bool
    {
        return $this->applyHashFromTokenAsId;
    }

    /**
     * @param bool $applyHashFromTokenAsId
     *
     * @return Generator
     */
    public function setApplyHashFromTokenAsId(bool $applyHashFromTokenAsId): Generator
    {
        $this->applyHashFromTokenAsId = $applyHashFromTokenAsId;

        return $this;
    }

    /**
     * @return bool
     */
    public function isApplyTokenAsResName(): bool
    {
        return $this->applyTokenAsResName;
    }

    /**
     * @param bool $applyTokenAsResName
     *
     * @return Generator
     */
    public function setApplyTokenAsResName(bool $applyTokenAsResName): Generator
    {
        $this->applyTokenAsResName = $applyTokenAsResName;

        return $this;
    }
}
