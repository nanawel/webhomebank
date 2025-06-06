<?php
/**
 * WebHomeBank
 * User: Anael Ollier
 * Date: 05/07/15
 * Time: 09:48
 */

namespace Xhb\Model\Resource;

use Xhb\Util\MagicObject;

/**
 * Class AbstractCollection
 *
 * @package Xhb\Model\Resource
 */
abstract class AbstractCollection extends MagicObject implements \ArrayAccess, \IteratorAggregate, \Countable
{
    const SORT_DIR_DEFAULT = SORT_ASC;

    protected $_itemClass = \xhb\util\MagicObject::class;

    protected $_keyField = null;

    protected $_params = null;

    /**
     * @var MagicObject[]
     */
    protected $_items = [];

    protected $_flags = [];

    protected $_isLoaded = false;

    protected $_filters = [];

    protected $_orders = [];

    protected $_limit = false;

    public function __construct($params = []) {
        $this->setData($params);
    }

    protected function _init($itemClass, $keyField) {
        $this->_itemClass = $itemClass;
        $this->_keyField = $keyField;
    }

    public function setItems(array $items) {
        foreach($items as $it) {
            $this->addItem($it);
        }

        return $this;
    }

    public function load($force = false) {
        if ($force || !$this->isLoaded()) {
            $this->_beforeLoad()
                ->_applyFilters()
                ->_applyOrder()
                ->_applyLimit()
                ->_load();

            $this->_isLoaded = true;
            $this->_afterLoad();
        }

        return $this;
    }

    public function getItems() {
        $this->load();
        return $this->_items;
    }

    protected function _beforeLoad() {
        //to be overridden
        return $this;
    }

    protected function _afterLoad() {
        //to be overridden
        return $this;
    }

    protected function _load() {
        //to be overridden
        return $this;
    }

    public function isLoaded() {
        return $this->_isLoaded;
    }

    public function addItem(MagicObject $item) {
        if ($this->_keyField !== null && ($key = $item->getDataUsingMethod($this->_keyField)) !== null) {
            $this->_items[$key] = $item;
        }
        else {
            $this->_items[] = $item;
        }

        $this->_isLoaded = false;
        return $this;
    }

    public function getItem($value, $key = null, $strict = false) {
        $this->load();
        if ($key === null) {
            $key = $this->_keyField;
        }

        if ($key === $this->_keyField) {
            return $this->_items[$value] ?? null;
        }

        foreach($this->_items as $item) {
            if ($strict) {
                if ($item->getDataUsingMethod($key) === $value) {
                    return $item;
                }
            } elseif ($item->getDataUsingMethod($key) == $value) {
                return $item;
            }
        }

        return null;
    }

    public function getFirstItem() {
        $this->load();
        reset($this->_items);
        return current($this->_items);
    }

    public function getLastItem() {
        $this->load();
        end($this->_items);
        return current($this->_items);
    }

    public function getAllIds() {
        $this->load();
        return array_keys($this->_items);
    }

    /**
     * @param string $field
     * @param mixed $cond
     */
    public function addFieldToSelect($field) {
        // Nothing here for now
        return $this;
    }

    /**
     * @param string $field
     * @param mixed $cond
     */
    public function addFieldToFilter($field, $cond) {
        $this->_filters[] = [$field => $cond];
        $this->_isLoaded = false;
        return $this;
    }

    public function clearFilters() {
        $this->_filters = [];
        $this->_isLoaded = false;
        return $this;
    }

    public function orderBy($field, $dir = self::SORT_DIR_DEFAULT) {
        if (!$dir) {
            $dir = self::SORT_DIR_DEFAULT;
        }

        $this->_orders[$field] = $dir;
        $this->_isLoaded = false;
        return $this;
    }

    public function clearOrders() {
        $this->_orders = [];
        $this->_isLoaded = false;
        return $this;
    }

    public function setLimit($limit) {
        $this->_limit = $limit === false ? false : (int) $limit;
        return $this;
    }

    public function clear() {
        $this->_items = null;
        $this->_isLoaded = false;
        return $this;
    }

    abstract protected function _applyFilters();

    abstract protected function _applyOrder();

    abstract protected function _applyLimit();

    /*
     * ArrayAccess Methods Implementation
     */

    public function offsetExists($offset): bool {
        $this->load();
        return isset($this->_items[$offset]);
    }

    public function offsetGet($offset): mixed {
        $this->load();
        return $this->_items[$offset];
    }

    public function offsetSet($offset, $value): void {
        throw new \BadMethodCallException('Setter is not supported.');
    }

    public function offsetUnset($offset): void {
        throw new \BadMethodCallException('Unsetter is not supported.');
    }

    /*
     * IterratorAggregate Methods Implementation
     */

    public function getIterator(): \Traversable {
        $this->load();
        return new \ArrayIterator($this->_items);
    }

    /*
     * Countable Methods Implementation
     */

    public function count(): int {
        $this->load();
        return count($this->_items);
    }

    public function setFlag($flag) {
        $this->_flags[$flag] = true;
        return $this;
    }

    public function getFlag($flag) {
        return isset($this->_flags[$flag]) && $this->_flags[$flag] == 1;
    }

    public function unsetFlag($flag) {
        $this->_flags[$flag] = false;
        return $this;
    }
}
