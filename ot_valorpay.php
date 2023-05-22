<?php

/**
 * This file is part of osCommerce ecommerce platform.
 * osCommerce the ecommerce
 *
 * @link https://www.oscommerce.com
 * @copyright Copyright (c) 2000-2022 osCommerce LTD
 *
 * Released under the GNU General Public License
 * For the full copyright and license information, please view the LICENSE.TXT file that was distributed with this source code.
 */
namespace common\modules\orderTotal;

use common\classes\modules\ModuleTotal;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

class ot_valorpay extends ModuleTotal {

    var $title, $output;

    protected $visibility = [
        'admin',
        'shop_order',
    ];

    protected $defaultTranslationArray = [
        'MODULE_ORDER_TOTAL_VALORPAY_TITLE' => 'SurchargeFee',
        'MODULE_ORDER_TOTAL_VALORPAY_DESCRIPTION' => 'Surcharge Fee'
    ];

    function __construct() {
        parent::__construct();

        $this->code = 'ot_valorpay';
        $this->title = MODULE_ORDER_TOTAL_VALORPAY_TITLE;
        $this->description = MODULE_ORDER_TOTAL_VALORPAY_DESCRIPTION;
        if (!defined('MODULE_ORDER_TOTAL_VALORPAY_STATUS')) {
            $this->enabled = false;
            return false;
        }
        $this->enabled = ((MODULE_ORDER_TOTAL_VALORPAY_STATUS == 'true') ? true : false);
        $this->sort_order = MODULE_ORDER_TOTAL_VALORPAY_SORT_ORDER;

        $this->output = array();
    }

    function process($replacing_value = -1) {
        $module = $this->manager->getPayment();
	
	$this->output = [];

	if( MODULE_PAYMENT_VALORPAY_STATUS == 'True' && $module == "valorpay" ) {
         
            $order = $this->manager->getOrderInstance();
            \common\helpers\Php8::nullArrProps($order->info, ['total_paid_exc_tax', 'total_paid_inc_tax', 'currency', 'currency_value']);
            $currencies = \Yii::$container->get('currencies');
            $this->output = [];

            $surchargeIndicator  = MODULE_PAYMENT_VALORPAY_SURCHARGE_MODE;
            $surchargeType       = MODULE_PAYMENT_VALORPAY_SURCHARGE_TYPE;
            $surchargeFlatRate   = MODULE_PAYMENT_VALORPAY_SURCHARGE_FLAT;
            $surchargePercentage = MODULE_PAYMENT_VALORPAY_SURCHARGE_PERCENT;
	    
	    if( $surchargeIndicator == "Yes" ) {
            
                if( $surchargeType == "flatrate" ) {
			    $surchargeAmount = (float)$surchargeFlatRate;
		}
                else {
                    $total = $order->info['subtotal'];
                    $surchargeAmount = (float)(($total*((float)$surchargePercentage))/100);
                }
            
            } else {

                $surchargeAmount    = 0;
            
            }

            if( $surchargeAmount > 0 ) {

                $order->info['total'] += $surchargeAmount;
                $order->info['total_inc_tax'] += $surchargeAmount;
                $order->info['total_exc_tax'] += $surchargeAmount;

                $this->output[] = array('title' => $this->title . ':',
                    'text' => $currencies->format($surchargeAmount, true, $order->info['currency'], $order->info['currency_value']),
                    'value' => $surchargeAmount,
                    'text_exc_tax' => $currencies->format($surchargeAmount, true, $order->info['currency'], $order->info['currency_value']),
                    'text_inc_tax' => $currencies->format($surchargeAmount, true, $order->info['currency'], $order->info['currency_value']),
                    'value_exc_vat' => $surchargeAmount,
                    'value_inc_tax' => $surchargeAmount,
                    'sort_order' => $this->sort_order,
                    'code' => $this->code,
		);

	    }

	}
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_ORDER_TOTAL_VALORPAY_STATUS', 'true', 'false');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_ORDER_TOTAL_VALORPAY_SORT_ORDER');
    }

    public function configure_keys() {
        return array(
            'MODULE_ORDER_TOTAL_VALORPAY_STATUS' =>
            array(
                'title' => 'Display Surcharge Fee',
                'value' => 'true',
                'description' => 'Do you want to display the surcharge fee?',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'true\', \'false\'), ',
            ),
            'MODULE_ORDER_TOTAL_VALORPAY_SORT_ORDER' =>
            array(
                'title' => 'Sort Order',
                'value' => '90',
                'description' => 'Sort order of display.',
                'sort_order' => '2',
            ),
        );
    }

}
