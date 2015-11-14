<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 10/08/15
 * Time: 18:34
 */

namespace app\models\core;

class Grid extends \Prefab
{
    /**
     * @param array $currentOrder [order => dir]
     * @param array $newOrder [order => dir]
     * @param string $gridPath
     * @param array $params
     */
    public function getOrderLinkUrl(array $currentOrder, array $newOrder, $gridPath = null, $params = array()) {
        $orderParam = isset($params['order_param']) ? $params['order_param'] : 'order';
        $dirParam = isset($params['dir_param']) ? $params['dir_param'] : 'dir';
        if ($gridPath === null) {
            $gridPath = '*/*/*';
        }

        $currentOrderKey = key($currentOrder);
        $currentOrderDir = current($currentOrder);
        $newOrderKey = key($newOrder);
        $newOrderDir = current($newOrder);

        if ($newOrderDir === null) {
            if ($currentOrderKey == $newOrderKey) {
                $newOrderDir = $currentOrderDir == SORT_ASC ? SORT_DESC : SORT_ASC;
            }
            else {
                $newOrderDir = $currentOrderDir;
            }
        }

        $controller = Main::app()->getCurrentController();
        $query = $controller->getRequestQuery();
        $query[$orderParam] = $newOrderKey;
        $query[$dirParam] = $newOrderDir;

        return Url::instance()->getUrl($gridPath, array('_query' => $query));
    }
} 