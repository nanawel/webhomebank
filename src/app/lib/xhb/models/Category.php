<?php
namespace xhb\models;

use xhb\models\Resource\Iface\Category\Collection;

/**
 * Class Category
 *
 * @method int getKey()
 * @method string getName()
 * @method int getFlags()
 * @method int getParent()
 *
 * @package xhb\models
 */
class Category extends XhbModel
{
    public function getFullname() {
        $fullname = '';
        if ($parentKey = $this->getParent()) {
            $parentCat = $this->getXhb()->getCategory($parentKey);
            $fullname = $parentCat->getName() . ':';
        }
        $fullname .= $this->getName();
        return $fullname;
    }

    /**
     *
     * @return Collection
     */
    public function getChildrenCategories() {
        return $this->getXhb()->getCategoryCollection()
            ->addFieldToFilter('parent', $this->getKey());
    }
}