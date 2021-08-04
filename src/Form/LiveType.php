<?php

/**
 * Form for live translation.
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

use App\Entity\Live as LiveEntity;
use App\Helper\Translations;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form for live translation.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class LiveType extends AbstractType implements EventSubscriberInterface
{
    /** @var ParameterBagInterface|null */
    private $params = null;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationsHelper = new Translations(
            $this->params->get('translations_directory'),
            $options['locale']
        );

        $availableLanguages = $translationsHelper->availableLanguages();

        $builder
            ->add('sourceFile', FileType::class, [
                'label' => 'labels.source_xliff_file',
                'attr' => [
                    'class' => 'form-control',
                ],
                'translation_domain' => 'labels',
            ])
            ->add('translationFile', FileType::class, [
                'label' => 'labels.translation_xliff_file',
                'attr' => [
                    'class' => 'form-control',
                ],
                'translation_domain' => 'labels',
                'required' => false,
            ])
            ->add('translationLanguage', ChoiceType::class, [
                'choices' => $availableLanguages,
                'label' => 'labels.translation_language',
                'attr' => [
                    'class' => 'form-control',
                ],
                'translation_domain' => 'labels',
                'required' => false,
            ])
            ->add('translate', SubmitType::class)
        ; 
        // telling the form builder about the new event subscriber
        $builder->addEventSubscriber($this);
    } 
    
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::SUBMIT => 'ensureTranslationFileOrLanguageIsSubmitted',
        ];
    }
 
    public function ensureTranslationFileOrLanguageIsSubmitted(FormEvent $event)
    {
        /** @var LiveEntity $submittedData*/
        $submittedData = $event->getData();
 
        // just checking for `null` here, but you may want to check for an empty string or something like that
        if (null === $submittedData->getTranslationFile()
            && null === $submittedData->getTranslationLanguage()
        ) {
            throw new TransformationFailedException(
                'error.translation_file_or_translation_language_must_set',
                0, // code
                null, // previous
                'error.translation_file_or_translation_language_must_set', // user message
                ['{{ whatever }}' => 'here'] // message context for the translater
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LiveEntity::class,
            'locale' => 'en',
        ]);
    }
}
