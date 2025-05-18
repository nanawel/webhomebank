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

        if (!$this->getXhbSession()->isModelLoaded()) {
            $this->_reroute('/init/load');
            return false;
        }

        Design::instance()
            ->addInlineJs(<<<EOJS
                window.LANGUAGE='{$fw->get('LANGUAGE')}';
                window.CURRENCY='{$this->getXhbSession()->getCurrencyCode()}';
            EOJS);
        Design::instance()->addJsModule('app.bundle.js');

        $xhb = $this->getXhbSession()->getModel();
        $this->setPageTitle($xhb->getTitle());

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
        $cacheKeyInfo[] = $this->getSession()->getTheme();
        $cacheKeyInfo[] = $this->getSession()->getLocale();
        return $cacheKeyInfo;
    }
}
