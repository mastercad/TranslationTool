<?php
/**
 * Static Helper class to extract information from given file.
 *
 * PHP version 7
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @package    App\Helper
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    GIT: $Id$
 * @link       http://pear.php.net/package/PackageName
 * @since      File available since Release 1.0.0
 */
namespace App\Helper;

use DirectoryIterator;
use DOMDocument;
use DOMElement;
use DOMNamedNodeMap;
use DOMXPath;
use JsonStreamingParser\Listener\InMemoryListener;
use JsonStreamingParser\Parser;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Helper for translations.
 *
 * @package    App\Helper
 * @author     Andreas Kempe <andreas.kempe@byte-artist.de>
 * @copyright  2018-2019 byte-artist
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @since      Class available since Release 1.0.0
 */
class Translations
{
    public const SEARCH_TYPE_ANY = 'any';

    public const SEARCH_TYPE_BEGIN = 'begin';

    public const SEARCH_TYPE_END = 'end';

    public const SEARCH_LOCATION_TOKENS = 1;

    public const SEARCH_LOCATION_TRANSLATIONS = 2;

    /** @var bool */
    private $initialized = false;

    /** @var string */
    private $translationsDirectory;

    /** @var string */
    private $currentLocale;

    /** @var array */
    private $fallBackLanguages = [
        'de' => [
            'cn' => 'Chinesisch',
            'vn' => 'Vietnamesisch',
        ],
        'en' => [
            'cn' => 'Chinese',
            'vn' => 'Vietnamese',
        ],
    ];

    /**
     * @var null
     */
    private static $tokens;

    /** @var array */
    private $locales = [];

    private $externalSourcePath;

    /**
     * Translations constructor.
     *
     * @param $translationsDirectory
     * @param $currentLocale
     */
    public function __construct($translationsDirectory, $currentLocale = 'en')
    {
        $this->externalSourcePath = __DIR__.'/../../vendor/symfony/intl/Resources/data/locales';
        $this->translationsDirectory = $translationsDirectory;
        $this->currentLocale = $currentLocale;
    }

    /**
     * returns all available languages, translated in current locale.
     *
     * @return array
     */
    public function availableLanguages(): array
    {
        $languagesFilePathName = $this->translationsDirectory.'/languages.json';
        $locales = [];

        if (is_readable($languagesFilePathName)) {
            $languages = json_decode(file_get_contents($languagesFilePathName), true);
            $locales = [];

            foreach ($languages as $locale => $data) {
                $locales[$this->mapLocaleToLanguage($locale)] = $locale;
            }
            ksort($locales);
        } elseif ($this->init()) {
            return array_flip($this->locales);
        }

        return $locales;
    }

    /**
     * returns all known tokens and his files and locales.
     *
     * @return mixed
     */
    public function availableTokens()
    {
        /*
//        $memcacheCache = $this->getParameter('doctrine_cache.providers.memcache');
        $memcached = new \Memcached();
        $memcached->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_MODULA);
        $memcached->addServers([
            ['memcached', 11211]
        ]);

        if ($memcached->getStats() === false) {
            echo "memcached servers are offline" . PHP_EOL;
            die();
        }
        */
        if (null === static::$tokens) {
            static::$tokens = [];
            $tokensFilePathName = $this->translationsDirectory.'/tokens.json';

            if (is_readable($tokensFilePathName)) {
                // should more go to this way, but cache is not really used there and the performance is too bad
                /*
                $listener = new InMemoryListener();
                $stream = fopen($tokensFilePathName, 'rb');
                try {
                    $parser = new Parser($stream, $listener);
                    $parser->parse();
                } finally {
                    fclose($stream);
                }

                var_dump($listener->getJson());
            */
                static::$tokens = json_decode(file_get_contents($tokensFilePathName), true);
//                static::$tokens = json_decode(htmlentities(file_get_contents($tokensFilePathName)));
//                $content = file_get_contents($tokensFilePathName);
//                $content = utf8_encode($content);
//                static::$tokens = json_decode($content, true);
            }
        }

//        return $memcached->get('tokens');
        return static::$tokens;
    }

    /**
     * initialize service.
     *
     * @return bool
     */
    private function init(): bool
    {
        if (false === $this->initialized) {
            $languageFile = $this->getExternalSourcePath().'/'.$this->currentLocale.'.json';

            if (!is_readable($languageFile)) {
                $languageFile = $this->getExternalSourcePath().'/en.json';
            }
            $content = json_decode(file_get_contents($languageFile), true);
            $this->locales = $content['Names'];
            $this->initialized = true;
        }

        return $this->initialized;
    }

    /**
     * search matching translations or tokens by given string, needed search location and type of search in match.
     *
     * @param string $searchString
     * @param int    $searchLocation
     * @param string $searchType
     *
     * @return array
     */
    public function searchMatchingTranslations(
        $searchString,
        $searchLocation = self::SEARCH_LOCATION_TOKENS,
        $searchType = self::SEARCH_TYPE_ANY
    ): array {
        $matches = [];
        $searchString = $this->prepareSearchString($searchString, $searchType);

        if ($searchLocation & static::SEARCH_LOCATION_TOKENS) {
            $matches = array_merge($matches, $this->searchMatchesByString($searchString, 'tokens'));
        }
        if ($searchLocation & static::SEARCH_LOCATION_TRANSLATIONS) {
            $matches = array_merge($matches, $this->searchMatchesByString($searchString, 'translations'));
        }
        ksort($matches);

        return $matches;
    }

    /**
     * prepare given string, depending on given search type. given string also quoted for regex.
     *
     * @param string $searchString
     * @param string $searchType
     *
     * @return string
     */
    private function prepareSearchString($searchString, $searchType): string
    {
        preg_quote($searchString, '/@#~');
        switch ($searchType) {
            case self::SEARCH_TYPE_BEGIN:
                $searchString = '^'.$searchString;
                break;

            case self::SEARCH_TYPE_END:
                $searchString .= '$';
                break;
        }

        return $searchString;
    }

    /**
     * search matches, corresponding by given string in available tokens by given position,
     * e.g. TOKENS or TRANSLATIONS.
     *
     * @param $searchString
     * @param $position
     *
     * @return array
     */
    private function searchMatchesByString($searchString, $position): array
    {
        $matches = [];

        if ($this->init()) {
            $tokens = $this->availableTokens();

            if (!is_array($tokens)) {
                return [];
            }
            foreach ($tokens[$position] as $token => $data) {
                if (preg_match('/'.$searchString.'/i', $token)) {
                    $matches[$token][] = $data;
                }
            }

            foreach ($matches as $token => $tokenMatches) {
                $matches[$token] = array_merge(...$tokenMatches);
            }
        }

        return $matches;
    }

    /**
     * returns translated locale, if known.
     *
     * @param string $locale
     *
     * @return string
     */
    public function mapLocaleToLanguage($locale): string
    {
        if (array_key_exists($locale, $this->locales)
            && $this->init()
        ) {
            return $this->locales[$locale];
        }
        if (array_key_exists($this->currentLocale, $this->fallBackLanguages)
            && array_key_exists($locale, $this->fallBackLanguages[$this->currentLocale])
        ) {
            return $this->fallBackLanguages[$this->currentLocale][$locale];
        }
        if (array_key_exists($locale, $this->fallBackLanguages['en'])) {
            return $this->fallBackLanguages['en']['locale'];
        }

        return $locale;
    }

    /**
     * generates known Token and Languages from imported translation files.
     *
     * @return $this
     */
    public function generateKnownTokenAndLanguagesList(): self
    {
        $dirIterator = new DirectoryIterator($this->translationsDirectory);
        @unlink($this->translationsDirectory.'/tokens.json');
        @unlink($this->translationsDirectory.'/languages.json');

        $tokens = [
            'tokens' => [],
            'translations' => [],
        ];
        $languages = [];

        foreach ($dirIterator as $file) {
            if ($file->isDot()
                || 0 === strpos($file->getFilename(), '.')
            ) {
                continue;
            }
            [$fileName, $language] = explode('.', $file->getFilename());
            $xmlObject = new DOMDocument();
            $xmlObject->load($file->getPathname());
            $xpath = new DOMXPath($xmlObject);
            $xpath->registerNamespace('xliff', 'urn:oasis:names:tc:xliff:document:1.2');
            $xpath->registerNamespace('php', 'http://php.net/xpath');

            /** @var DOMElement $transUnit */
            foreach ($xpath->query('//xliff:trans-unit') as $transUnit) {
                $attributes = $transUnit->attributes;
                $token = trim($transUnit->getElementsByTagName('source')->item(0)->textContent);
                $translation = $transUnit->getElementsByTagName('target')->item(0)->textContent;

                if (empty($token)) {
                    continue;
                }
                if (!array_key_exists($token, $tokens['tokens'])) {
                    $tokens['tokens'][$token] = [];
                }
                if (!array_key_exists($fileName, $tokens['tokens'][$token])) {
                    $tokens['tokens'][$token][$fileName] = [];
                    $tokens['tokens'][$token][$fileName]['__token'] = $token;
                }
                if (!array_key_exists('languages', $tokens['tokens'][$token][$fileName])) {
                    $tokens['tokens'][$token][$fileName]['languages'] = [];
                }
                if (!array_key_exists($language, $tokens['tokens'][$token][$fileName]['languages'])) {
                    $tokens['tokens'][$token][$fileName]['languages'][$language] = 0;
                }
                ++$tokens['tokens'][$token][$fileName]['languages'][$language];

                if (!array_key_exists('attributes', $tokens['tokens'][$token][$fileName])) {
                    $tokens['tokens'][$token][$fileName]['attributes'] = [];
                }

                if (!array_key_exists($language, $tokens['tokens'][$token][$fileName]['attributes'])) {
                    $tokens['tokens'][$token][$fileName]['attributes'][$language] = [];
                }

                $tokens['tokens'][$token][$fileName]['attributes'][$language]['translation'] = $translation;
                $extraDataAttributes = [];

                /**
                 * @var string
                 * @var DOMNamedNodeMap $attributeNode
                 */
                foreach ($attributes as $name => $attributeNode) {
                    if ('extradata' === $name) {
                        $extraDataAttributes = json_decode(base64_decode($attributeNode->value), true);
                    } else {
                        $tokens['tokens'][$token][$fileName]['attributes'][$language][$attributeNode->name] =
                            $attributeNode->value;
                    }
                }

                if (0 < count($extraDataAttributes)) {
                    $currentAttributes = $tokens['tokens'][$token][$fileName]['attributes'][$language];
                    $tokens['tokens'][$token][$fileName]['attributes'][$language] = array_merge(
                        $currentAttributes,
                        $extraDataAttributes
                    );
                }

                ksort($tokens['tokens'][$token][$fileName]['attributes']);
                ksort($tokens['tokens'][$token][$fileName]['languages']);
                ksort($tokens['tokens'][$token][$fileName]);
                ksort($tokens['tokens'][$token]);

                if (empty($translation)) {
                    continue;
                }

                if (!array_key_exists($translation, $tokens['translations'])) {
                    $tokens['translations'][$translation] = [];
                }
                if (!array_key_exists($fileName, $tokens['translations'][$translation])) {
                    $tokens['translations'][$translation][$fileName] = [];
                    $tokens['translations'][$translation][$fileName]['__token'] = $token;
                }
                if (!array_key_exists('languages', $tokens['translations'][$translation][$fileName])) {
                    $tokens['translations'][$translation][$fileName]['languages'] = [];
                }
                if (!array_key_exists($language, $tokens['translations'][$translation][$fileName]['languages'])) {
                    $tokens['translations'][$translation][$fileName]['languages'][$language] = 0;
                }
                ++$tokens['translations'][$translation][$fileName]['languages'][$language];

                if (!array_key_exists('attributes', $tokens['translations'][$translation][$fileName])) {
                    $tokens['translations'][$translation][$fileName]['attributes'] = [];
                }

                if (!array_key_exists($language, $tokens['translations'][$translation][$fileName]['attributes'])) {
                    $tokens['translations'][$translation][$fileName]['attributes'][$language] = [];
                }

                /**
                 * @var string
                 * @var DOMNamedNodeMap $attributeNode
                 */
                foreach ($attributes as $name => $attributeNode) {
                    $tokens['translations'][$translation][$fileName]['attributes'][$language][$attributeNode->name] =
                        $attributeNode->value;
                }
            }
            $fileName = $file->getFilename();
            $languages[$language][$fileName] = $fileName;
        }

        ksort($tokens['translations']);
        ksort($tokens['tokens']);
        ksort($languages);
        ksort($tokens);

        /*
        $redis = new \Redis();
        $redis->connect("redis");
        $redis->set('tokens', $tokens);
        var_dump($redis->get('tokens'));
        */

//        $memcacheCache = $this->getParameter('doctrine_cache.providers.memcache');
        /*
        $memcached = new \Memcached();
//        $memcached->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);
        $memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
        $memcached->addServer(
            'memcached',
            11211
        );

        if ($memcached->getStats() === false) {
            echo "memcached servers are offline" . PHP_EOL;
            die();
        }

        echo mb_strlen(serialize((array)$tokens), '8bit');

        $memcached->set('tokens', ["ErsterKEy" => 'Test', "Zweiter Key" => 'Test1', 'TeSt2']);

//        foreach ($memcached->getServerList() as $server) {
//            $client = new \Memcached();
//            $client->addServer($server['host'], $server['port']);
//            printf("%s -> %s" . PHP_EOL, $server['host'], implode(',', $client->getAllKeys()));
//        }

        var_dump($memcached->getAllKeys());
        var_dump($memcached->get('tokens'));
        */

        file_put_contents(
            $this->translationsDirectory.'/tokens.json',
            json_encode($tokens, JSON_UNESCAPED_UNICODE)
        );
        file_put_contents(
            $this->translationsDirectory.'/languages.json',
            json_encode($languages, JSON_UNESCAPED_UNICODE)
        );

        return $this;
    }

    /**
     * @param $string
     *
     * @return string
     */
    public function generateArrayKey($string): string
    {
        $string = preg_replace('/[\x00-\x1F\x7F]/u', '', $string);

        return $string;
    }

    /**
     * Update given data in tokens.
     *
     * @param $data
     * @param $fileName
     *
     * @return Translations
     */
    public function updateToken($data, $fileName): Translations
    {
        $tokens = $this->availableTokens();
        $token = $data['attributes']['resname'];
        $translation = $data['translation'];
        $translationOrig = isset($data['translation_orig']) ? $this->generateArrayKey($data['translation_orig']) : null;
        $fileName = substr($fileName, 0, strpos($fileName, '.'));

//        $tokens['tokens'][$token][$fileName]['attributes'][$this->currentLocale] = $data['attributes'];
//        $currentData = [
//            'attributes' => [
//                'created' => date('Y-m-d H:i:s'),
//                'creator' => 1,
//            ]
//        ];

        if (null !== $translationOrig
            && isset($tokens['translations'][$translationOrig])
        ) {
//            $currentData = $tokens['translations'][$translationOrig][$fileName];
            unset($tokens['translations'][$translationOrig][$fileName]);
        }

//        $tokens['translations'][$translation][$fileName] = $currentData;
//        $tokens['translations'][$translation][$fileName]['attributes'][$this->currentLocale] = $data['attributes'];
        $tokens['tokens'][$token][$fileName]['__token'] = $token;
        $tokens['tokens'][$token][$fileName]['attributes'][$this->currentLocale] = $data['attributes'];
        $tokens['translations'][$translation][$fileName]['attributes'][$this->currentLocale] = $data['attributes'];

        if (!array_key_exists('languages', $tokens['tokens'][$token][$fileName])) {
            $tokens['tokens'][$token][$fileName]['languages'][$this->currentLocale] = 0;
        }
        ++$tokens['tokens'][$token][$fileName]['languages'][$this->currentLocale];

        if (!isset($tokens['translations'][$fileName])
            || !array_key_exists('languages', $tokens['translations'][$fileName])
        ) {
            $tokens['translations'][$translation][$fileName]['languages'][$this->currentLocale] = 0;
        }
        ++$tokens['translations'][$translation][$fileName]['languages'][$this->currentLocale];

        /*
//        $memcacheCache = $this->getParameter('doctrine_cache.providers.memcache');
        $memcached = new \Memcached();
        $memcached->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_MODULA);
        $memcached->addServers([
            ['memcached', 11211]
        ]);

        if ($memcached->getStats() === false) {
            echo "memcached servers are offline" . PHP_EOL;
            die();
        }

                $memcached->set('tokens', $tokens);
        */

        static::$tokens = $tokens;

        return $this;
    }

    /**
     * @return bool|int
     *
     * @throws FileNotFoundException
     */
    public function saveTranslations()
    {
        if (!is_dir($this->translationsDirectory)) {
            throw new FileNotFoundException('Translations Directory not found!');
        }
        $tokensFilePathName = $this->translationsDirectory.'/tokens.json';

        return file_put_contents(
            $tokensFilePathName,
            json_encode(static::$tokens, JSON_UNESCAPED_UNICODE)
        );

//        $memcacheCache = $this->getParameter('doctrine_cache.providers.memcache');
        /*
        $memcached = new \Memcached();
        $memcached->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_MODULA);
        $memcached->addServers([
            ['memcached', 11211]
        ]);

        if ($memcached->getStats() === false) {
            echo "memcached servers are offline" . PHP_EOL;
            die();
        }

        $memcached->set('tokens', $tokens);
        */
    }

    /**
     * @return string
     */
    public function getTranslationsDirectory(): string
    {
        return $this->translationsDirectory;
    }

    /**
     * @param string $translationsDirectory
     *
     * @return Translations
     */
    public function setTranslationsDirectory($translationsDirectory): Translations
    {
        $this->translationsDirectory = $translationsDirectory;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentLocale(): string
    {
        return $this->currentLocale;
    }

    /**
     * @param string $currentLocale
     *
     * @return Translations
     */
    public function setCurrentLocale($currentLocale): Translations
    {
        $this->currentLocale = $currentLocale;

        return $this;
    }

    /**
     * @return string
     */
    public function getExternalSourcePath(): string
    {
        return $this->externalSourcePath;
    }

    /**
     * @param string $externalSourcePath
     *
     * @return Translations
     */
    public function setExternalSourcePath(string $externalSourcePath): Translations
    {
        $this->externalSourcePath = $externalSourcePath;

        return $this;
    }
}
