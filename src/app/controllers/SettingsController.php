<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 30/06/15
 * Time: 18:37
 */

namespace app\controllers;

use app\helpers\core\Output;
use app\helpers\whb\AccountOperation;
use app\helpers\whb\Chart;
use app\models\core\Chart\Donut;
use app\models\whb\Chart\Scatter;
use app\models\core\Design;
use app\models\core\I18n;
use app\models\whb\Form\Element\PeriodFilter;
use xhb\models\Constants;
use xhb\models\Operation\Collection;
use xhb\models\Xhb\DateHelper;

class SettingsController extends WhbController
{
    public function setCurrencyAction() {
        $currencyCode = $this->getRequestQuery('code');
        $this->getSession('xhb')->setCurrencyCode($currencyCode);

        $this->_redirectReferer();
    }

    public function setLocaleAction() {
        $localeCode = $this->getRequestQuery('code');
        $this->getSession('xhb')->setLocaleCode($localeCode);

        $this->_redirectReferer();
    }
}