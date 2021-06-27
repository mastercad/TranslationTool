<?php

/**
 * Form for search.
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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form for search.
 *
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 *
 * @version    Release: @package_version@
 *
 * @since      Class available since Release 1.0.0
 */
class SearchingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('searchToken', TextType::class, [
                'label' => 'labels.search_token',
                'translation_domain' => 'labels',
                'error_bubbling' => true,
                'invalid_message' => 'The token, you entered is not listed!',
            ])
            ->add('searchInTokens', CheckboxType::class, [
                'label' => 'labels.search_in_tokens',
                'translation_domain' => 'labels',
                'attr' => [
                    'value' => Translations::SEARCH_LOCATION_TOKENS,
                    'checked' => 'checked',
                ],
            ])
            ->add('searchInTranslations', CheckboxType::class, [
                'label' => 'labels.search_in_translations',
                'translation_domain' => 'labels',
                'attr' => [
                    'value' => Translations::SEARCH_LOCATION_TRANSLATIONS,
                ],
            ])
            ->add('searchType', ChoiceType::class, [
                'label' => 'labels.search_type',
                'translation_domain' => 'labels',
                'attr' => [
                    'class' => 'form-control',
                ],
                'choices' => [
                    'labels.search_type_begin' => Translations::SEARCH_TYPE_BEGIN,
                    'labels.search_type_end' => Translations::SEARCH_TYPE_END,
                    'labels.search_type_any' => Translations::SEARCH_TYPE_ANY,
                ],
            ]);
    }
}
