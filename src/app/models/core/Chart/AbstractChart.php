<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 11/11/15
 * Time: 14:37
 */

namespace app\models\core\Chart;


use app\models\core\Form\Element\AbstractElement;
use app\models\core\I18n;
use app\models\core\Log;
use app\models\core\View;
use Xhb\Util\MagicObject;

/**
 * Class AbstractChart
 *
 * @method setId(string $id)
 * @method setDataUrl(string $dataUrl)
 * @method setFilterElements(array $filterElements)
 * @method setNoDataMessage(string $noDataMessage)
 * @method setTemplate(string $template)
 * @method setTitle(string $title)
 *
 *
 * @package app\models\core\Chart
 */
class AbstractChart extends MagicObject
{
    public const SCALE_Y_UNIT_CURRENCY = '__currency__';

    public const SCALE_Y_UNIT_NUMBER = '__number__';

    public const SCALE_Y_UNIT_CUSTOM = '__custom__';

    protected static $_commonDefaultData = [
        'escape_hives'           => false,
        'no_data_message'        => 'No Data',
        'empty_data_message'     => 'No Data',
        'mimetype'               => 'text/html',
        'width'                  => 600,
        'height'                 => 300,
        'filters'                => [],
        'footer_note'            => '',
        'class'                  => '',
        'show_legend'            => true,
    ];

    protected $_defaultData = [];

    public function __construct($data = []) {
        parent::__construct(array_merge(self::$_commonDefaultData, $this->_defaultData, $data));
    }

    public function __(?string $string, $vars = null) {
        return I18n::instance()->tr($string, $vars);
    }

    public final function toHtml() {
        return $this->_toHtml();
    }

    protected function _toHtml() {
        if (!$template = $this->getTemplate()) {
            Log::instance()->log('No template defined for chart with ID "' . $this->getId() . '", skipping.',LOG_ERR);
            return null;
        }

        $fw = \Base::instance();
        if ($this->getEscapeHives() != $fw->get('ESCAPE')) {
            $esc = $fw->get('ESCAPE');
            $fw->set('ESCAPE', $this->getEscapeHives());
        }

        try {
            $hive = $this->getData() + ['_block' => $this];
            $html = View::instance()->render($template, $this->getMimetype(), $hive);
        } finally {
            if ($this->getEscapeHives()) {
                $fw->set('ESCAPE', $esc);
            }
        }

        return $html;
    }

    public function getTooltipJsCallback($jsValueVar = 'data.parsed.y') {
        return match ($this->getData('scale_y_unit')) {
            self::SCALE_Y_UNIT_CURRENCY => sprintf('i18n.formatCurrency(%s)', $jsValueVar),
            self::SCALE_Y_UNIT_CUSTOM => $this->getData('scale_y_unit_custom'),
            default => sprintf('i18n.formatNumber(%s)', $jsValueVar),
        };
    }
}
