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
    protected function _beforeRoute($fw, $args = null) {
        AbstractController::_beforeRoute($fw, $args);
        $this->_viewInstance = View::instance();

        $this->setPageTitle($this->__('WebHomeBank'));

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
            $config = Main::app()->getConfig('XHB');
            $adapter = new XhbAdapter($this->_fw, $this->getXhbSession()->get('xhb_file'), $config);
            $xhb = $adapter->loadXhb(true);
            $this->getSession()
                ->addMessage(I18n::instance()->tr('XHB imported to database successfully!'), Session::MESSAGE_INFO);

            $this->getView()->setData('DATA', array(
                'status'  => 'success',
                'message' => ''
            ));
        }
        catch(\Exception $e) {
            $response = array(
                'status'  => 'error',
                'message' => $e->getMessage()
            );
            if ($this->_fw->get('DEBUG') > 0) {
                $response['trace'] = $e->getTraceAsString();
            }
            $this->getView()->setData('DATA', $response);
        }
    }
}
