<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 30/06/15
 * Time: 18:37
 */

namespace app\controllers;

class SettingsController extends WhbController
{
    public function setCurrencyAction(): void {
        $currencyCode = $this->getRequestQuery('code');
        $this->getSession('xhb')->setCurrencyCode($currencyCode);

        $this->_redirectReferer();
    }

    public function setLocaleAction(): void {
        $locale = $this->getRequestQuery('code');
        $this->getSession()->setLocale($locale);

        $this->_redirectReferer();
    }

    public function setThemeAction(): void {
        $theme = $this->getRequestQuery('code');
        $this->getSession()->setTheme($theme);

        $this->_redirectReferer();
    }
}