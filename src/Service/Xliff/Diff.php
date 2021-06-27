<?php

/**
 * Service to diff XLIFF files between source and possible translation file.
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

namespace App\Service\Xliff;

use App\Service\ServiceAbstract;
use SimpleXMLElement;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Service to diff source and translation file (if exists).
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class Diff extends ServiceAbstract
{
    /** @var string */
    private $sourceFilePathName = '';

    /** @var string */
    private $translationFilePathName = '';

    /** @var array */
    private $sourceData = [];

    /** @var array */
    private $translationData = [];

    /**
     * @var bool should value with same like source removed?
     */
    private $removeSameContent = false;

    /**
     * count of already translated values.
     *
     * @var int|null
     */
    private $translatedCount;

    /**
     * count of empty values.
     *
     * @var int|null
     */
    private $emptyCount;

    /**
     * count of same values in both content.
     *
     * @var int|null
     */
    private $sameCount;

    /**
     * count of all items in source to translate.
     *
     * @var int|null
     */
    private $itemCount;

    /**
     * iterates sourceFile and persists founds in sourceData, if translationFilePath is readable, content
     * persists in translationData.
     */
    public function diff(): Diff
    {
        $doc = $this->readContentFromFile($this->getSourceFilePathName());
        /*
        var_dump($doc->children()->file->body->{"trans-unit"});
        var_dump(count($doc->file->body->{"trans-unit"}));
        foreach ($doc->file->body->{"trans-unit"} as $unit) {
            var_dump($unit->source);
            foreach ($unit->source[0]->attributes('xml', true) as $name => $attribute) {
                echo $name." => ".$attribute;
            }
            var_dump($unit->target);
            var_dump($unit->target->__toString());
            foreach ($unit->target[0]->attributes('xml', true) as $name => $attribute) {
                echo $name." => ".$attribute;
            }
            var_dump($unit->target->attributes());
        }
        */

        $sourceData = $this->extendDataWithDOM(
            $doc->children()->file->body->{'trans-unit'},
            $this->getSourceData()
        );

        $translationData = $this->getTranslationData();

        if (false !== ($doc = $this->readContentFromFile($this->getTranslationFilePathName()))) {
            $translationElements = $doc->children()->file->body->{'trans-unit'};

            foreach ($translationElements as $translationElement) {
                $translationData = $this->processTranslationElement($translationElement, $sourceData, $translationData);
            }
            $this->setItemCount(count($sourceData));
            $this->setEmptyCount(
                $this->getItemCount() -
                $this->getSameCount() -
                $this->getTranslatedCount()
            );
        }

        return $this->setSourceData($sourceData)
            ->setTranslationData($translationData);
    }

    /**
     * process the given translationElement with the current TranslationData, depends on SourceData.
     */
    private function processTranslationElement(
        SimpleXMLElement $translationElement,
        array &$sourceData,
        array &$translationData
    ): array {
        if (isset($translationElement->attributes()['resname'])) {
            $resName = trim($translationElement->attributes()['resname']);

            $translationData[$resName] = [];
            if (isset($translationElement->attributes()['id'])) {
                $translationData[$resName]['id'] = trim($translationElement->attributes()['id']);
            }
            $translationData[$resName]['source'] = trim($translationElement->source);

            foreach ($translationElement->attributes() as $name => $value) {
                $translationData[$resName]['attributes'][$name] = trim($value);
            }

            $translationTarget = trim($translationElement->target);

            $sourceTarget = '';

            if (isset($sourceData[$resName])) {
                $sourceTarget = $sourceData[$resName]['target'];
            }

            if (false === $this->isRemoveSameContent()
                || (true === $this->isRemoveSameContent()
                    && $translationTarget !== $sourceTarget)
            ) {
                $translationData[$resName]['target'] = $translationTarget;
            }
            $this->considerCurrentTranslation($sourceTarget, $translationTarget);
        }

        return $translationData;
    }

    /**
     * count, if current translation empty, same or different to source translation.
     *
     * @param SimpleXMLElement $sourceTarget
     * @param SimpleXMLElement $translationTarget
     *
     * @return $this
     */
    private function considerCurrentTranslation($sourceTarget, $translationTarget): self
    {
        if (empty($translationTarget)) {
            $this->setEmptyCount($this->getEmptyCount() + 1);
        } elseif ($sourceTarget === $translationTarget) {
            $this->setSameCount($this->getSameCount() + 1);
        } else {
            $this->setTranslatedCount($this->getTranslatedCount() + 1);
        }

        return $this;
    }

    /**
     * extends given array with given element from DOM.
     *
     * @param SimpleXMLElement $sourceElements
     * @param array            $sourceData
     */
    private function extendDataWithDOM($sourceElements, $sourceData): array
    {
        /** @var SimpleXMLElement $sourceElement */
        foreach ($sourceElements as $sourceElement) {
            if (isset($sourceElement->attributes()['resname'])) {
                $resName = trim($sourceElement->attributes()['resname']);
                $sourceData[$resName] = [];
                if (isset($sourceElement->attributes()['id'])) {
                    $sourceData[$resName]['id'] = trim($sourceElement->attributes()['id']);
                }
                $sourceData[$resName]['source'] = trim($sourceElement->source);
                $sourceData[$resName]['target'] = trim($sourceElement->target);
            }
        }

        return $sourceData;
    }

    /**
     * read content from given file, if exists, else return false.
     *
     * @param $filePathName
     *
     * @return SimpleXMLElement
     */
    private function readContentFromFile($filePathName)
    {
        if (is_readable($filePathName)) {
            return @simplexml_load_file($filePathName, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
        $exceptionMessage = $this->getTranslator()->trans(
            'errors.file_not_found',
            ['%filename%' => basename($filePathName)],
            'errors'
        );
        throw new FileNotFoundException($exceptionMessage);
    }

    public function getSourceFilePathName(): string
    {
        return $this->sourceFilePathName;
    }

    public function setSourceFilePathName(string $sourceFilePathName): Diff
    {
        $this->sourceFilePathName = $sourceFilePathName;

        return $this;
    }

    public function getTranslationFilePathName(): string
    {
        return $this->translationFilePathName;
    }

    public function setTranslationFilePathName(string $translationFilePathName): Diff
    {
        $this->translationFilePathName = $translationFilePathName;

        return $this;
    }

    public function getSourceData(): array
    {
        return $this->sourceData;
    }

    public function setSourceData(array $sourceData): Diff
    {
        $this->sourceData = $sourceData;

        return $this;
    }

    public function getTranslationData(): array
    {
        return $this->translationData;
    }

    public function setTranslationData(array $translationData): Diff
    {
        $this->translationData = $translationData;

        return $this;
    }

    public function isRemoveSameContent(): bool
    {
        return $this->removeSameContent;
    }

    public function setRemoveSameContent(bool $removeSameContent): void
    {
        $this->removeSameContent = $removeSameContent;
    }

    public function getTranslatedCount(): ?int
    {
        return $this->translatedCount;
    }

    /**
     * @param int|null $translatedCount
     */
    public function setTranslatedCount($translatedCount): void
    {
        $this->translatedCount = $translatedCount;
    }

    public function getEmptyCount(): ?int
    {
        return $this->emptyCount;
    }

    /**
     * @param int|null $emptyCount
     */
    public function setEmptyCount($emptyCount): void
    {
        $this->emptyCount = $emptyCount;
    }

    public function getSameCount(): ?int
    {
        return $this->sameCount;
    }

    /**
     * @param int|null $sameCount
     */
    public function setSameCount($sameCount): void
    {
        $this->sameCount = $sameCount;
    }

    public function getItemCount(): ?int
    {
        return $this->itemCount;
    }

    /**
     * @param int|null $itemCount
     */
    public function setItemCount($itemCount): void
    {
        $this->itemCount = $itemCount;
    }
}
