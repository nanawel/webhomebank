<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 07/07/15
 * Time: 13:33
 */

namespace app\models\whb;

use app\models\core\Design;
use app\models\core\I18n;
use app\models\core\Session;

class App extends \app\models\core\App
{
    /**
     * @var \Base
     */
    protected $_fw = null;
    protected $_xhbFile = null;

    public function __construct() {
        $this->_fw = \Base::instance();
        $this->_xhbFile = $this->getConfig('BUDGET_FILE');
    }

    protected function _setup() {
        ini_set('max_execution_time', 60);

        if ($this->_fw->get('DEBUG')) {
            ini_set('display_errors', 1);
        }

        // Setup i18n
        $i18n = I18n::instance();
        $i18n->setLocale($this->getSession()->getLocale());
        $i18n->setCurrencyCode($this->getSession('xhb')->getCurrencyCode());

        // Set HTML lang according to defined locale
        $this->_fw->set('HTML_LANG', $i18n->getLocaleCountryCodeISO2());

        // Load XHB
        $xhbSession = $this->getSession('xhb')
            ->set('xhb_file', $this->_fw->get('app.BUDGET_FILE'));

        // Avoid decimal separator issues when casting double and float values to strings
        setlocale(LC_NUMERIC, 'C');

        if ($theme = $this->getSession()->getTheme()) {
            Design::instance()->setTheme($theme);
        }
        Design::instance()->init();

        if ($this->_xhbFile == 'data/example.xhb') {
            $this->getSession()->addMessage($i18n->tr(
                "It seems you're using the default <span class=\"mono\">example.xhb</span> file. " .
                "You may want to change it by editing <span class=\"mono\">etc/local.ini</span>."),
                Session::MESSAGE_INFO,
                array('no_escape' => true)
            );
        }
    }
}