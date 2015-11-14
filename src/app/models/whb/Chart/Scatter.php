<?php
/**
 * Created by PhpStorm.
 * User: anael
 * Date: 11/11/15
 * Time: 14:37
 */

namespace app\models\whb\Chart;


class Scatter extends \app\models\core\Chart\Scatter
{
    const AXIS_TYPE_DATE_CURRENCY = 'date-currency';
    const AXIS_TYPE_CUSTOM        = 'custom';

    protected function _toHtml() {
        $axisTypeDefaultConfig = array();
        switch($this->getAxisType()) {
            case self::AXIS_TYPE_DATE_CURRENCY:
                $axisTypeDefaultConfig = array(
                    'legend_template' =>
                        '<ul class="<%= name.toLowerCase() %>-legend">' .
                        '<% for (var i=0; i<datasets.length; i++){ %>' .
                        '<li><span class="label-color" style="background-color:<%= datasets[i].strokeColor %>"></span><span class="label-text"><% if(datasets[i].label){ %><%= datasets[i].label %><% } %></span></li>' .
                        '<% } %></ul>',
                    'tooltip_template' =>
                        '<%if (argLabel){ %><%= i18n.formatDate(argLabel) %><% } %> - <%if (datasetLabel){ %><%= datasetLabel%> <% } %>(<%= valueLabel %>)',
                    'multi_tooltip_template' =>
                        '<%if (argLabel){ %><%=argLabel%><%}%> - <%if (datasetLabel){ %><%=datasetLabel%> <% } %>(<%= valueLabel %>)',
                    'scale_label' =>
                        '<%=i18n.formatCurrency(value)%>'
                );
                break;

            // More to come

            case self::AXIS_TYPE_CUSTOM:
            default:
                //nothing
        }
        foreach($axisTypeDefaultConfig as $key => $config) {
            if ($this->getData($key) === '') {
                $this->setData($key, $config);
            }
        }
        return parent::_toHtml();
    }
}