<?php

/**
 * Form for static translation.
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

namespace App\Form;

use App\Helper\Translations;
use App\Service\StaticChoiceLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form for static translation.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class StaticType extends AbstractType
{
    /** @var ParameterBagInterface|null */
    private $params = null;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'container' => null,
            'locale' => 'en',
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setMethod('POST');
        StaticChoiceLoader::setTranslationsDirectory(
            $this->params->get('translations_directory')
        );

        $translationsHelper = new Translations(
            $this->params->get('translations_directory'),
            $options['locale']
        );

        $availableLanguages = $translationsHelper->availableLanguages();

        $builder->add(
            'source_language',
            ChoiceType::class,
            [
                'choices' => $availableLanguages,
                'attr' => [
                    'class' => 'col-sm-8 form-control',
                ],
                'label_attr' => [
                    'class' => 'col-form-label',
                ],
                'data' => 'en',
                'placeholder' => 'labels.choose_source_language',
                'label' => 'labels.source_language',
                'translation_domain' => 'labels',
            ]
        )->add('source_file', ChoiceType::class, [
                'choices' => StaticChoiceLoader::searchSourceFiles(),
                'attr' => [
                    'class' => 'col-sm-8 form-control',
                ],
                'label' => 'labels.source_file',
                'translation_domain' => 'labels',
            ])->add('translation_language', ChoiceType::class, [
                'choices' => $availableLanguages,
                'attr' => [
                    'class' => 'col-sm-8 form-control',
                ],
                'placeholder' => 'labels.choose_translation_language',
                'label' => 'labels.translation_language',
                'translation_domain' => 'labels',
            ]);

        $builder->add('translate', SubmitType::class, [
            'attr' => [
                'style' => 'clear: both; float: left; margin-top: 15px',
            ],
            'label' => 'labels.translate',
            'translation_domain' => 'labels',
        ]);
    }
}
