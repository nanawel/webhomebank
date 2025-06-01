<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 30/06/15
 * Time: 18:37
 */

namespace app\controllers;

use app\controllers\Core\AbstractController;

class IndexController extends AbstractController
{
    /**
     * @param \Base $fw
     */
    public function indexAction(): void {
        $this->_reroute('/account/index', true);
    }
} 