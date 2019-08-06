<?php
/**
 * Live Controller.
 *
 * This Controller makes live translation possible.
 *
 * PHP version 7
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @package    App\Controller
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    GIT: $Id$
 * @link       http://pear.php.net/package/PackageName
 * @since      File available since Release 1.0.0
 */
namespace App\Controller;

use App\Entity\Live as LiveEntity;
use App\Form\LiveType;
use App\Helper\Extractor;
use App\Service\File;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Live controller.
 *
 * This Controller makes live translations from single file available.
 *
 * @package    App\Controller
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @since      Class available since Release 1.0.0
 */
class LiveController extends AbstractController
{
    /**
     * @Route("/{_locale}/live", name="new-translation", requirements={"_locale" = "en|de"}, defaults={"_locale" = "de"})
     * @Route("/{_locale}/live")
     * @Route("/live/")
     *
     * @param TranslatorInterface $translator
     * @param Request             $request
     *
     * @return Response
     */
    public function indexAction(TranslatorInterface $translator, Request $request): Response
    {
        $this->get('session')->set('_locale', $request->get('_locale'));

        $liveEntity = new LiveEntity();
        $form = $this->createForm(LiveType::class, $liveEntity, [
            'locale' => $request->get('_locale'),
        ]);

        $form->handleRequest($request);
        /** @var UploadedFile $translationFile */
        $translationFile = $liveEntity->getTranslationFile();
        $translationLanguage = $liveEntity->getTranslationLanguage();
        $translationFileName = null;

        $submitted = $form->isSubmitted();

        if (true === $submitted
            && null === $translationFile
            && null === $translationLanguage
        ) {
            $form->addError(new FormError(
                $translated = $translator->trans('errors.nothing_selected', [], 'errors')
            ));
        } elseif (true === $submitted
            && $form->isValid()
        ) {
            /** @var UploadedFile $sourceFile */
            $sourceFile = $liveEntity->getSourceFile();

            $liveEntity->setSourceFileName(
                File::moveUniqueUploadedFile(
                    $sourceFile,
                    $this->getParameter('xliff_directory')
                )
            );

            $fileInformation = explode('.', $sourceFile->getClientOriginalName());
            $baseFileName = $fileInformation[0];
            $extension = isset($fileInformation[2]) ? $fileInformation[2] : $fileInformation[1];
            
            if (null !== $translationFile) {
                $exportFileName = $translationFile->getClientOriginalName();
                $translationFileName = File::moveUniqueUploadedFile(
                    $translationFile,
                    $this->getParameter('xliff_directory')
                );
            } else {
                $exportFileName = $baseFileName.'.'.$translationLanguage.'.'.$extension;
            }
            $liveEntity->setTranslationFileName($translationFileName);

            return $this->redirect(
                $this->generateUrl(
                    'translate-overview',
                    [
                        'from' => $liveEntity->getSourceFileName(),
                        'exportFileName' => $exportFileName,
                        'to' => $translationFileName,
                        'sourceLanguage' => Extractor::extractLanguageFromFileName($sourceFile->getClientOriginalName()),
                        'lang' => $translationLanguage,
                    ]
                )
            );
        }

        return $this->render('live/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
