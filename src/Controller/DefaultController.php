<?php
/**
 * Default Controller.
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

use App\Entity\Import as ImportEntity;
use App\Entity\Live as LiveEntity;
use App\Form\ImportType;
use App\Form\LiveType;
use App\Form\SearchingType;
use App\Form\StaticType;
use App\Helper\Extractor;
use App\Helper\Translations;
use App\Service\File;
use App\Service\Statistic;
use App\Service\Xliff\Diff;
use App\Service\Xliff\Generator;
use function count;
use Exception;
use function is_array;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use function strlen;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipArchive;

/**
 * Default controller.
 *
 * @package    App\Controller
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @since      Class available since Release 1.0.0
 */
class DefaultController extends AbstractController
{
    /**
     * Index page with instructions and welcome content.
     * 
     * @Route("/{_locale}", name="homepage", requirements={"_locale" = "en|de"}, defaults={"_locale" = "de"})
     * @Route("/{_locale}/")
     * @Route("/")
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * @Route("/{_locale}/import", name="import", requirements={"_locale" = "en|de"}, defaults={"_locale" = "de"})
     * @Route("/{_locale}/import")
     * @Route("/import/")
     *
     * @param TranslatorInterface $translator
     * @param Request             $request
     *
     * @return Response
     */
    public function importAction(TranslatorInterface $translator, Request $request): Response
    {
        $importEntity = new ImportEntity();
        $this->get('session')->set('_locale', $request->get('_locale'));

        $form = $this->createForm(ImportType::class, $importEntity, [
            'locale' => $request->get('_locale'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()
            && $form->isValid()
        ) {
            /** @var UploadedFile $translationPackage */
            $translationPackage = $importEntity->getTranslationPackage();
            $extractService = new ZipArchive();
            $extractService->open($translationPackage->getPathname());

            if ($extractService->extractTo($this->getParameter('translations_directory'))) {
                $translationService = new Translations($this->getParameter('translations_directory'));
                $translationService->generateKnownTokenAndLanguagesList();

                return $this->render('default/success.html.twig', [
                    'status_message' => $translator->trans('messages.successfully_imported'),
                ]);
            }
        }

        return $this->render(
            'default/import.html.twig',
            [
                'form' => $form->createView(),
                'import_active' => 'active',
            ]
        );
    }

    /**
     * @Route("/{_locale}/static", name="static", requirements={"_locale" = "en|de"}, defaults={"_locale" = "de"})
     * @Route("/{_locale}/static/")
     * @Route("/static")
     *
     * @param TranslatorInterface $translator
     * @param Request             $request
     *
     * @return Response
     */
    public function staticAction(TranslatorInterface $translator, Request $request): Response
    {
        $translationsDirectory = $this->getParameter('translations_directory');
        if (!is_dir($translationsDirectory)
            && !mkdir($translationsDirectory, 0777, true)
            && !is_dir($translationsDirectory)
        ) {
            return $this->render('default/error.html.twig', [
                'message' => $translator->trans('errors.file_not_found', [$translationsDirectory], 'errors'),
            ]);
        }

        $this->get('session')->set('_locale', $request->get('_locale'));

        $form = $this->createForm(StaticType::class, null, [
            'locale' => $request->get('_locale'),
        ]);

        if (File::isDirEmpty($translationsDirectory, true)) {
            $form->addError(new FormError(
                $translated = $translator->trans('errors.no_static_without_import', [], 'errors')
            ));
        } else {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $params = $request->get('static');
                $sourceFileInformation = explode('.', $params['source_file']);

                $sourceTag = $sourceFileInformation[0];
                $sourceLanguage = $params['source_language'];
                $translationLanguage = $params['translation_language'];
                $sourceFileName = $sourceTag.'.'.$sourceLanguage.'.xlf';
                $translationFileName = $sourceTag.'.'.$translationLanguage.'.xlf';

                if (!empty($sourceLanguage)
                    && !empty($sourceFileName)
                    && !empty($translationLanguage)
                ) {
                    return $this->redirect(
                        $this->generateUrl(
                            'translate-overview',
                            [
                                'from' => $sourceFileName,
                                'exportFileName' => $translationFileName,
                                'to' => $translationFileName,
                                'lang' => $translationLanguage,
                                'sourceLanguage' => $sourceLanguage,
                                'action' => 'stats',
                            ]
                        )
                    );
                }
                $form->addError(new FormError(
                    $translated = $translator
                        ->trans('errors.nothing_selected_for_static', [], 'errors')
                ));
            }
        }

        return $this->render('default/static.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{_locale}/stats", name="stats", requirements={"_locale" = "en|de"}, defaults={"_locale" = "de"})
     * @Route("/{_locale}/stats/")
     * @Route("/stats")
     *
     * @param TranslatorInterface $translator
     * @param Request             $request
     *
     * @return Response
     */
    public function statsAction(TranslatorInterface $translator, Request $request): Response
    {
        $translationsDirectory = $this->getParameter('translations_directory');

        if (!file_exists($translationsDirectory)
            || File::isDirEmpty($translationsDirectory, true)
        ) {
            return $this->render('default/error.html.twig', [
                'message' => $translator->trans('errors.no_static_without_import', [], 'errors'),
            ]);
        }

        $sourceLanguage = $request->get('translation_language', 'en');
        $sourceFiles = $request->get(
            'source_files',
            File::collectTranslationFiles($translationsDirectory, $sourceLanguage)
        );

        $statisticService = new Statistic($translator);
        $statisticService->setSourceLanguage($sourceLanguage)
            ->setSourceFiles($sourceFiles)
            ->setTranslationsDirectory($translationsDirectory);

        return $this->render(
            'default/stats.html.twig',
            [
                'statsCollection' => $statisticService->generate(),
                'sourceLanguage' => $sourceLanguage,
                'stats_active' => 'active',
            ]
        );
    }

    /**
     * @Route("/{_locale}/translate-overview", name="translate-overview", requirements={"_locale" = "en|de"}, defaults={"_locale" = "de"})
     * @Route("/{_locale}/translate-overview/")
     * @Route("/translate-overview")
     *
     * @param TranslatorInterface $translator
     * @param Request             $request
     *
     * @return Response
     */
    public function translateOverviewAction(TranslatorInterface $translator, Request $request): Response
    {
        $sourceFile = $request->get('from');
        $translationFile = $request->get('to');
        $translationLanguage = $request->get('lang');
        $exportFileName = $request->get('exportFileName');
        $requestAction = $request->get('action');
        $sourceLanguage = $request->get('sourceLanguage');
        $overwriteTranslationFile = false;

        if ('stats' === $requestAction) {
            $path = $this->getParameter('translations_directory');
            $overwriteTranslationFile = true;
        } else {
            $path = $this->getParameter('upload_directory');
        }

        try {
            /** @var Form $form */
            $form = $this->generateForm(
                $path.'/'.$sourceFile,
                $path.'/'.$translationFile,
                $translationLanguage,
                $exportFileName,
                $translator,
                $overwriteTranslationFile,
                $request->getLocale()
            );
        } catch (FileNotFoundException $exception) {
            return $this->render('default/error.html.twig', [
                'message' => $exception->getMessage(),
            ]);
        }

        return $this->render('default/translate-overview.html.twig', [
            'form' => $form->createView(),
            'live_active' => 'active',
            'source_language' => $sourceLanguage,
        ]);
    }

    /**
     * @Route("/{_locale}/export", name="export", requirements={"_locale" = "en|de"}, defaults={"_locale" = "de"})
     * @Route("/{_locale}/export/")
     * @Route("/export")
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws Exception
     */
    public function exportAction(Request $request): Response
    {
        $requestParams = $request->get('form');
        $exportFileName = null;
        $translationLanguage = null;

        $generator = new Generator();
        $translationsService = new Translations($this->getParameter('translations_directory'));

        $translationLanguage = isset($requestParams['translation_language']) ?
            $requestParams['translation_language'] : null;

        $exportFileName = isset($requestParams['export_file_name']) ?
            $requestParams['export_file_name'] : null;

        if (null === $translationLanguage
            && null !== $exportFileName
        ) {
            $translationLanguage = Extractor::extractLanguageFromFileName($exportFileName);
        }

        $translationsService->setCurrentLocale($translationLanguage);
        $generator->setTranslationLanguage($translationLanguage);

        foreach ($requestParams as $key => $value) {
            /* if entry hidden export filename field? */
            if (!is_array($value)
                || !isset($value['translation'])
                || 0 >= strlen(trim($value['translation']))
            ) {
                continue;
            }
            $attributes = [];
            if (array_key_exists('attributes', $value)) {
                $attributes = is_array($value['attributes']) ?: json_decode($value['attributes'], true);
            }

            $attributes['id'] = !empty($attributes['id']) ? $attributes['id'] : md5($key);
            $attributes['resname'] = !empty($attributes['resname']) ? $attributes['resname'] : $key;

            if (isset($value['translation_orig'])
                && $value['translation_orig'] !== $value['translation']
            ) {
                $attributes['modified'] = date('Y-m-d H:i:s');
                $attributes['modifier'] = $this->getCurrentUser();
            }

            if (!isset($attributes['created'])) {
                $attributes['created'] = date('Y-m-d H:i:s');
                $attributes['creator'] = $this->getCurrentUser();
            }
            $value['attributes'] = $attributes;
            $translationsService->updateToken($value, $exportFileName);

            $generator->addTransUnit($key, $value);
        }

        $generator->setTranslationLanguage($translationLanguage);
        $translationsService->setCurrentLocale($translationLanguage);

        $generator->finishContent();
        $translationsService->saveTranslations();

        if (true === (bool) $requestParams['overwrite_translation_file']) {
            file_put_contents(
                $this->getParameter('translations_directory').'/'.$exportFileName,
                $generator->getContent()
            );

            return $this->render('default/success.html.twig', [
                'status_message' => 'Translation saved',
            ]);
        }

        $response = new Response($generator->getContent());
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $exportFileName
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * exports all translations as zip archive.
     *
     * @Route("/{_locale}/export-translations", name="export-translations", requirements={"_locale" = "en|de"}, defaults={"_locale" = "de"})
     * @Route("/{_locale}/translations/")
     * @Route("/translations")
     *
     * @param TranslatorInterface $translator
     *
     * @throws FileNotFoundException
     *
     * @return Response
     */
    public function exportTranslationsAction(TranslatorInterface $translator): Response
    {
        $exportFileName = 'translations.zip';
        $translationsDirectory = $this->getParameter('translations_directory');

        if (!file_exists($translationsDirectory)
            || File::isDirEmpty($translationsDirectory, true)
        ) {
            return $this->render('default/error.html.twig', [
                'message' => $translator->trans('errors.no_static_without_import', [], 'errors'),
            ]);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($translationsDirectory)
        );

        $regexFileIterator = new RegexIterator($iterator, '#\.xlf$#i');

        $archive = new ZipArchive();
        $archive->open(
            $translationsDirectory.'/'.
                $exportFileName,
            ZipArchive::CREATE
        );

        /** @var SplFileInfo $fileInfo */
        foreach ($regexFileIterator as $fileInfo) {
            if ('xlf' === $fileInfo->getExtension()
                && is_file($fileInfo->getPathname())
            ) {
                $archive->addFile($fileInfo->getPathname(), $fileInfo->getFilename());
            }
        }
        $archive->close();

        if (!is_readable($translationsDirectory.'/'.$exportFileName)) {
            throw new FileNotFoundException($translator->trans('errors.export_file_not_found', [], 'errors'));
        }

        $zipContent = file_get_contents(
            $translationsDirectory.'/'.$exportFileName
        );
        unlink($translationsDirectory.'/'.$exportFileName);

        $response = new Response($zipContent);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $exportFileName
        );

        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @Route("/{_locale}/search", name="search"), requirements={"_locale" = "en|de"}, defaults={"_locale" = "de"})
     * @Route("/{_locale}/search/")
     * @Route("/search")
     *
     * @param TranslatorInterface $translator
     * @param Request             $request
     *
     * @return Response
     */
    public function searchAction(TranslatorInterface $translator, Request $request): Response
    {
        $this->get('session')->set('_locale', $request->get('_locale'));

        $form = $this->createForm(SearchingType::class, null);

        $translationsDirectory = $this->getParameter('translations_directory');
        if (!file_exists($translationsDirectory)
            || File::isDirEmpty($translationsDirectory, true)
        ) {
            $form->addError(new FormError(
                $translated = $translator->trans('errors.no_static_without_import', [], 'errors')
            ));
        }

        return $this->render(
            'default/search.html.twig',
            [
                'form' => $form->createView(),
                'search_active' => 'active',
            ]
        );
    }

    /**
     * @Route("/{_locale}/get-search-proposals", name="get-search-proposals", requirements={"_locale" = "en|de"}, defaults={"_locale" = "de"})
     * @Route("/{_locale}/get-search-proposals/")
     * @Route("/get-search-proposals")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getSearchProposalsAction(Request $request): JsonResponse
    {
        $searchString = $request->get('searchString');
        $searchLocation = $request->get('searchLocation', Translations::SEARCH_LOCATION_TOKENS);
        $searchType = $request->get('searchType', Translations::SEARCH_TYPE_BEGIN);
        $translationService = new Translations($this->getParameter('translations_directory'));
        $matches = $translationService->searchMatchingTranslations($searchString, $searchLocation, $searchType);

        return new JsonResponse(
            json_encode(
                [
                    'hits' => \count($matches),
                    'matches' => $matches
                ]
            )
        );
    }

    /**
     * @Route("/{_locale}/clear-translations", name="clear-translations", requirements={"_locale" = "en|de"}, defaults={"_locale" = "de"}))
     * @Route("/{_locale}/clear-translations/")
     * @Route("/clear-translations")
     *
     * @param TranslatorInterface $translator
     *
     * @return Response
     */
    public function clearTranslationsAction(TranslatorInterface $translator): Response
    {
        $translationsDirectory = $this->getParameter('translations_directory');
        if (!file_exists($translationsDirectory)
            || File::rmDirRecursive($translationsDirectory)
        ) {
            return $this->render('default/success.html.twig', [
                'status_message' => $translator->trans('messages.translations_successfully_deleted', [], 'messages'),
            ]);
        }

        return $this->render('default/success.html.twig', [
            'status_message' => $translator->trans('errors.translations_could_not_deleted', [], 'errors'),
        ]);
    }

    /**
     * generate form.
     *
     * @param $sourceFilePathName
     * @param $translationFilePathName
     * @param $translationLanguage
     * @param TranslatorInterface $translator
     * @param $exportFileName
     * @param $overwriteTranslationFile
     * @param $locale
     *
     * @return Form|FormTypeInterface
     */
    private function generateForm(
        $sourceFilePathName,
        $translationFilePathName,
        $translationLanguage,
        $exportFileName,
        $translator,
        $overwriteTranslationFile = false,
        $locale = 'en'
    ) {
        $diffService = new Diff($translator);
        $diffService->setSourceFilePathName($sourceFilePathName)
            ->setTranslationFilePathName($translationFilePathName)
            ->setSourceData([])
            ->setTranslationData([]);

        $translationHelper = new Translations(
            $this->getParameter('translations_directory'),
            $locale
        );

        /** @var FormBuilder $formBuilder */
        $formBuilder = $this->createFormBuilder()
            ->setAction($this->generateUrl('export'))
            ->setMethod('POST')
            ->add('translation_language', ChoiceType::class, [
                'choices' => $translationHelper->availableLanguages(),
                'data' => $translationLanguage,
                'attr' => [
                    'class' => 'col-sm-10',
                    'disabled' => 'disabled',
                ],
                'label_attr' => [
                    'class' => 'col-sm-2 col-form-label',
                ],
                'label' => 'labels.translation_language',
                'translation_domain' => 'labels',
            ])
            ->add('export_file_name', HiddenType::class, [
                'data' => $exportFileName,
            ])
            ->add('overwrite_translation_file', HiddenType::class, [
                'data' => $overwriteTranslationFile,
            ]);

        $diffService->diff();
        \App\Helper\Form::extendFormWithSourceAndTranslationData($diffService, $formBuilder);

        $formBuilder->add(
            'export',
            SubmitType::class,
            [
                'attr' => [
                    'style' => 'clear: both; float: left;',
                ],
                'label' => $translator->trans('labels.export', [], 'labels'),
            ]
        );

        return $formBuilder->getForm();
    }

    /**
     * Get the current logged-in user id, because no authentication exists, the result is currently every time 1.
     *
     * @return int
     */
    private function getCurrentUser(): int
    {
        return 1;
    }
}
