<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 02/11/15
 * Time: 21:28
 */

namespace app\models\whb\Form\Element;

use Xhb\Model\Xhb;

interface IWhbElement
{
    public function __construct(Xhb $xhb, $data = []);

    public function getXhb();
}