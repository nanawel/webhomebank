<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 30/06/15
 * Time: 18:37
 */

namespace app\controllers;

use app\controllers\Core\AbstractController;
use app\models\core\Design;
use app\models\core\Main;
use app\models\whb\View;

class WhbController extends AbstractController
{
    protected function _beforeRoute($fw, $args = null) {
        parent::_beforeRoute($fw, $args);
        $this->_viewInstance = View::instance();

        $xhb = $this->getXhbSession()->getModel();
        $this->setPageTitle($xhb->getTitle());

        Design::instance()
            ->addJs('jquery/jquery-2.1.4.min.js', 'header', -100)
            ->addJs('whb/i18n.js')
            ->addInlineJs("var LANGUAGE='{$fw->get('LANGUAGE')}';\nvar CURRENCY='{$this->getXhbSession()->getCurrencyCode()}';\nvar i18n = new I18n(LANGUAGE, CURRENCY);");

        $this->_setupLayoutBlocks();
    }

    protected function _setupLayoutBlocks() {
        $this->getView()
            ->setBlockTemplate('head', 'page/head.phtml')
            ->setBlockTemplate('footer', 'page/footer.phtml')
            ->setBlockTemplate('header', 'page/header.phtml')
            ->setBlockTemplate('header.nav', 'page/header/nav.phtml')
            //->setBlockCachePlaceholder('footer');                                 // Both method calls
            ->setBlockCachePlaceholder('footer', function() {                       // are equivalent here.
                    return $this->getView()->renderBlockWithoutCache('footer');     //
                })                                                                  //
            ->setBlockTemplate('messages', 'messages.phtml')
            ->setBlockCachePlaceholder('messages');
        return $this;
    }

    protected function _addCrumbsToTitle(array $elements) {
        $pageTitle = $this->getPageTitle();
        $pageTitle .= ' / ' . implode(' / ', $elements);
        $this->setPageTitle($pageTitle);
    }

    /**
     *
     * @return \app\models\whb\Session\Xhb
     */
    protected function getXhbSession() {
        return Main::app()->getSession('xhb');
    }

    protected function _getRequestCacheKeyInfo()
    {
        $cacheKeyInfo = parent::_getRequestCacheKeyInfo();
        $cacheKeyInfo[] = $this->getXhbSession()->getId();
        $cacheKeyInfo[] = $this->getXhbSession()->getModel()->getUniqueKey();
        $cacheKeyInfo[] = $this->getXhbSession()->getCurrencyCode();
        return $cacheKeyInfo;
    }
}