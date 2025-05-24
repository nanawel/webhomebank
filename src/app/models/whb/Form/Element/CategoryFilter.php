<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 02/11/15
 * Time: 21:28
 */

namespace app\models\whb\Form\Element;

use app\models\core\Form\Element\Select;
use Xhb\Model\Category;
use Xhb\Model\Xhb;

class CategoryFilter extends Select implements IWhbElement
{
    protected $_xhb;

    public function __construct(Xhb $xhb, $data = []) {
        $this->setLabel('Category');
        parent::__construct($data);
        $this->_xhb = $xhb;
        $options = [];
        foreach($xhb->getCategoryCollection() as $key => $category) {
            $options[$key] = $this->_categoryToOptionArray($category);
        }

        $this->setOptions($options);
    }

    public function getXhb() {
        return $this->_xhb;
    }

    protected function _categoryToOptionArray(Category $category): array {
        return [
            'label' => $category->getFullname()
        ];
    }
}