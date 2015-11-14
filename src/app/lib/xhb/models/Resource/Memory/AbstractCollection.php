<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:48
 */

namespace xhb\models\Resource\Memory;

use app\models\core\Log;
use DB\SQL;
use xhb\models\Resource\Closure;
use xhb\models\Resource\Memory\Operation\Collection;
use xhb\util\MagicObject;

/**
 * Class AbstractCollection
 *
 * Default in-memory collection implementation.
 *
 * @package xhb\models\Resource
 */
abstract class AbstractCollection extends \xhb\models\Resource\AbstractCollection
{
    protected $_unfilteredItems = null;

    public function setItems(array $items)
    {
        $this->_unfilteredItems = $items;
        return parent::setItems($items);
    }

    public function addItem(MagicObject $item) {
        if ($this->_keyField !== null && ($key = $item->getDataUsingMethod($this->_keyField)) !== null) {
            $this->_unfilteredItems[$key] = $item;
        }
        else {
            $this->_unfilteredItems[] = $item;
        }
        $this->_isLoaded = false;
        return $this;
    }

    protected function _beforeLoad() {
        $this->_items = $this->_unfilteredItems;
        return $this;
    }

    protected function _applyFilters() {
        foreach($this->_filters as $filter) {
            $field = key($filter);
            $cond = current($filter);

            $filterFunction = null;
            if ($cond instanceof Closure) {
                $filterFunction = $cond;
            }
            else {
                if (!is_array($cond)) {
                    $cond = array('eq' => $cond);
                }
                $operator = key($cond);
                $value = current($cond);

                switch($operator) {
                    case 'eq':
                    case '=':
                    case '==':
                        $filterFunction = function(MagicObject $v) use ($field, $value) {
                            return $v->getDataUsingMethod($field) == $value;
                        };
                        break;

                    case 'ne':
                    case 'neq':
                    case '!=':
                        $filterFunction = function(MagicObject $v) use ($field, $value) {
                            return $v->getDataUsingMethod($field) != $value;
                        };
                        break;

                    case 'gt':
                    case '>':
                        $filterFunction = function(MagicObject $v) use ($field, $value) {
                            return strnatcmp($v->getDataUsingMethod($field), $value) > 0;
                        };
                        break;


                    case 'ge':
                    case '>=':
                        $filterFunction = function(MagicObject $v) use ($field, $value) {
                            return strnatcmp($v->getDataUsingMethod($field), $value) >= 0;
                        };
                        break;

                    case 'lt':
                    case '<':
                        $filterFunction = function(MagicObject $v) use ($field, $value) {
                            return strnatcmp($v->getDataUsingMethod($field), $value) < 0;
                        };
                        break;

                    case 'le':
                    case '<':
                        $filterFunction = function(MagicObject $v) use ($field, $value) {
                            return strnatcmp($v->getDataUsingMethod($field), $value) <= 0;
                        };
                        break;

                    case 'null':
                        $filterFunction = function(MagicObject $v) use ($field, $value) {
                            return ($v->getDataUsingMethod($field) === null) === $value;
                        };
                        break;

                    case 'in':
                        $filterFunction = function(MagicObject $v) use ($field, $value) {
                            return in_array($v->getDataUsingMethod($field), $value);
                        };
                        break;

                    case 'nin':
                        $filterFunction = function(MagicObject $v) use ($field, $value) {
                            return !in_array($v->getDataUsingMethod($field), $value);
                        };
                        break;

                    case 'like':
                        $filterFunction = function(MagicObject $v) use ($field, $value) {
                            $value = '/' . str_replace(array('%', '?'), array('.*', '.?'), preg_quote($value, '/')) . '/i';
                            return preg_match($value, $v->getDataUsingMethod($field)) > 0;
                        };
                        break;

                    default:
                        Log::instance()->log('Unknown operator "' . $operator . '", ignoring.', LOG_WARNING);
                }
            }

            if ($filterFunction) {
                $this->_items = array_filter($this->_items, $filterFunction);
            }
        }
        return $this;
    }

    /**
     * FIXME Implement multisort
     *
     * @return array
     */
    protected function _applyOrder()
    {
        if (!empty($this->_orders) && !empty($this->_items)) {
            $field = key($this->_orders) ;
            $dir = current($this->_orders);

            usort(
                $this->_items,
                function (MagicObject $a, MagicObject $b) use ($field, $dir) {
                    $aVal = $a->getDataUsingMethod($field);
                    $bVal = $b->getDataUsingMethod($field);
                    if (is_numeric($aVal) && is_numeric($bVal)) {
                        return $dir == SORT_ASC ? $aVal - $bVal : $bVal - $aVal;
                    }
                    return $dir == SORT_ASC ? strnatcmp($aVal, $bVal) : strnatcmp($bVal, $aVal);
                }
            );
        }
        return $this;
    }

    protected function _applyLimit()
    {
        if ($this->_limit !== false) {
            $this->_items = array_slice($this->_items, 0, $this->_limit, true);
        }
        return $this;
    }
}