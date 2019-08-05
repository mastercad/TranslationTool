<?php
/**
 * Form for translation.
 *
 * PHP version 7
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @package    App\Form
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    GIT: $Id$
 * @link       http://pear.php.net/package/PackageName
 * @since      File available since Release 1.0.0
 */
namespace App\Form;

use App\Helper\Translations;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form for translation.
 *
 * @package    App\Form
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @since      Class available since Release 1.0.0
 */
class TranslationType extends AbstractType
{
    /**
     * @var array
     */
    private $translationData = [];

    /**
     * @var array
     */
    private $sourceData = [];

    /**
     * @var string|null
     */
    private $exportFileName;

    /**
     * @var string|null
     */
    private $translationLanguage;

    /**
     * @var FormBuilder
     */
    private $builder;

    /**
     * @var ParameterBagInterface|null
     */
    private $params = null;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setMethod('POST');
        $this->builder = $builder;

        $translationsHelper = new Translations(
            $this->params->get('translations_directory'),
            $options['locale']
        );

        $builder->add('translation_language', ChoiceType::class, [
            'choices' => $translationsHelper->availableLanguages(),
            'data' => $this->getTranslationLanguage(),
            'attr' => [
                'class' => 'col-sm-8',
            ],
            'label_attr' => [
                'class' => 'col-form-label',
            ],
            'label' => 'labels.translation_language',
            'translation_domain' => 'labels',
        ])->add('export_file_name', HiddenType::class, [
            'data' => $this->exportFileName,
            'label' => 'labels.export_file_name',
            'translation_domain' => 'labels',
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);

        $builder->add('save', SubmitType::class, [
            'attr' => [
                'style' => 'clear: both; float: left;',
            ],
            'label' => 'labels.save',
            'translation_domain' => 'labels',
        ]);
    }

    /**
     * @param FormEvent $event
     */
    public function onPresetData(FormEvent $event): void
    {
        $translationEntity = $event->getData();
        $form = $event->getForm();

        foreach ($translationEntity->getSourceData() as $currentKey => $element) {
            $sourceValue = $element['target'];
            $translationValue = null;
            $emptyClass = 'translation-empty';

            if (isset($translationEntity->translationData[$currentKey]['target'])) {
                $emptyClass = '';

                $translationValue = $translationEntity->translationData[$currentKey]['target'];
            }

            $form->add(
                $this->builder->create(
                    $currentKey,
                    FormType::class,
                    [
                        'auto_initialize' => false,
                        'inherit_data' => true,
                        'required' => false,
                        'label_attr' => [
                            'for' => 'form_'.$currentKey.'_translation',
                        ],
                    ]
                )->getForm()
                ->add(
                    'source',
                    TextareaType::class,
                    [
                        'required' => false,
                        'data' => $sourceValue,
                        'disabled' => true,
                        'label_attr' => [
                            'style' => 'display: none;',
                        ],
                        'attr' => [
                            'readonly' => 'readonly',
                        ],
                    ]
                )
                ->add(
                    'translation',
                    TextType::class,
                    [
                        'required' => false,
                        'data' => $translationValue,
                        'label_attr' => [
                            'style' => 'display: none;',
                        ],
                        'attr' => [
                            'class' => $emptyClass.' translation',
                        ],
                    ]
                )
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'container' => null,
            'locale' => 'en',
        ]);
    }

    /**
     * @return array
     */
    public function getTranslationData(): array
    {
        return $this->translationData;
    }

    /**
     * @param array $translationData
     *
     * @return TranslationType
     */
    public function setTranslationData(array $translationData): TranslationType
    {
        $this->translationData = $translationData;

        return $this;
    }

    /**
     * @return array
     */
    public function getSourceData(): array
    {
        return $this->sourceData;
    }

    /**
     * @param array $sourceData
     *
     * @return TranslationType
     */
    public function setSourceData(array $sourceData): TranslationType
    {
        $this->sourceData = $sourceData;

        return $this;
    }

    public function getExportFileName()
    {
        return $this->exportFileName;
    }

    /**
     * @param null $exportFileName
     *
     * @return TranslationType
     */
    public function setExportFileName($exportFileName): TranslationType
    {
        $this->exportFileName = $exportFileName;

        return $this;
    }

    public function getTranslationLanguage()
    {
        return $this->translationLanguage;
    }

    /**
     * @param null $translationLanguage
     *
     * @return TranslationType
     */
    public function setTranslationLanguage($translationLanguage): TranslationType
    {
        $this->translationLanguage = $translationLanguage;

        return $this;
    }
}
