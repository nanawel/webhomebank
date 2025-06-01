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
use app\models\whb\Xhb\Adapter as XhbAdapter;

class InitController extends WhbController
{
    #[\ReturnTypeWillChange]
    protected function _beforeRoute($fw, $args = null) {
        AbstractController::_beforeRoute($fw, $args);
        $this->_viewInstance = View::instance();

        $this->setPageTitle($this->__('WebHomeBank'));

        Design::instance()
            ->addInlineJs(<<<EOJS
                window.LANGUAGE='{$fw->get('LANGUAGE')}';
                window.CURRENCY='{$this->getXhbSession()->getCurrencyCode()}';
            EOJS);
        Design::instance()->addJsModule('dist/app.bundle.js');

        $this->canCacheOutput(false);
        $this->_setupLayoutBlocks();
    }


    protected function _setupLayoutBlocks(): self {
        $this->getView()
            ->setBlockTemplate('head', 'page/head.phtml')
            ->setBlockTemplate('footer', 'page/footer.phtml')
            ->setBlockTemplate('header', 'page/header.phtml')
            ->setBlockTemplate('messages', 'messages.phtml')
            ->setBlockCachePlaceholder('messages');
        return $this;
    }

    public function loadAction(): void {
        $this->getView()
            ->setBlockTemplate('content', 'common/whbload.phtml')
            ->setData('AJAX_PROGRESS_URL', $this->getUrl('*/doLoad'))
            ->setData('REDIRECT_URL', $this->_getReferer() ?: $this->getUrl('/'));
    }

    public function doLoadAction(): void {
        $this->setPageConfig([
            'template' => 'data/json.phtml',
            'mime'     => 'application/json'
        ]);
        try {
            $config = Main::app()->getConfig('XHB');
            $adapter = new XhbAdapter($this->fw, $this->getXhbSession()->get('xhb_file'), $config);
            $xhb = $adapter->loadXhb(true);
            $this->getSession()
                ->addMessage(I18n::instance()->tr('XHB imported to database successfully!'), Session::MESSAGE_INFO);

            $this->getView()->setData('DATA', [
                'status'  => 'success',
                'message' => ''
            ]);
        }
        catch(\Exception $exception) {
            $response = [
                'status'  => 'error',
                'message' => $exception->getMessage()
            ];
            if ($this->fw->get('DEBUG') > 0) {
                $response['trace'] = $exception->getTraceAsString();
            }

            $this->getView()->setData('DATA', $response);
        }
    }
}
