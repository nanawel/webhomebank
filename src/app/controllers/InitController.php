<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 25/06/16
 * Time: 22:56
 */

namespace app\controllers;

use app\controllers\Core\AbstractController;
use app\models\core\Design;
use app\models\core\I18n;
use app\models\core\Main;
use app\models\core\Session;
use app\models\whb\View;
use app\models\whb\Xhb\Adapter;

class InitController extends WhbController
{
    protected function _beforeRoute($fw, $args = null) {
        AbstractController::_beforeRoute($fw, $args);
        $this->_viewInstance = View::instance();

        $this->setPageTitle($this->__('WebHomeBank'));

        Design::instance()
            ->addJs('jquery/jquery-2.1.4.min.js', 'header', -100)
            ->addJs('whb/i18n.js')
            ->addInlineJs("var LANGUAGE='{$fw->get('LANGUAGE')}';\nvar CURRENCY='{$this->getXhbSession()->getCurrencyCode()}';\nvar i18n = new I18n(LANGUAGE, CURRENCY);");

        $this->canCacheOutput(false);
        $this->_setupLayoutBlocks();
    }


    protected function _setupLayoutBlocks() {
        $this->getView()
            ->setBlockTemplate('head', 'page/head.phtml')
            ->setBlockTemplate('footer', 'page/footer.phtml')
            ->setBlockTemplate('header', 'page/header.phtml')
            ->setBlockTemplate('messages', 'messages.phtml')
            ->setBlockCachePlaceholder('messages');
        return $this;
    }

    public function indexAction() {
        return $this->runAction();
    }

    public function loadAction() {
        $this->getView()
            ->setBlockTemplate('content', 'common/whbload.phtml')
            ->setData('AJAX_PROGRESS_URL', $this->getUrl('*/doLoad'))
            ->setData('REDIRECT_URL', $this->_getReferer() ?: $this->getUrl('/'));
    }

    public function doLoadAction() {
        $this->setPageConfig(array(
            'template' => 'data/json.phtml',
            'mime'     => 'application/json'
        ));
        try {
        throw new \Exception('test');
            $config = Main::app()->getConfig('XHB');
            $adapter = new Adapter($this->_fw, Main::app()->getConfig('BUDGET_FILE'), $config);
            $xhb = $adapter->loadXhb();
            $this->getSession('xhb')
                ->set('model', $xhb)
                ->addMessage(I18n::instance()->tr('XHB imported to database successfully!'), Session::MESSAGE_INFO);

            $this->getView()->setData('DATA', array(
                'status'  => 'success',
                'message' => ''
            ));
        }
        catch(\Exception $e) {
            $this->getView()->setData('DATA', array(
                'status'  => 'error',
                'message' => $e->getMessage()
            ));
        }
    }
}