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
 
namespace common\modules\orderPayment\valorpay\backend\controllers;

use common\helpers\Translation;
use common\classes\modules\ModulePayment;
use Yii;

/**
 * default controller to handle user requests.
 */
class ValorpayController extends \backend\controllers\Sceleton {
    
    function post_transaction($requestData, $_valor_api_url, $sandbox) 
    {

        $json = json_encode($requestData);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $_valor_api_url);
        if( $sandbox == "Yes" ) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json)
        ));
        $response = curl_exec($ch);
        curl_close($ch);

	    $response = json_decode($response);
	    
	    return $response;
        
    }

    public function actionSendotp() {
        
        if( \Yii::$app->request->isPost === FALSE ) return;
        
        $post = \Yii::$app->request->post();

        $opyID = $post['opyID'];
        $paymentRecord = \common\helpers\OrderPayment::getRecord($opyID);
        if ($paymentRecord instanceof \common\models\OrdersPayment) {
            $orderPaymentAmountAvailable = \common\helpers\OrderPayment::getAmountAvailable($paymentRecord);
            if ($post['amount'] > $orderPaymentAmountAvailable) {
                $post['amount'] = $orderPaymentAmountAvailable;
            }
            if ($post['amount'] <= 0) {
                $this->showError('Amount already refunded!');
            }
        } else {
            $this->showError('Parent payment record not found!');
        }
        
        $query = tep_db_query(
            "select configuration_key, configuration_value from " . TABLE_PLATFORMS_CONFIGURATION . " ".
            "where platform_id = '".intval($post['platform_id'])."' AND configuration_key in (
                'MODULE_PAYMENT_VALORPAY_APIID',
                'MODULE_PAYMENT_VALORPAY_APPKEY',
                'MODULE_PAYMENT_VALORPAY_EPI',
                'MODULE_PAYMENT_VALORPAY_SANDBOX'
            )"
        );

        while( $row = tep_db_fetch_array($query) ) {
            define($row["configuration_key"], $row["configuration_value"]);
        }

        $requestData = array(
            'appid' => MODULE_PAYMENT_VALORPAY_APIID,
            'appkey' => MODULE_PAYMENT_VALORPAY_APPKEY,
            'epi' => MODULE_PAYMENT_VALORPAY_EPI,
            'action' => 'ecomm_refund',
            'amount' => $post['amount'],
            'sandbox' => (MODULE_PAYMENT_VALORPAY_SANDBOX == "Yes"?1:0)
        );

        $sandbox = MODULE_PAYMENT_VALORPAY_SANDBOX;
    	    
		if( $sandbox == "Yes" )	$_valor_api_url = 'https://2fa-staging.valorpaytech.com:4430/?main_action=Manage2FA&operation=ecommRefund'; 
        else $_valor_api_url = 'https://2fa.valorpaytech.com/?main_action=Manage2FA&operation=ecommRefund';    
		
        $response = $this->post_transaction($requestData, $_valor_api_url, $sandbox);
        if( $response->error_code != "S00" ) {
            $result = array(
                'message' => '<span class="warn warning"></span> <span class="text-error">'.$response->error_desc.'</span>',
                'error' => true
            );
            echo json_encode($result);
        }
        else {
            $masked_is_enable_2fa = $response->response->is_enable_2fa;
            if( $masked_is_enable_2fa === 1 ) {
                $masked_email = $response->response->emailId;
                $masked_phone = $response->response->phoneNumber;
                $masked_uuid  = $response->response->uuid;
                $result = array(
                    'message' => '<span>'. sprintf('OTP sent to your registered Email Address %1$s and Mobile Number %2$s', '<b>'.$masked_email.'</b>', '<b>'.$masked_phone.'</b>') .' </span>',
                    'error' => false,
                    'is_enable_2fa' => true,
                    'uuid' => $masked_uuid
                );
                echo json_encode($result);
            }
            else {
                $masked_uuid  = $response->response->uuid;
                $result = array(
                    'error' => false,
                    'is_enable_2fa' => false,
                    'uuid' => $masked_uuid
                );
                echo json_encode($result);
            }
        }
    }
    
    function showError($error_message) {
        $result = array(
            'message' => '<span class="warn warning"></span> <span class="text-error">'.$error_message.'</span>',
            'error' => true
        );
        echo json_encode($result);
        exit();
    }

    public function actionRefundPayment() {
        
        if( \Yii::$app->request->isPost === FALSE ) return;

        $post = \Yii::$app->request->post();
        
        if( !$post['platform_id'] ) $this->showError('The Platform ID is missing.');
        elseif( !$post['opyID'] ) $this->showError('The Payment ID is missing.');
        elseif( !$post['otp'] ) $this->showError('The OTP is missing.');
        elseif( !$post['uuid'] ) $this->showError('The UUID is missing.');
        elseif( !$post['amount'] ) $this->showError('The amount is missing.');
        elseif( !is_numeric($post['amount']) ) $this->showError('The amount is invalid.');

        $query = tep_db_query(
            "select configuration_key, configuration_value from " . TABLE_PLATFORMS_CONFIGURATION . " ".
            "where platform_id = '".intval($post['platform_id'])."' AND configuration_key in (
                'MODULE_PAYMENT_VALORPAY_FRONT_TITLE',
                'MODULE_PAYMENT_VALORPAY_APIID',
                'MODULE_PAYMENT_VALORPAY_APPKEY',
                'MODULE_PAYMENT_VALORPAY_EPI',
                'MODULE_PAYMENT_VALORPAY_SANDBOX',
                'MODULE_PAYMENT_VALORPAY_SURCHARGE_MODE'
            )"
        );

        while( $row = tep_db_fetch_array($query) ) {
            define($row["configuration_key"], $row["configuration_value"]);
        }

        $transactionId = '';
        $token         = '';
        $rrn           = '';
        $auth_code     = '';
        $order_id      = 0;
        $order_currency = '';
        $partialRefund = false;
        $opyID = $post['opyID'];
        $paymentRecord = \common\helpers\OrderPayment::getRecord($opyID);
        if ($paymentRecord instanceof \common\models\OrdersPayment) {
            $order_id       = $paymentRecord["orders_payment_order_id"];
            $order_currency = $paymentRecord["orders_payment_currency"];
            $currency_value = $paymentRecord["orders_payment_currency_rate"];
            $payment        = json_decode($paymentRecord["orders_payment_transaction_full"], true);
            $transactionId  = $payment["Transaction ID"];
            $token          = $payment["Token"];
            $rrn            = $payment["RRN"];
            $auth_code      = $payment["Approval Code"];
            $orderPaymentAmountAvailable = \common\helpers\OrderPayment::getAmountAvailable($paymentRecord);
            if ($post['amount'] > $orderPaymentAmountAvailable) {
                $post['amount'] = $orderPaymentAmountAvailable;
            }
            if ($post['amount'] <= 0) {
                $this->showError('Amount already refunded!');
            }
            if ($post['amount'] < $orderPaymentAmountAvailable) {
                $partialRefund = true;
            }
        } else {
            $this->showError('Parent payment record not found!');
        }

        if( !$order_id ) $this->showError('The order id is missing.');

        $cartInstance = new \common\classes\shopping_cart((int)$order_id);
        if (is_object($cartInstance)) {
            $managerInstance = \common\services\OrderManager::loadManager($cartInstance);
            if (is_object($managerInstance)) {
                $orderInstance = $managerInstance->getOrderInstanceWithId('\common\classes\Order', (int)$order_id);
                if (is_object($orderInstance)) {
                    Yii::$app->get('platform')->config((int)$post['platform_id'])->constant_up();
                    $managerInstance->set('platform_id', (int)$post['platform_id']);
                }
            }
        }

        $requestData = array(
            'appid' => MODULE_PAYMENT_VALORPAY_APIID,
            'appkey' => MODULE_PAYMENT_VALORPAY_APPKEY,
            'epi' => MODULE_PAYMENT_VALORPAY_EPI,
            'txn_type' => 'refund',
            'ecomm_channel' => 'oscommerce',
            'amount' => $post['amount'],
            'sandbox' => (MODULE_PAYMENT_VALORPAY_SANDBOX == "Yes"?1:0),
            'token' => $token,
            'ref_txn_id' => $transactionId,
            'rrn' => $rrn,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'auth_code' => $auth_code,
            'surchargeIndicator' => (MODULE_PAYMENT_VALORPAY_SURCHARGE_MODE == "Yes"?1:0),
            'otp' => $post['otp'],
            'uuid' => $post['uuid']
        );
        
        $sandbox = MODULE_PAYMENT_VALORPAY_SANDBOX;
    	    
		if( $sandbox == "Yes" )	$_valor_api_url = 'https://securelink-staging.valorpaytech.com:4430'; 
        else $_valor_api_url = 'https://securelink.valorpaytech.com/';    
            
        $response = $this->post_transaction($requestData, $_valor_api_url, $sandbox);    
        
        if( $response->error_no != "S00" ) {
            $error_message = $response->mesg;
			if( isset($response->desc) )
	    		$error_message .= "<br />".$response->desc;
            $result = array(
                'message' => '<span class="warn warning"></span> <span class="text-error">'.$error_message.'</span>',
                'error' => true
            );
            echo json_encode($result);
        }
        else {
            $currencies = \Yii::$container->get('currencies');
            $orders_payment_transaction_id = $response->txnid;
            $orders_payment_token = $response->token;
            $orders_payment_rrn = $response->rrn;
            $orders_payment_approval_code = $response->approval_code;

            $result = array (
                "Transaction ID" => $orders_payment_transaction_id,
                "Token" => $orders_payment_token,
                "RRN" => $orders_payment_rrn,
                "Approval Code" => $orders_payment_approval_code
            );

            $response_string = sprintf(
                  'ValorPos payment %1$s for %2$s.%3$s <strong>Transaction ID:</strong>  %4$s.%5$s <strong>Approval Code:</strong> %6$s.%7$s <strong>RRN:</strong> %8$s', 
                "completed",
                $currencies->format($post['amount'], true, $order_currency, $currency_value),
                "<br />",
                $orders_payment_transaction_id,
                "<br />",
                $orders_payment_approval_code,
                "<br />",
                $orders_payment_rrn
            );
                            
            $orderPayment = new \common\models\OrdersPayment();
            $orderPayment->orders_payment_module = 'valorpay';
            $orderPayment->orders_payment_module_name = MODULE_PAYMENT_VALORPAY_FRONT_TITLE;
            $orderPayment->orders_payment_transaction_id = $orders_payment_transaction_id;
            $orderPayment->orders_payment_id_parent = $opyID;
            $orderPayment->orders_payment_order_id = $order_id;
            $orderPayment->orders_payment_is_credit = 0;
            $orderPayment->deferred = 0;
            $orderPayment->orders_payment_status = \common\helpers\OrderPayment::OPYS_REFUNDED;
            $orderPayment->orders_payment_amount = $post['amount'];
            $orderPayment->orders_payment_currency = trim($order_currency);
            $orderPayment->orders_payment_currency_rate = (float) $currency_value;
            $orderPayment->orders_payment_snapshot = json_encode(\common\helpers\OrderPayment::getOrderPaymentSnapshot($orderInstance));
            $orderPayment->orders_payment_transaction_status = 'OK';
            $orderPayment->orders_payment_transaction_commentary = $response_string;
            $orderPayment->orders_payment_date_create = date('Y-m-d H:i:s');
            $orderPayment->orders_payment_transaction_full = json_encode($result);
            global $login_id;
            $orderPayment->orders_payment_admin_create = (int)$login_id;
            
            if( $orderPayment->save() ) {
                
                $languages_id = \common\classes\language::get_id(DEFAULT_LANGUAGE);
                
                $updated = $orderInstance->updatePaidTotals();
                
                if( !$partialRefund ) { 
                
                    //check refunded order status if exist get id or insert then get id
                    $order_status_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = 'Refunded' AND language_id = '" . $languages_id . "'");
                    if ( tep_db_num_rows($order_status_query) > 0 ) {
                        $order_status = tep_db_fetch_array($order_status_query);
                        \common\helpers\Order::setStatus($order_id, (int)$order_status['orders_status_id'], [
                            'customer_notifsssied' => 1,
                        ]);
                    }

                }

                if( $partialRefund ) {
                
                    //check partial refunded order status if exist get id or insert then get id
                    $order_status_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = 'Partially refunded' AND language_id = '" . $languages_id . "'");
                    if ( tep_db_num_rows($order_status_query) > 0 ) {
                        $order_status = tep_db_fetch_array($order_status_query);
                        \common\helpers\Order::setStatus($order_id, (int)$order_status['orders_status_id'], [
                            'customer_notifsssied' => 1,
                        ]);
                    }

                }
                
                $response_string = sprintf(
                    '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg> <h2>Success!</h2><p>The Payment %2$s has been %1$s successfully.%3$s<strong>Transaction ID:</strong>  %4$s.%5$s <strong>Approval Code:</strong> %6$s.%7$s <strong>RRN:</strong> %8$s</p><div class="buttonbox"><button type="button" id="donebutton" class="btn btn-primary">Ok</button></div>', 
                "refunded",
                $currencies->format($post['amount'], true, $order_currency, $currency_value),
                "<br /><br />",
                $orders_payment_transaction_id,
                "<br />",
                $orders_payment_approval_code,
                "<br />",
                $orders_payment_rrn
                );

                $result = array(
                    'message' => $response_string,
                    'error' => false
                );
                echo json_encode($result);

            }
            else {

                $result = array(
                    'message' => '<span class="warn warning"></span> <span class="text-error">Error while updating Order totals!</span>',
                    'error' => true
                );
                echo json_encode($result);

            }

        }

    }
    
    function formatCurrencyRaw($total, $currency_code = null, $currency_value = null) {

        if (!isset($currency_code)) {
            $currency_code = DEFAULT_CURRENCY;
        }

        if (!isset($currency_value) || !is_numeric($currency_value)) {
            $currencies = \Yii::$container->get('currencies');
            $currency_value = $currencies->currencies[$currency_code]['value'];
        }

        return number_format(self::round($total * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }

    public function actionBalancePayment() {

        if( \Yii::$app->request->isPost === FALSE ) return;

        $post = \Yii::$app->request->post();

        $opyID = $post['opyID'];
        $paymentRecord = \common\helpers\OrderPayment::getRecord($opyID);
        if ($paymentRecord instanceof \common\models\OrdersPayment) {
            
            $orderPaymentAmountAvailable = \common\helpers\OrderPayment::getAmountAvailable($paymentRecord);
            
            $result = array(
                'amount' => sprintf("%1.2f",$orderPaymentAmountAvailable),
                'message' => 'Amount already refunded!',
                'error' => false
            );

            echo json_encode($result);

        } else {

            $result = array(
                'error' => true
            );

            echo json_encode($result);

        }

    }

}