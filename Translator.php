<?php
namespace MCATranslator;

/**
 * Implements translation for
 */
class Translator
{
    protected $language = null;
    protected $messages = array();
    protected $requestUri = null;
    protected $customMessages = array();

    protected $defaultModule;
    protected $defaultController;
    protected $modulesDir;
    protected $globalDir;

    const EXCEPTION_TRANSLATION_FILE_NOT_FOUND_MESSAGE  = 'Could not found the translation file "%s" at "%s".';
    const EXCEPTION_TRANSLATION_FILE_NOT_FOUND_CODE     = 100;

    /**
     * @param $globalDir
     * @param $modulesDir
     * @param string $defaultModule
     * @param string $defaultController
     */
    public function __construct($globalDir, $modulesDir, $defaultModule = 'system', $defaultController = 'index') {
        $this->modulesDir = $modulesDir;
        $this->globalDir = $globalDir;
        $this->defaultModule = $defaultModule;
        $this->defaultController = $defaultController;
    }

    /**
     * Returns the translation string of the given key
     *
     * @param   string
     * @param   array
     * @return  string
     */
    public function _($translationKey, $placeHolders = null)
    {
        // Search in customer messages array
        if (isset($this->customMessages[$translationKey])) {
            if (is_array($placeHolders)) {
                $strMessage = $this->customMessages[$translationKey];
                $arrParam = array_merge(array($strMessage), $placeHolders);
                $strMessage = call_user_func_array('sprintf', $arrParam);

                return $strMessage;
            } else
                return $this->customMessages[$translationKey];
        }

        // Search in messages array
        if (isset($this->messages[$translationKey])) {
            if (is_array($placeHolders)) {
                $strMessage = $this->messages[$translationKey];
                $arrParam = array_merge(array($strMessage), $placeHolders);
                $strMessage = call_user_func_array('sprintf', $arrParam);

                return $strMessage;
            } else
                return $this->messages[$translationKey];
        }

        return '[' . $translationKey . ']';
    }

    /**
     * Alias for _().
     *
     * @param $translationKey
     * @param null $placeHolders
     */
    public function t($translationKey, $placeHolders = NULL)
    {
        $this->_($translationKey, $placeHolders);
    }

    /**
     * Alias for _().
     *
     * @param   string
     * @param   array
     * @return  string
     */
    public function query($index, $placeHolders = NULL)
    {
        return $this->_($index, $placeHolders);
    }

    /**
     * Returns the translation pattern without substitution.
     *
     * @param $translateKey
     * @return mixed
     */
    public function getTranslationPattern($translateKey)
    {
        if (isset($this->customMessages[$translateKey])) {
            return $this->customMessages[$translateKey];
        }

        if (isset($this->messages[$translateKey])) {
            return $this->messages[$translateKey];
        }
    }

    /**
     * Checks if a translation key is defined in the internal array
     *
     * @param   string $index
     * @return  bool
     */
    public function exists($index)
    {
        return isset($this->messages[$index]) || isset($this->customMessages[$index]);
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Sets a new language based on the current controller. The function checks
     * for the existence of the language file on disk. If not found, tries to load
     * the default language. If not found yet, the function returns FALSE without
     * throwing any errors.
     *
     * @param  string
     * @return boolean
     * @throws TranslateFileNotFoundException
     */
    public function setLanguage($newLanguage = 'pt_BR')
    {
        // Detects controller language file based on URL
        if ($this->getRequestURI() != '') {
            $urlParts = explode('?', $this->getRequestURI());
            $urlParts = str_replace(array('\\', '/'), '/', $urlParts[0]);
            $urlParts = explode('/', $urlParts);
            if (count($urlParts) >= 3) {
                $strModule = $urlParts[1];
                $strController = $urlParts[2];
            } else {
                $strModule = '';
                $strController = '';
            }
        }

        // Gets default module and controller
        if ($strModule == '' || $strController == '') {
            $strModule = $this->defaultModule;
            $strController = $this->defaultController;
        }

        // Path to controller language file
        $relativePath = strtolower($strModule) . '/messages/' . $newLanguage . '/' . strtolower($strController) . '-controller.php';
        $languageFilePath = realpath($this->modulesDir . $relativePath);

        // Verifies if the file exists
        if (is_file($languageFilePath)) {
            include($languageFilePath);

            if (isset($arrMessages)) {
                $this->language = $newLanguage;
                $this->messages = $arrMessages;
                unset($arrMessages);
            }
        } else {
            require_once(dirname(__FILE__) . '/exception/TranslateFileNotFoundException.php');

            throw new TranslateFileNotFoundException(
                sprintf(self::EXCEPTION_TRANSLATION_FILE_NOT_FOUND_MESSAGE, $relativePath, $this->globalDir)
            );
        }
    }

    /**
     * Adds a custom language file. The messages will be merged with the existing
     * loaded messages.
     *
     * @param string $filename Name of the language file
     * @return void
     * @throws TranslateFileNotFoundException
     */
    public function addCustomLanguageFile($filename)
    {
        $relativePath = $this->language . '/' . $filename;
        $pathToLanguageFile = realpath($this->globalDir . '/' . $relativePath);

        if (is_file($pathToLanguageFile)) {
            require_once($pathToLanguageFile);

            if (isset($arrMessages)) {
                $this->customMessages = array_merge($this->customMessages, $arrMessages);
            }
        } else {
            require_once(dirname(__FILE__) . '/exception/TranslateFileNotFoundException.php');

            throw new TranslateFileNotFoundException(
                sprintf(self::EXCEPTION_TRANSLATION_FILE_NOT_FOUND_MESSAGE, $relativePath, $this->globalDir)
            );
        }
    }

    /**
     * Erases all loaded custom messages.
     *
     * @return void
     */
    public function clearCustomLanguageMessages()
    {
        $this->customMessages = array();
    }

    /**
     * @param string
     */
    public function setRequestURI($requestUri)
    {
        $this->requestUri = $requestUri;
    }

    /**
     * @return string
     */
    public function getRequestURI()
    {
        if (is_null($this->requestUri)) {
            $this->requestUri = $_SERVER['REQUEST_URI'];
        }

        return $this->requestUri;
    }
}