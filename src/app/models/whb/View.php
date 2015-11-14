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
    public function getModel() {
        return Main::app()->getSession('xhb')->getModel();
    }
}