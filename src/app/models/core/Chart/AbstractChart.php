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
    protected static $_commonDefaultData = array(
        'escape_hives'           => false,
        'no_data_message'        => 'No Data',
        'empty_data_message'     => 'No Data',
        'mimetype'               => 'text/html',
        'width'                  => 600,
        'height'                 => 300,
        'filters'                => array(),
        'footer_note'            => '',
        'class'                  => '',
        'legend_template'        => '',
        'tooltip_template'       => '',
        'multi_tooltip_template' => '',
        'show_legend'            => true,
        'scale_label'            => '',
        'scale_arg_label'        => ''
    );

    protected $_defaultData = array();

    public function __construct($data = array()) {
        //FIXME Date patterns used by Chart.js are different from PHP's... :(
        //$this->_defaultData['scale_date_time_format'] = I18n::instance()->getDateFormatterInstance()->getPattern();
        $this->_defaultData['scale_date_time_format'] = 'yyyy-mm-dd';
        $this->_defaultData['scale_date_format'] = 'yyyy-mm-dd';
        parent::__construct(array_merge(self::$_commonDefaultData, $this->_defaultData, $data));
    }

    public function __($string, $vars = null) {
        return I18n::instance()->tr($string, $vars);
    }

    public final function toHtml() {
        return $this->_toHtml();
    }

    protected function _toHtml() {
        if (!$template = $this->getTemplate()) {
            Log::instance()->log('No template defined for chart with ID "' . $this->getId() . '", skipping.',LOG_ERR);
            return;
        }
        $fw = \Base::instance();
        if ($this->getEscapeHives() != $fw->get('ESCAPE')) {
            $esc = $fw->get('ESCAPE');
            $fw->set('ESCAPE', $this->getEscapeHives());
        }
        $html = View::instance()->render($template, $this->getMimetype(), $this->getData());
        if ($this->getEscapeHives()) {
            $fw->set('ESCAPE', $esc);
        }
        return $html;
    }
}