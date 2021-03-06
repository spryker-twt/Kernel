<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Kernel;

use Exception;
use InvalidArgumentException;
use Spryker\Shared\Config\Config;
use Spryker\Shared\Kernel\Locale\LocaleNotFoundException;
use Spryker\Shared\Library\Context;

class Store
{

    const APPLICATION_ZED = 'ZED';

    /**
     * @var \Spryker\Shared\Kernel\Store
     */
    protected static $instance;

    /**
     * @var string
     */
    protected $storeName;

    /**
     * List of all storeNames
     *
     * @var array
     */
    protected $allStoreNames;

    /**
     * @var array
     */
    protected $allStores;

    /**
     * List of locales
     *
     * E.g: "de" => "de_DE"
     *
     * @var array
     */
    protected $locales;

    /**
     * List of countries
     *
     * @var array
     */
    protected $countries;

    /**
     * Examples: DE, PL
     *
     * @var string
     */
    protected $currentCountry;

    /**
     * Examples: de_DE, pl_PL
     *
     * @var string
     */
    protected $currentLocale;

    /**
     * Examples: EUR, PLN
     *
     * @link http://en.wikipedia.org/wiki/ISO_4217
     *
     * @var string
     */
    protected $currencyIsoCode;

    /**
     * @var array
     */
    protected $contexts;

    /**
     * @var string
     */
    protected static $defaultStore;

    /**
     * @return \Spryker\Shared\Kernel\Store
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return string
     */
    public static function getDefaultStore()
    {
        if (self::$defaultStore === null) {
            self::$defaultStore = require APPLICATION_ROOT_DIR . '/config/Shared/default_store.php';
        }

        return self::$defaultStore;
    }

    protected function __construct()
    {
        $currentStoreName = APPLICATION_STORE;
        $this->initializeSetup($currentStoreName);
        $this->publish();
    }

    /**
     * @return void
     */
    protected function publish()
    {
    }

    /**
     * @param string $currentStoreName
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function getStoreSetup($currentStoreName)
    {
        $stores = require APPLICATION_ROOT_DIR . '/config/Shared/stores.php';

        if (array_key_exists($currentStoreName, $stores) === false) {
            throw new Exception('Missing setup for store: ' . $currentStoreName);
        }

        return $stores;
    }

    /**
     * @param string $currentStoreName
     *
     * @throws \Exception
     *
     * @return void
     */
    public function initializeSetup($currentStoreName)
    {
        $stores = $this->getStoreSetup($currentStoreName);
        $storeArray = $stores[$currentStoreName];
        $vars = get_object_vars($this);
        foreach ($storeArray as $k => $v) {
            if (!array_key_exists($k, $vars)) {
                throw new Exception('Unknown setup-key: ' . $k);
            }
            $this->$k = $v;
        }

        $this->storeName = $currentStoreName;
        $this->allStoreNames = array_keys($stores);
        $this->allStores = $stores;

        if (APPLICATION === self::APPLICATION_ZED) {
            $this->setCurrentLocale(current($this->locales));
        }

        $this->setCurrentCountry(current($this->countries));
    }

    /**
     * @throws \Spryker\Shared\Kernel\Locale\LocaleNotFoundException
     *
     * @return string
     */
    public function getCurrentLocale()
    {
        if ($this->currentLocale === null) {
            throw new LocaleNotFoundException('Locale is not defined.');
        }

        return $this->currentLocale;
    }

    /**
     * @param string $locale string The locale, e.g. 'de_DE'
     *
     * @return string The language, e.g. 'de'
     */
    protected function getLanguageFromLocale($locale)
    {
        //TODO use strstr here
        return substr($locale, 0, strpos($locale, '_'));
    }

    /**
     * @return string
     */
    public function getCurrentLanguage()
    {
        return $this->getLanguageFromLocale($this->currentLocale);
    }

    /**
     * @return array
     */
    public function getAllowedStores()
    {
        return $this->allStoreNames;
    }

    /**
     * @return array
     */
    public function getInactiveStores()
    {
        $inActiveStores = [];
        foreach ($this->getAllowedStores() as $store) {
            if ($this->storeName !== $store) {
                $inActiveStores[] = $store;
            }
        }

        return $inActiveStores;
    }

    /**
     * @return string
     */
    public function getCurrencyIsoCode()
    {
        return $this->currencyIsoCode;
    }

    /**
     * @return array
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * @param string $storeName
     *
     * @return array
     */
    public function getLocalesPerStore($storeName)
    {
        if (!array_key_exists($storeName, $this->allStores)) {
            return [];
        }

        return $this->allStores[$storeName]['locales'];
    }

    /**
     * @return string
     */
    public function getStoreName()
    {
        return $this->storeName;
    }

    /**
     * @param string $storeName
     *
     * @return $this
     */
    public function setStoreName($storeName)
    {
        $this->storeName = $storeName;

        return $this;
    }

    /**
     * @param string $currentLocale
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function setCurrentLocale($currentLocale)
    {
        if (!in_array($currentLocale, $this->locales)) {
            throw new InvalidArgumentException(sprintf('"%s" locale is not a valid value. Please use one of "%s".', $currentLocale, implode('", "', $this->locales)));
        }

        $this->currentLocale = $currentLocale;
    }

    /**
     * @param string|\Spryker\Shared\Library\Context|null $context
     *
     * @return string
     */
    public function getTimezone($context = null)
    {
        $contextInstance = Context::getInstance($context);

        if ($contextInstance->has('timezone')) {
            return $contextInstance->get('timezone');
        } else {
            return Config::get(KernelConstants::PROJECT_TIMEZONE);
        }
    }

    /**
     * @return array
     */
    public function getContexts()
    {
        return $this->contexts;
    }

    /**
     * @return array
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * @param string $currentCountry
     *
     * @return void
     */
    public function setCurrentCountry($currentCountry)
    {
        $this->currentCountry = $currentCountry;
    }

    /**
     * @return string
     */
    public function getCurrentCountry()
    {
        return $this->currentCountry;
    }

    /**
     * @return string
     */
    public function getStorePrefix()
    {
        $prefix = Config::get(KernelConstants::STORE_PREFIX, '');
        $prefix .= $this->getStoreName();

        return $prefix;
    }

}
