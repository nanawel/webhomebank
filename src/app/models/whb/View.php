<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 03/07/15
 * Time: 22:20
 */

namespace app\models\whb;

use app\models\core\Main;

class View extends \app\models\core\View
{
    public function getSession($code = 'xhb') {
        return Main::app()->getSession($code);
    }

    public function getModel() {
        return $this->getSession('xhb')->getModel();
    }
}