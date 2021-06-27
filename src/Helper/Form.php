<?php

/**
 * Form Helper class.
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

use App\Service\Xliff\Diff;
use function count;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;

/**
 * Helper for translation form.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class Form
{
    /**
     * adds given data to given formBuilder.
     *
     * @param $currentKey
     * @param $sourceValue
     * @param $translationValue
     * @param $emptyClass
     * @param array $attributes
     * @param bool  $sourceAndTargetSame
     */
    public static function addTranslationEntryToForm(
        FormBuilder $formBuilder,
        $currentKey,
        $sourceValue,
        $translationValue = '',
        $emptyClass = '',
        $attributes = [],
        $sourceAndTargetSame = true
    ): FormBuilder {
        $elementGroup = $formBuilder->create(
            $currentKey,
            FormType::class,
            [
                'inherit_data' => true,
                'required' => false,
                'label_attr' => [
                    'class' => 'col-form-label',
                    'for' => 'form_'.$currentKey.'_translation',
                ],
            ]
        );

        if (false === $sourceAndTargetSame) {
            $elementGroup->add(
                'source',
                TextType::class,
                [
                    'required' => false,
                    'data' => $sourceValue,
                    'disabled' => true,
                    'label_attr' => [
                        'class' => 'col-form-label',
                        'style' => 'display: none;',
                    ],
                    'attr' => [
                        'readonly' => 'readonly',
                    ],
                ]
            );
        } else {
            $elementGroup->add(
                'source',
                HiddenType::class,
                [
                    'required' => false,
                    'data' => $sourceValue,
                ]
            );
        }
        $elementGroup->add(
            'translation',
            TextType::class,
            [
                'required' => false,
                'data' => $translationValue,
                'label_attr' => [
                    'class' => 'col-form-label',
                    'style' => 'display: none;',
                ],
                'attr' => [
                    'class' => $emptyClass.' translation',
                    'data-orig' => $translationValue,
                ],
            ]
        )->add(
            'translation_orig',
            HiddenType::class,
            [
                'data' => $translationValue,
            ]
        )->add(
            'attributes',
            HiddenType::class,
            [
                'data' => json_encode($attributes),
            ]
        );

        $formBuilder->add($elementGroup);

        return $formBuilder;
    }

    /**
     * extends given form builder with given translation and source data from given diff service.
     */
    public static function extendFormWithSourceAndTranslationData(
        Diff $diffService,
        FormBuilder $formBuilder
    ): FormBuilder {
        $sourceData = $diffService->getSourceData();
        $translationData = $diffService->getTranslationData();

        foreach ($sourceData as $currentKey => $element) {
            $sourceValue = $element['target'];
            $translationValue = null;
            $emptyClass = 'translation-empty';

            $attributes = null;

            // both values exists and are different == key translated
            if (isset($translationData[$currentKey]['target'])
                && $translationData[$currentKey]['target'] !== $sourceData[$currentKey]['target']
            ) {
                $emptyClass = '';
                $attributes = $translationData[$currentKey]['attributes'];
                $translationValue = $translationData[$currentKey]['target'];
                unset($translationData[$currentKey]);
            } elseif (isset($translationData[$currentKey]['target'])) {
                if ($translationData[$currentKey]['target'] === $sourceData[$currentKey]['target']) {
                    $emptyClass .= ' translation-same';
                }
                $attributes = $translationData[$currentKey]['attributes'];
                $translationValue = $translationData[$currentKey]['target'];
                unset($translationData[$currentKey]);
            }

            Form::addTranslationEntryToForm(
                $formBuilder,
                $currentKey,
                $sourceValue,
                $translationValue,
                $emptyClass,
                $attributes,
                $diffService->getSourceFilePathName() === $diffService->getTranslationFilePathName()
            );
        }

        if (0 < count($translationData)) {
            foreach ($translationData as $currentKey => $element) {
                $translationValue = $element['target'];
                $attributes = $element['attributes'];
                Form::addTranslationEntryToForm(
                    $formBuilder,
                    $currentKey,
                    'NOT IN SOURCE FILE!',
                    $translationValue,
                    '',
                    $attributes,
                    $diffService->getSourceFilePathName() === $diffService->getTranslationFilePathName()
                );
            }
        }

        return $formBuilder;
    }
}
