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
 * @package    App\Form
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    GIT: $Id$
 * @link       http://pear.php.net/package/PackageName
 * @since      File available since Release 1.0.0
 */
namespace App\Form;

use App\Entity\Live as LiveEntity;
use App\Helper\Translations;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form for live translation.
 *
 * @package    App\Form
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @since      Class available since Release 1.0.0
 */
class LiveType extends AbstractType
{
    /** @var ParameterBagInterface|null */
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
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LiveEntity::class,
            'locale' => 'en',
        ]);
    }
}
