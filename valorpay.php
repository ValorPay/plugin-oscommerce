<?php
/**
 * namespace
 */
namespace common\modules\orderPayment;

/**
 * used classes
 */
use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

/**
 * class declaration
 */
class valorpay extends ModulePayment {

    /**
     * variables
     */
    var $code, $title, $description, $enabled, $order_id;
	
    /**
	 * Sandbox payment URL
	 */
	const WC_VALORPAY_SANDBOX_URL = 'https://securelink-staging.valorpaytech.com:4430/';
	/**
	 * Live payment URL
	 */
	const WC_VALORPAY_URL = 'https://securelink.valorpaytech.com/';
	
	/**
	 * Sandbox vault add customer profile URL
	 */
	const WC_VALORPAY_VAULT_SANDBOX_URL = 'https://demo.valorpaytech.com/api/valor-vault/addcustomer';

	/**
	 * Live vault add customer profile URL
	 */
	const WC_VALORPAY_VAULT_URL = 'https://online.valorpaytech.com/api/valor-vault/addcustomer';

	/**
	 * Sandbox vault add payment profile URL
	 */
	const WC_VALORPAY_VAULT_ADD_PAYMENT_PROFILE_SANDBOX_URL = 'https://demo.valorpaytech.com/api/valor-vault/addpaymentprofile/%s';

	/**
	 * Live vault add payment profile URL
	 */
	const WC_VALORPAY_VAULT_ADD_PAYMENT_PROFILE_URL = 'https://online.valorpaytech.com/api/valor-vault/addpaymentprofile/%s';

	/**
	 * Sandbox vault get payment profile URL
	 */
	const WC_VALORPAY_VAULT_GET_PAYMENT_PROFILE_SANDBOX_URL = 'https://demo.valorpaytech.com/api/valor-vault/getpaymentprofile/%s';

	/**
	 * Live vault get payment profile URL
	 */
	const WC_VALORPAY_VAULT_GET_PAYMENT_PROFILE_URL = 'https://online.valorpaytech.com/api/valor-vault/getpaymentprofile/%s';

	/**
	 * Sandbox vault get payment profile URL
	 */
	const WC_VALORPAY_VAULT_DELETE_PAYMENT_PROFILE_SANDBOX_URL = 'https://demo.valorpaytech.com/api/valor-vault/deletepaymentprofile/%s/%s';

	/**
	 * Live vault get payment profile URL
	 */
	const WC_VALORPAY_VAULT_DELETE_PAYMENT_PROFILE_URL = 'https://online.valorpaytech.com/api/valor-vault/deletepaymentprofile/%s/%s';
		
    /**
     * default values for translation
     */
    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_VALORPAY_TEXT_TITLE' => 'ValorPay',
        'MODULE_PAYMENT_VALORPAY_TEXT_DESCRIPTION' => 'The ValorPay Payment Gateway enables merchants to accept credit card online during checkout.',
        'MODULE_PAYMENT_VALORPAY_ERROR' => 'There has been an error processing your credit card',
        'MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_JANUARY' => 'January',
        'MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_FEBRUARY' => 'February',
        'MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_MARCH' => 'March',
        'MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_APRIL' => 'April',
        'MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_MAY' => 'May',
        'MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_JUNE' => 'June',
        'MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_JULY' => 'July',
        'MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_AUGUST' => 'August',
        'MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_SEPTEMBER' => 'September',
        'MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_OCTOBER' => 'October',
        'MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_NOVEMBER' => 'November',
        'MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_DECEMBER' => 'December',
        'MODULE_PAYMENT_VALORPAY_RESPONSE_TEXT' => 'ValorPos payment completed for %1$s.%2$s <strong>Transaction ID:</strong>  %3$s.%4$s <strong>Approval Code:</strong> %5$s.%6$s <strong>RRN:</strong> %7$s', 
    ];

    /**
     * class constructor
     */
    function __construct($order_id = -1) {
        parent::__construct();
        
        $this->code = 'valorpay';
        $this->title = MODULE_PAYMENT_VALORPAY_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_VALORPAY_TEXT_DESCRIPTION;
        if (!defined('MODULE_PAYMENT_VALORPAY_STATUS')) {
            $this->enabled = false;
            return false;
        }

        $this->sort_order = MODULE_PAYMENT_VALORPAY_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_VALORPAY_STATUS == 'True') ? true : false);
        $this->online = false;
        $this->order_id = $order_id;
        
        if ( MODULE_PAYMENT_VALORPAY_PAYMENT_METHOD == 'Auth Only' && (int) MODULE_PAYMENT_VALORPAY_AUTHONLY_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_VALORPAY_AUTHONLY_ORDER_STATUS_ID;
            $this->paid_status = 0;
        }
        elseif ( MODULE_PAYMENT_VALORPAY_PAYMENT_METHOD == 'Sale' && (int) MODULE_PAYMENT_VALORPAY_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_VALORPAY_ORDER_STATUS_ID;
            $this->paid_status = 0;            
        }
        
        //remove keys if validate key false in last submit
        if( defined('MODULE_PAYMENT_VALORPAY_VALIDATEKEY') && MODULE_PAYMENT_VALORPAY_VALIDATEKEY == "No" ) {
            tep_db_query("UPDATE ".TABLE_PLATFORMS_CONFIGURATION." SET configuration_value='' WHERE configuration_key='MODULE_PAYMENT_VALORPAY_APIID' AND platform_id='".(int)$platform_id."'");
            tep_db_query("UPDATE ".TABLE_PLATFORMS_CONFIGURATION." SET configuration_value='' WHERE configuration_key='MODULE_PAYMENT_VALORPAY_APPKEY' AND platform_id='".(int)$platform_id."'");
            tep_db_query("UPDATE ".TABLE_PLATFORMS_CONFIGURATION." SET configuration_value='' WHERE configuration_key='MODULE_PAYMENT_VALORPAY_EPI' AND platform_id='".(int)$platform_id."'");
            tep_db_query("UPDATE ".TABLE_PLATFORMS_CONFIGURATION." SET configuration_value='' WHERE configuration_key='MODULE_PAYMENT_VALORPAY_AUTHTOKEN' AND platform_id='".(int)$platform_id."'");
        }
        
        if( $this->manager ) {
            $platform_id  = $this->manager->getPlatformId();
            $send_otp_url = \Yii::$app->urlManager->createUrl('valorpay/sendotp');
            $refund_url   = \Yii::$app->urlManager->createUrl('valorpay/refund-payment');
            $balancepayment_url = \Yii::$app->urlManager->createUrl('valorpay/balance-payment');
            $opyID        = \Yii::$app->request->get('opyID');
            $script = 'var valorpay = {
                    platform_id : '.$platform_id.',
                    sendotp_url : "'.$send_otp_url.'",
                    refund_url  : "'.$refund_url.'",
                    balancepayment_url  : "'.$balancepayment_url.'",
                    opyID       : "'.$opyID.'"
                };';
            \Yii::$app->getView()->registerJs($script);
        }
        
        if ($this->checkView() == "admin") {
            $adminrefund = tep_catalog_href_link('lib/common/modules/orderPayment/valorpay/js/adminrefund.js');
            $adminrefundcss = tep_catalog_href_link('lib/common/modules/orderPayment/valorpay/css/adminrefund.css');
            \Yii::$app->getView()->registerJsFile($adminrefund);
            \Yii::$app->getView()->registerCssFile($adminrefundcss);
        } else {
            $branddetection = tep_href_link('lib/common/modules/orderPayment/valorpay/js/BrandDetection.js');
            $creditcard = tep_href_link('lib/common/modules/orderPayment/valorpay/js/creditcard.js');
            $cc = tep_href_link('lib/common/modules/orderPayment/valorpay/js/cc.js');
            $valorpaycss = tep_href_link('lib/common/modules/orderPayment/valorpay/css/valorpay.css');
            \Yii::$app->getView()->registerJsFile($branddetection);
            \Yii::$app->getView()->registerJsFile($creditcard);
            \Yii::$app->getView()->registerJsFile($cc);
            \Yii::$app->getView()->registerCssFile($valorpaycss);
        }

        //check keys entered if ok otherwise throw error
        if( \Yii::$app->request->isPost && \Yii::$app->request->post('module') == "valorpay" 
            && \Yii::$app->request->post('set') == "payment" ) {
                $configuration = \Yii::$app->request->post('configuration');
                $action = \Yii::$app->request->post('action');
                if(!$action && $configuration && count($configuration) > 0) $this->validateKey(\Yii::$app->request->post());
        }
        
        $this->update_status();
        
    }

    function getScriptName() {
        global $PHP_SELF;
        if (class_exists('\Yii') && is_object(\Yii::$app)) {
            return \Yii::$app->controller->id;
        } else {
            return basename($PHP_SELF);
        }
    }
    
    function checkView() {
        $view = "admin";
        if (tep_session_name() != 'tlAdminID') {
            if ($this->getScriptName() == 'checkout' /* FILENAME_CHECKOUT_PAYMENT */) {
                $view = "checkout";
            } else {
                $view = "frontend";
            }
        }
        return $view;
    }

    function update_status() {
        if (($this->enabled == true) && ((int) MODULE_PAYMENT_VALORPAY_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_VALORPAY_ZONE . "' and zone_country_id = '" . $this->billing['country']['id'] . "' order by zone_id");
            while ($check = tep_db_fetch_array($check_query)) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $this->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
    }

    function selection() {

        //if store currency not usd then ignore valorpay payment gateway
        $transaction_currency = \Yii::$app->settings->get('currency');
        if (strtolower($transaction_currency) != "usd") {
            $this->enabled = false;
            return false;
        }
        
        $months_array     = array();
        $months_array[0]  = array('', 'Month');
        $months_array[1]  = array('01', MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_JANUARY);
        $months_array[2]  = array('02', MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_FEBRUARY);
        $months_array[3]  = array('03', MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_MARCH);
        $months_array[4]  = array('04', MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_APRIL);
        $months_array[5]  = array('05', MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_MAY);
        $months_array[6]  = array('06', MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_JUNE);
        $months_array[7]  = array('07', MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_JULY);
        $months_array[8]  = array('08', MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_AUGUST);
        $months_array[9]  = array('09', MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_SEPTEMBER);
        $months_array[10] = array('10', MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_OCTOBER);
        $months_array[11] = array('11', MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_NOVEMBER);
        $months_array[12] = array('12', MODULE_PAYMENT_VALORPAY_CC_TEXT_MONTH_DECEMBER);
        
        $today         = getdate();
        $years_array   = array();
        $years_array[] = array('','Year');

        for ($i = $today['year']; $i < $today['year'] + 10; $i++) {
            $years_array[$i] = array(strftime('%Y', mktime(0, 0, 0, 1, 1, $i)),
                                     strftime('%Y', mktime(0, 0, 0, 1, 1, $i)));
        }

        if( strstr(MODULE_PAYMENT_VALORPAY_ACCEPTED_CARDS,'American Express') ) {
            $amex = tep_href_link('lib/common/modules/orderPayment/valorpay/images/amex.png');
            $logos .= '<img src="'.$amex.'" class="valorpay-cc-logo" id="valorpay-cc-amex" alt="American Express"/>';
        }
        if( strstr(MODULE_PAYMENT_VALORPAY_ACCEPTED_CARDS,'Visa') ) {
            $visa = tep_href_link('lib/common/modules/orderPayment/valorpay/images/visa.png');
            $logos .= '<img src="'.$visa.'" class="valorpay-cc-logo" id="valorpay-cc-visa" alt="VISA"/>';
        }
        if( strstr(MODULE_PAYMENT_VALORPAY_ACCEPTED_CARDS,'MasterCard') ) {
            $mastercard = tep_href_link('lib/common/modules/orderPayment/valorpay/images/mastercard.png');
            $logos .= '<img src="'.$mastercard.'" class="valorpay-cc-logo" id="valorpay-cc-mastercard" alt="Master Card"/>';
        }
        if( strstr(MODULE_PAYMENT_VALORPAY_ACCEPTED_CARDS,'Discover') ) {
            $discover = tep_href_link('lib/common/modules/orderPayment/valorpay/images/discover.png');
            $logos .= '<img src="'.$discover.'" class="valorpay-cc-logo" id="valorpay-cc-discover" alt="Discover"/>';
        }
        if( strstr(MODULE_PAYMENT_VALORPAY_ACCEPTED_CARDS,'JCB') ) {
            $jcb = tep_href_link('lib/common/modules/orderPayment/valorpay/images/jcb.png');
            $logos .= '<img src="'.$jcb.'" class="valorpay-cc-logo" id="valorpay-cc-jcb" alt="JCB"/>';
        }
        if( strstr(MODULE_PAYMENT_VALORPAY_ACCEPTED_CARDS,'Diners') ) {
            $diners = tep_href_link('lib/common/modules/orderPayment/valorpay/images/diners.png');
            $logos .= '<img src="'.$diners.'" class="valorpay-cc-logo" id="valorpay-cc-diners-club" alt="Diners"/>';
        }
        
        if ($this->checkView() == "admin") {
            $valorlogo = tep_catalog_href_link('lib/common/modules/orderPayment/valorpay/logo/ValorLogo.svg');
        } else {
            $valorlogo = tep_href_link('lib/common/modules/orderPayment/valorpay/logo/ValorLogo.svg');
        }
        
        $billing_address = $this->manager->getBillingAddress();
            
        $customer_id = \Yii::$app->user->getId();

        $script = '<script type="text/javascript">'
                  . 'var valorpay_cc_months = ' . json_encode($months_array) . ';'
                  . 'var valorpay_cc_years = ' . json_encode($years_array) . ';'
                  . 'var valorpay_avs_options = \'' . MODULE_PAYMENT_VALORPAY_AVS . '\';'
                  . 'var valorpay_logos = \'' . $logos . '\';'
                  . 'var valorpay_street_address = \'' . $billing_address["street_address"] . '\';'
                  . 'var valorpay_postcode = \'' . $billing_address["postcode"] . '\';'
                  . 'var valorpay_cards = \'' . (MODULE_PAYMENT_VALORPAY_VAULT === 'Yes'?$this->get_payment_profile():'') . '\';'
                  . 'var valorpay_customer_id = \'' . $customer_id . '\';'
                  . 'var valorpay_vault = \'' . MODULE_PAYMENT_VALORPAY_VAULT . '\';'
                  . 'var valorpay_brand_logo_path = \'' . tep_href_link('lib/common/modules/orderPayment/valorpay/images') . '\';'
                . '</script>';
                
        return array('id' => $this->code,
            'module' => (MODULE_PAYMENT_VALORPAY_SHOWLOGO=='Yes'?'<img src="'.$valorlogo.'" width="150px">':MODULE_PAYMENT_VALORPAY_FRONT_TITLE).$script
        );
    
    }

    function process_button() {
        return false;
    }
    
    function get_surcharge_fee($order) 
    {

	    $surchargeIndicator  = MODULE_PAYMENT_VALORPAY_SURCHARGE_MODE;
	    $surchargeType       = MODULE_PAYMENT_VALORPAY_SURCHARGE_TYPE;
	    $surchargeFlatRate   = MODULE_PAYMENT_VALORPAY_SURCHARGE_FLAT;
	    $surchargePercentage = MODULE_PAYMENT_VALORPAY_SURCHARGE_PERCENT;

	    if( $surchargeIndicator == "Yes" ) {
		
			if( $surchargeType == "flatrate" )
				$surchargeAmount = (float)$surchargeFlatRate;
			else {
				$total = $order->info['subtotal'];
				$surchargeAmount = (float)(($total*((float)$surchargePercentage))/100);
			}
		
	    } else {

			$surchargeAmount    = 0;
	    
		}
	    
	    return $surchargeAmount;
    
    }
    
    function _post_transaction($requestData) 
    {

		$sandbox = MODULE_PAYMENT_VALORPAY_SANDBOX;
    	    
		if( $sandbox == "Yes" )	$_valor_api_url = self::WC_VALORPAY_SANDBOX_URL; 
        else $_valor_api_url = self::WC_VALORPAY_URL;
        	
        $json = json_encode($requestData);
        $ch = curl_init($url);
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

    /**
     * Payment capturing / authorising start transaction
     *
     * call post transaction function
     * throws Exception
     **/
    function _start_transaction($order)
    {
        
        $billing  = $order->billing;
        $shipping = $order->delivery;
        $customer = $order->customer;
        
        $valorpay_card_number       = $this->manager->get('valorpay-card-number');
        $valorpay_card_expiry_month = $this->manager->get('valorpay-card-expiry-month');
        $valorpay_card_expiry_year  = $this->manager->get('valorpay-card-expiry-year');
        $valorpay_card_name         = $this->manager->get('valorpay-card-name');
        $valorpay_card_cvv          = $this->manager->get('valorpay-card-cvv');
        $valorpay_card_address      = $this->manager->get('valorpay-card-address');
        $valorpay_card_zip          = $this->manager->get('valorpay-card-zip');
        $valorpay_remote_address    = $this->manager->get('valorpay-remote-address');
        $valorpay_stored_card_token = $this->manager->get('valorpay-stored-card-token');
        
        $surchargeIndicator  = (MODULE_PAYMENT_VALORPAY_SURCHARGE_MODE=='Yes'?1:0);
    
        if( $surchargeIndicator != 1 ) $surchargeIndicator = 0;
        $surchargeAmount = $this->get_surcharge_fee($order);
        $amount = $order->info['total_inc_tax'] - $order->info['tax'] - $surchargeAmount;
        $valor_avs_street = ($valorpay_card_address?$valorpay_card_address:$billing["street_address"]);
        $valor_avs_zip = ($valorpay_card_zip?$valorpay_card_zip:$billing["postcode"]);
        
        $requestData = array(
            'appid' => MODULE_PAYMENT_VALORPAY_APIID,
            'appkey' => MODULE_PAYMENT_VALORPAY_APPKEY,
            'epi' => MODULE_PAYMENT_VALORPAY_EPI,
            'txn_type' => (MODULE_PAYMENT_VALORPAY_PAYMENT_METHOD=='Sale'?'sale':'auth'),
            'ecomm_channel' => 'oscommerce',
            'amount' => $amount,
            'sandbox' => (MODULE_PAYMENT_VALORPAY_SANDBOX=='Yes'?1:0),
            'phone' => $billing["telephone"],
            'email' => $customer["email_address"],
            'uid' => $order->order_id,
            'tax_amount' => $order->info['tax'],
            'ip' => $valorpay_remote_address,
            'surchargeIndicator' => $surchargeIndicator,
            'surchargeAmount' => $surchargeAmount,
            'address1' => $valor_avs_street,
            'address2' => $billing["suburb"],
            'city' => $billing["city"],
            'state' => $billing["state"],
            'zip' => $valor_avs_zip,
            'billing_country' => $billing["country"]["iso_code_2"],
            'shipping_country' => $shipping["country"]["iso_code_2"],
            'cardnumber' => $valorpay_card_number,
            'status' => 'Y',
            'token' => $valorpay_stored_card_token,
            'cvv' => $valorpay_card_cvv,
            'cardholdername' => $valorpay_card_name,
            'expirydate' => $valorpay_card_expiry_month.substr($valorpay_card_expiry_year,2,2)
        );
        
        $response = $this->_post_transaction($requestData);
        
        return $response;

    }

    function before_process() {
        
        try {
            
            $transaction_currency = \Yii::$app->settings->get('currency');
            $order = $this->manager->getOrderInstance();
            
            $response = $this->_start_transaction($order);
            
            if( $response->error_no != "S00" ) {
            
                $error_message = $response->mesg;
                if( isset($response->desc) )
                    $error_message .= " - ".$response->desc;

                tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode($error_message), 'SSL'));

            }
            else {
                
                $this->manager->set('valorpay-txnid', $response->txnid);
                $this->manager->set('valorpay-token', $response->token);
                $this->manager->set('valorpay-rrn', $response->rrn);
                $this->manager->set('valorpay-authcode', $response->approval_code);  

                return true;

            }

        } catch (Exception $e) {
            tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode($e->getMessage()), 'SSL'));
        }
        
        tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(MODULE_PAYMENT_VALORPAY_ERROR), 'SSL'));
    
    }

    function after_process() {
        
        $currencies = \Yii::$container->get('currencies');
        $order = $this->manager->getOrderInstance();
        
        $orders_payment_transaction_id = $this->manager->get('valorpay-txnid');

        if( $order->order_id && $orders_payment_transaction_id ) {

            $orders_payment_token = $this->manager->get('valorpay-token');
            $orders_payment_rrn = $this->manager->get('valorpay-rrn');
            $orders_payment_approval_code = $this->manager->get('valorpay-authcode');

            $result = array (
                "Transaction ID" => $orders_payment_transaction_id,
                "Token" => $orders_payment_token,
                "RRN" => $orders_payment_rrn,
                "Approval Code" => $orders_payment_approval_code
            );

            $response_string = sprintf(
                MODULE_PAYMENT_VALORPAY_RESPONSE_TEXT,
                $currencies->format($order->info['total_inc_tax'], true, $order->info['currency'], $order->info['currency_value']),
                "<br />",
                $orders_payment_transaction_id,
                "<br />",
                $orders_payment_approval_code,
                "<br />",
                $orders_payment_rrn
            );
                            
            $orderPayment = new \common\models\OrdersPayment();
            $orderPayment->orders_payment_module = $this->code;
            $orderPayment->orders_payment_module_name = $this->title;
            $orderPayment->orders_payment_transaction_id = $orders_payment_transaction_id;
            $orderPayment->orders_payment_id_parent = 0;
            $orderPayment->orders_payment_order_id = $order->order_id;
            $orderPayment->orders_payment_is_credit = 0;
            if( MODULE_PAYMENT_VALORPAY_PAYMENT_METHOD == 'Sale' ) {
                $orderPayment->orders_payment_status = \common\helpers\OrderPayment::OPYS_SUCCESSFUL;
            } else {
                $orderPayment->orders_payment_status = \common\helpers\OrderPayment::OPYS_PENDING;
            }
            $orderPayment->orders_payment_amount = $this->formatCurrencyRaw($order->info['total_inc_tax'], $order->info['currency']);
            $orderPayment->orders_payment_currency = trim($order->info['currency']);
            $orderPayment->orders_payment_currency_rate = (float) $order->info['currency_value'];
            $orderPayment->orders_payment_snapshot = json_encode(\common\helpers\OrderPayment::getOrderPaymentSnapshot($order));
            $orderPayment->orders_payment_transaction_status = 'OK';
            $orderPayment->orders_payment_transaction_commentary = $response_string;
            $orderPayment->orders_payment_date_create = date('Y-m-d H:i:s');
            $orderPayment->orders_payment_transaction_full = json_encode($result);
            $orderPayment->save();
            
            //if authorize payment method selected then 
            //make payment paid 0 and payment due set to total payment
            if( MODULE_PAYMENT_VALORPAY_PAYMENT_METHOD == 'Auth Only' ) {

                $get_total = tep_db_query(
                    "select * from " . TABLE_ORDERS_TOTAL . " ".
                    "where orders_id = '".intval($order->order_id)."' AND title = 'Amount Paid:'"
                );
            
                if ( tep_db_num_rows($get_total) > 0 ) {
                    
                    $total = tep_db_fetch_array($get_total);
                    
                    tep_db_query(
                        "UPDATE ".TABLE_ORDERS_TOTAL." SET text='".$total["text"]."', value='".$total["value"]."', 
                        text_inc_tax='".$total["text_inc_tax"]."', text_exc_tax='".$total["text_exc_tax"]."', 
                        value_inc_tax='".$total["value_inc_tax"]."', value_exc_vat='".$total["value_exc_vat"]."' 
                        WHERE orders_id='".intval($order->order_id)."' AND title='Amount Due:'"
                    );
                    
                    $zero_amount = $currencies->format(0, true, $order->info['currency'], $order->info['currency_value']);
                    tep_db_query(
                        "UPDATE ".TABLE_ORDERS_TOTAL." SET text='".$zero_amount."', value=0, text_inc_tax='".$zero_amount."', 
                        text_exc_tax='".$zero_amount."', value_inc_tax=0, value_exc_vat=0 
                        WHERE orders_id='".intval($order->order_id)."' AND title='Amount Paid:'"
                    );
        
                }

            }
                
            //check if customer want to store card for future purchase
            $valorpay_card_stored_cc = $this->manager->get('valorpay-card-stored-cc');

            if( 'Yes' === MODULE_PAYMENT_VALORPAY_VAULT && $valorpay_card_stored_cc == "Y" ) {

                $customer_id = \Yii::$app->user->getId();
                
                $_vault_customer_id = $this->get_vault_customer_id($customer_id);

                //if not then create new customer valorpay vault account and get vault customer id
                if (empty($_vault_customer_id)) {
                    
                    $valorpay_card_address = $this->manager->get('valorpay-card-address');
                    $valorpay_card_zip     = $this->manager->get('valorpay-card-zip');

                    $_vault_customer_id = $this->create_customer_profile( $customer_id, $order, $valorpay_card_address, $valorpay_card_zip );
                    
                }

                //add new card to customer vault account  
                if( $_vault_customer_id ) {
                    
                    $valorpay_card_number       = $this->manager->get('valorpay-card-number');
                    $valorpay_card_expiry_month = $this->manager->get('valorpay-card-expiry-month');
                    $valorpay_card_expiry_year  = $this->manager->get('valorpay-card-expiry-year');
                    $valorpay_card_name         = $this->manager->get('valorpay-card-name');
                    
                    $this->create_payment_profile( $_vault_customer_id, $valorpay_card_number, $valorpay_card_expiry_month, $valorpay_card_expiry_year, $valorpay_card_name  );	
                    
                }

            }

        }
        
    }
  
    function pre_confirmation_check() {

        $valorpay_card_number = $_POST['valorpay-card-number'];
        $valorpay_card_number = str_replace(' ','',$valorpay_card_number);
        $valorpay_card_expiry_month = $_POST['valorpay-card-expiry-month'];
        $valorpay_card_expiry_year = $_POST['valorpay-card-expiry-year'];
        $valorpay_card_name = $_POST['valorpay-card-name'];
        $valorpay_card_cvv = $_POST['valorpay-card-cvv'];
        $valorpay_card_address = $_POST['valorpay-card-address'];
        $valorpay_card_zip = $_POST['valorpay-card-zip'];
        $valorpay_card_stored_cc = $_POST['valorpay-card-stored-cc'];
        $valorpay_stored_card_token = $_POST['stored-card-token'];

        $this->manager->set('valorpay-card-number', $valorpay_card_number);
        $this->manager->set('valorpay-card-expiry-month', $valorpay_card_expiry_month);
        $this->manager->set('valorpay-card-expiry-year', $valorpay_card_expiry_year);
        $this->manager->set('valorpay-card-name', $valorpay_card_name);
        $this->manager->set('valorpay-card-cvv', $valorpay_card_cvv);
        $this->manager->set('valorpay-card-address', $valorpay_card_address);
        $this->manager->set('valorpay-card-zip', $valorpay_card_zip);
        $this->manager->set('valorpay-remote-address', $_SERVER['REMOTE_ADDR']);
        $this->manager->set('valorpay-card-stored-cc', $valorpay_card_stored_cc);
        $this->manager->set('valorpay-stored-card-token', $valorpay_stored_card_token);
        
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

    /**********************************************************************************************************/
    /******************************** V A U L T  C O D E - S T A R T  H E R E *********************************/
    /******************************************************************************************************** */	
	
	/**
	 * Get the API URL.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	function get_valorpay_vault_url($_vault_customer_id, $list=false, $payment_id=0) {
		$api_url = self::WC_VALORPAY_VAULT_URL;
		if ( !$_vault_customer_id && 'Yes' === MODULE_PAYMENT_VALORPAY_SANDBOX ) {
			$api_url = self::WC_VALORPAY_VAULT_SANDBOX_URL;
		}
		if( $_vault_customer_id && !$list && !$payment_id ) {
			$api_url = sprintf(self::WC_VALORPAY_VAULT_ADD_PAYMENT_PROFILE_URL,$_vault_customer_id);
			if ( 'Yes' === MODULE_PAYMENT_VALORPAY_SANDBOX ) {
				$api_url = sprintf(self::WC_VALORPAY_VAULT_ADD_PAYMENT_PROFILE_SANDBOX_URL,$_vault_customer_id);
			}
		}
		if( $_vault_customer_id && $list ) {
			$api_url = sprintf(self::WC_VALORPAY_VAULT_GET_PAYMENT_PROFILE_URL,$_vault_customer_id);
			if ( 'Yes' === MODULE_PAYMENT_VALORPAY_SANDBOX ) {
				$api_url = sprintf(self::WC_VALORPAY_VAULT_GET_PAYMENT_PROFILE_SANDBOX_URL,$_vault_customer_id);
			}
		}
		if( $_vault_customer_id && $payment_id ) {
			$api_url = sprintf(self::WC_VALORPAY_VAULT_DELETE_PAYMENT_PROFILE_URL,$_vault_customer_id,$payment_id);
			if ( 'Yes' === MODULE_PAYMENT_VALORPAY_SANDBOX ) {
				$api_url = sprintf(self::WC_VALORPAY_VAULT_DELETE_PAYMENT_PROFILE_SANDBOX_URL,$_vault_customer_id,$payment_id);
			}
		}
		return $api_url;
	}

	/**
	 * Call valor API
	 *
	 * @since 1.0.0
	 *
	 * @param string $payload JSON payload.
	 * @param string $transaction_type Transaction type.
	 * @return string|WP_Error JSON response or a WP_Error on failure.
	 */
	function post_vault_transaction( $payload, $_vault_customer_id=0, $list = false, $payment_id = 0 ) {
		
        $parsed_response = array();

        if( $list ) {

			$api_url  = $this->get_valorpay_vault_url($_vault_customer_id, true);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            if( MODULE_PAYMENT_VALORPAY_SANDBOX == "Yes" ) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Valor-App-ID: '.MODULE_PAYMENT_VALORPAY_APIID,
                'Valor-App-Key: '.MODULE_PAYMENT_VALORPAY_APPKEY,
                'accept: application/json'
            ));
            $response = curl_exec($ch);
            curl_close($ch);            
            $parsed_response = json_decode($response);
			
		}
		elseif( $payment_id ) {

			$api_url  = $this->get_valorpay_vault_url($_vault_customer_id, false, $payment_id);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            if( MODULE_PAYMENT_VALORPAY_SANDBOX == "Yes" ) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Valor-App-ID: '.MODULE_PAYMENT_VALORPAY_APIID,
                'Valor-App-Key: '.MODULE_PAYMENT_VALORPAY_APPKEY,
                'accept: application/json'
            ));
            $response = curl_exec($ch);
            curl_close($ch);            
            $parsed_response = json_decode($response);

		}
		else {
		
			$api_url  = $this->get_valorpay_vault_url($_vault_customer_id);
			
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            if( MODULE_PAYMENT_VALORPAY_SANDBOX == "Yes" ) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1000); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Valor-App-ID: '.MODULE_PAYMENT_VALORPAY_APIID,
                'Valor-App-Key: '.MODULE_PAYMENT_VALORPAY_APPKEY,
                'accept: application/json',
                'content-type: application/json'
            ));
            $response = curl_exec($ch);
            curl_close($ch);
            
            $parsed_response = json_decode($response);

		}
   
        return $parsed_response;
	}	
 
    /**
     * Get Vault Customer ID
     *
     * @param int $customer_id
     * @return $_vault_customer_id
     */

	function get_vault_customer_id($customer_id) {
		$_vault_customer_id = 0;
        $get_vault = tep_db_query( "select * from valorpay_vault where customer_id = ".$customer_id);
        if ( tep_db_num_rows($get_vault) > 0 ) {
            $vault_data = tep_db_fetch_array($get_vault);
            $_vault_customer_id = $vault_data["vault_customer_id"];
        }
		return $_vault_customer_id;
	}
	
    /**
     * Create Customer Profile API Action
     *
     * @since 1.0.0
     * @param $order
     *
     * @return object JSON response
     */
    function create_customer_profile( $customer_id, $order, $valorpay_card_address, $valorpay_card_zip ) {
        
        $billing  = $order->billing;
        $shipping = $order->delivery;
        $customer = $order->customer;

        $valor_avs_street = ($valorpay_card_address?$valorpay_card_address:$billing["street_address"]);
        $valor_avs_zip = ($valorpay_card_zip?$valorpay_card_zip:$billing["postcode"]);

        $customer_name      = $customer["firstname"]." ".$customer["lastname"];
        $customer_company   = ($customer["company"]==null?'':$customer["company"]);
        $customer_phone     = $customer["telephone"];
        $customer_email     = $customer["email_address"];
        $billing_name       = $billing["firstname"]." ".$billing["lastname"];
        $billing_address    = $valor_avs_street;
        $billing_address2   = ($billing["suburb"]==null?'':$billing["suburb"]);
        $billing_city       = ($billing["city"]==null?'':$billing["city"]);
        $billing_state      = ($billing["state"]==null?'':$billing["state"]);
        $billing_postcode   = $valor_avs_zip;
        $shipping_name      = $shipping["firstname"]." ".$shipping["lastname"];
        $shipping_address   = ($shipping["street_address"]==null?'':$shipping["street_address"]);
        $shipping_address2  = ($shipping["suburb"]==null?'':$shipping["suburb"]);
        $shipping_city      = ($shipping["city"]==null?'':$shipping["city"]);
        $shipping_state     = ($shipping["state"]==null?'':$shipping["state"]);
        $shipping_postcode  = ($shipping["postcode"]==null?'':$shipping["postcode"]);
        
        $zone_query = tep_db_query("select zone_code from " . TABLE_ZONES . " where zone_name = '" . $billing_state . "'");
        while( $row = tep_db_fetch_array($zone_query) ) {
            $billing_state = $row["zone_code"];
        }
        
        $zone_query = tep_db_query("select zone_code from " . TABLE_ZONES . " where zone_name = '" . $shipping_state . "'");
        while( $row = tep_db_fetch_array($zone_query) ) {
            $shipping_state = $row["zone_code"];
        }

        $payload                                              = array();
        $payload["customer_name"]                             = $customer_name;  
        $payload["company_name"]                              = $customer_company;  
        $payload["customer_phone"]                            = $customer_phone;  
        $payload["customer_email"]                            = $customer_email;
        $payload["address_details"][0]["address_label"]          = "Home";  
        $payload["address_details"][0]["billing_customer_name"]  = $billing_name;
        $payload["address_details"][0]["billing_street_no"]      = $billing_address;
        $payload["address_details"][0]["billing_street_name"]    = $billing_address2;
        $payload["address_details"][0]["billing_zip"]            = $billing_postcode;
        $payload["address_details"][0]["billing_city"]           = $billing_city;
        $payload["address_details"][0]["billing_state"]          = $billing_state;
        $payload["address_details"][0]["shipping_customer_name"] = ($shipping_name?$shipping_name:$billing_name);
        $payload["address_details"][0]["shipping_street_no"]     = ($shipping_address?$shipping_address:$billing_address);
        $payload["address_details"][0]["shipping_street_name"]   = ($shipping_address2?$shipping_address2:($shipping_address?$billing_address2:""));
        $payload["address_details"][0]["shipping_zip"]           = ($shipping_postcode?$shipping_postcode:$billing_postcode);
        $payload["address_details"][0]["shipping_city"]          = ($shipping_city?$shipping_city:$billing_city);
        $payload["address_details"][0]["shipping_state"]         = ($shipping_state?$shipping_state:$billing_state);
        
        $payload = json_encode( $payload );
        $response = $this->post_vault_transaction( $payload );
        
        if( $response && $response->status == "OK" ) {

            tep_db_query(
                "INSERT INTO valorpay_vault (customer_id, vault_customer_id) 
                VALUES (".$customer_id.",'".$response->vault_customer_id."')"
            );

            return $response->vault_customer_id;	
        
        }
        
        return 0;
    
    }

    /**
     * Create Payment Profile API Action
     *
     * @since 1.0.0
     * @param int      $_vault_customer_id vault customer id.
     * @param int      $cc_number credit card number.
     * @param array    $exp_date credit card expiry date.
     * @param string   $cc_holdername Card holder name.
     *
     * @return object JSON response
     */	
    function create_payment_profile( $_vault_customer_id, $cc_number, $exp_month, $exp_year, $cc_holdername ) {

        $month = sprintf("%02d", $exp_month);
        $year  = substr( $exp_year, 2 );

        $payload                    = array();
        $payload["pan_num"]         = $cc_number;  
        $payload["expiry"]          = "$month/$year";  
        $payload["cardholder_name"] = $cc_holdername;  
        
        $payload  = json_encode( $payload );
        $response = $this->post_vault_transaction( $payload, $_vault_customer_id );
            
        if( $response && $response->status == "OK" ) {
            return $response->payment_id;	
        }

        return 0;

    }

	/**
	 * Get Payment Profile API Action
	 *
	 * @since 1.0.0
	 * @param int      $_vault_customer_id vault customer id.
	 *
	 * @return object JSON response
	 */
	function get_payment_profile() {
        $profiles = array();
        $customer_id = \Yii::$app->user->getId();
        if( $customer_id ) {
            $_vault_customer_id = $this->get_vault_customer_id($customer_id);
            if( $_vault_customer_id ) {
                $payment_profile = $this->post_vault_transaction( array(), $_vault_customer_id, true );
                if( isset($payment_profile) && $payment_profile->status == "OK" && count($payment_profile->data) > 0 ) {
                    $k = 0;
                    foreach($payment_profile->data as $single_key => $single_data) {
                        if($single_data->status != "active") continue;
                        $profiles[$k]["token"]           = $single_data->token;
                        $profiles[$k]["card_brand"]      = ($single_data->card_brand=="Unknown"?"Diners":$single_data->card_brand);
                        $profiles[$k]["cardholder_name"] = $single_data->cardholder_name;
                        $profiles[$k]["masked_pan"]      = substr($single_data->masked_pan,4); 
                        $k++;
                    }
                }
            }
        }
        return json_encode($profiles);
	}

    function isOnline() {
        return true;
    }

    public function configure_keys() {
        return array(
            'MODULE_PAYMENT_VALORPAY_STATUS' => array(
                'title' => 'Enable ValorPos',
                'value' => 'True',
                'description' => 'Do you want to accept ValorPay payments?',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ),
            'MODULE_PAYMENT_VALORPAY_FRONT_TITLE' => array(
                'title' => 'Title',
                'value' => '',
                'description' => 'ValorPay APP Checkout Title',
                'sort_order' => '2',
            ),
            'MODULE_PAYMENT_VALORPAY_APIID' => array(
                'title' => 'APP ID',
                'value' => '',
                'description' => 'Please read <a href="https://valorpaytech.com/kb/generating-api-keys-e-commerce/" target="_blank">Valorpaytech Generating APP KEYS ECommerce Guideline</a>',
                'sort_order' => '3',
            ),
            'MODULE_PAYMENT_VALORPAY_APPKEY' => array(
                'title' => 'APP KEY',
                'value' => '',
                'description' => 'APP secret key',
                'sort_order' => '4',
            ),
            'MODULE_PAYMENT_VALORPAY_EPI' => array(
                'title' => 'EPI',
                'value' => '',
                'description' => 'External Payments Interface',
                'sort_order' => '5',
            ),
            'MODULE_PAYMENT_VALORPAY_SANDBOX' => array(
                'title' => 'Use Sandbox',
                'value' => 'Yes',
                'description' => 'Set No if Production Keys are set OR Set Yes if Sandbox Keys are set then Live payments will not be taken.',
                'sort_order' => '6',
                'set_function' => 'multiOption(\'dropdown\', array(\'Yes\', \'No\'), ',
            ),
            'MODULE_PAYMENT_VALORPAY_VAULT' => array(
                'title' => 'Use Valor VAULT',
                'value' => 'Yes',
                'description' => 'Allow customers to securely save their card for faster checkouts in future.',
                'sort_order' => '6',
                'set_function' => 'multiOption(\'dropdown\', array(\'Yes\', \'No\'), ',
            ),
            'MODULE_PAYMENT_VALORPAY_SHOWLOGO' => array(
                'title' => 'Show Logo',
                'value' => 'Yes',
                'description' => 'Set Yes to show logo at checkout page OR Set No to show only title while selecting payment method.',
                'sort_order' => '7',
                'set_function' => 'multiOption(\'dropdown\', array(\'Yes\', \'No\'), ',
            ),
            'MODULE_PAYMENT_VALORPAY_PAYMENT_METHOD' => array(
                'title' => 'Payment Method',
                'value' => 'Sale',
                'description' => 'Set payment Authorize-Capture (Sale) or Authorize Only transaction.',
                'sort_order' => '8',
                'set_function' => 'multiOption(\'dropdown\', array(\'Sale\', \'Auth Only\'), ',
            ),
            'MODULE_PAYMENT_VALORPAY_ZONE' => array(
                'title' => 'Payment Zone',
                'value' => '0',
                'description' => 'If a zone is selected, only enable this payment method for that zone.',
                'sort_order' => '9',
                'use_function' => '\\common\\helpers\\Zones::get_zone_class_title',
                'set_function' => 'tep_cfg_pull_down_zone_classes(',
            ),
            'MODULE_PAYMENT_VALORPAY_ORDER_STATUS_ID' => array(
                'title' => 'Set Sale Order Status',
                'value' => '0',
                'description' => 'Set the status of orders made with this payment module to this value. Payment Method must be Sale.',
                'sort_order' => '10',
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\\common\\helpers\\Order::get_order_status_name',
            ),
            'MODULE_PAYMENT_VALORPAY_AUTHONLY_ORDER_STATUS_ID' => array(
                'title' => 'Set Auth Only Order Status',
                'value' => '0',
                'description' => 'Set the status of orders made with this payment module to this value. Payment Method must be Auth Only.',
                'sort_order' => '11',
                'set_function' => 'tep_cfg_pull_down_order_statuses(',
                'use_function' => '\\common\\helpers\\Order::get_order_status_name',
            ),
            'MODULE_PAYMENT_VALORPAY_SORT_ORDER' => array(
                'title' => 'Sort order of display',
                'value' => '0',
                'description' => 'Sort order of ValorPay display. Lowest is displayed first.',
                'sort_order' => '12',
            ),
            'MODULE_PAYMENT_VALORPAY_AVS' => array(
                'title' => 'AVS',
                'value' => 'No',
                'description' => 'The address verification service will add a text field to the checkout page based on the above option.',
                'sort_order' => '13',
                'set_function' => 'multiOption(\'dropdown\', array(\'none\'=>\'None\',\'zip\'=>\'Zip Only\',\'address\'=>\'Address Only\',\'zipandaddress\'=>\'Zip & Address\'), ',
            ),
            'MODULE_PAYMENT_VALORPAY_SURCHARGE_MODE' => array(
                'title' => 'Surcharge Mode',
                'value' => 'No',
                'description' => 'Set YES only if you want all transactions to be fall on surcharge mode, Merchant must have got an Surcharge MID inorder to work.',
                'sort_order' => '14',
                'set_function' => 'multiOption(\'dropdown\', array(\'Yes\', \'No\'), ',
            ),
            'MODULE_PAYMENT_VALORPAY_SURCHARGE_TYPE' => array(
                'title' => 'Surcharge Type',
                'value' => 'percentage',
                'description' => 'Set surcharge type percentage of order subtotal or flat.',
                'sort_order' => '15',
                'set_function' => 'multiOption(\'dropdown\', array(\'percentage\' => \'Surcharge %\', \'flatrate\' => \'Flat Rate $\'), ',
            ),
            'MODULE_PAYMENT_VALORPAY_SURCHARGE_LABEL' => array(
                'title' => 'Surcharge Label',
                'value' => '',
                'description' => 'Set label text to be displayed at checkout page for this charge',
                'sort_order' => '16',
            ),
            'MODULE_PAYMENT_VALORPAY_SURCHARGE_PERCENT' => array(
                'title' => 'Surcharge %',
                'value' => '',
                'description' => 'Percentage will apply only on enabling Surcharge mode and Surcharge type is set fo Surcharge %',
                'sort_order' => '17',
            ),
            'MODULE_PAYMENT_VALORPAY_SURCHARGE_FLAT' => array(
                'title' => 'Flat Rate $',
                'value' => '',
                'description' => 'Flat rate will apply only on if Surcharge mode enable and Surcharge type is set to Flat Rate $',
                'sort_order' => '18',
            ),
            'MODULE_PAYMENT_VALORPAY_ACCEPTED_CARDS' => array(
                'title' => 'Accepted Cards',
                'value' => '',
                'description' => 'Allow Credit or Debit cards while purchasing at checkout page',
                'sort_order' => '19',
                'set_function' => "tep_cfg_select_multioption(array('American Express', 'Visa', 'MasterCard', 'Discover', 'JCB', 'Diners'),",
            ),
        );
    }
    
    function validateKey($post) {

        $appid     = $post["configuration"]["MODULE_PAYMENT_VALORPAY_APIID"];
        $authkey   = $post["configuration"]["MODULE_PAYMENT_VALORPAY_APPKEY"];
        $epi       = $post["configuration"]["MODULE_PAYMENT_VALORPAY_EPI"];
        $authtoken = 1; //$post["configuration"]["MODULE_PAYMENT_VALORPAY_AUTHTOKEN"];
        $sandbox   = $post["configuration"]["MODULE_PAYMENT_VALORPAY_SANDBOX"];
        
        $requestData = array(
            'app_id'     => $appid,
            'auth_key'   => $authkey,
            'epi'        => $epi,
            'auth_token' => $authtoken,
            'mtype'      => 'validate'
        );
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, ($sandbox=='Yes'?'https://vt-staging.valorpaytech.com:4430':'https://vt.valorpaytech.com'));
        if( $sandbox == 'Yes' ) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($requestData));
        $response = curl_exec($ch);
        curl_close($ch);
        
        $response = json_decode($response);

        $get_validate_value = tep_db_query(
            "select configuration_value from " . TABLE_PLATFORMS_CONFIGURATION . " ".
            "where platform_id = '".intval($post['platform_id'])."' AND configuration_key = 'MODULE_PAYMENT_VALORPAY_VALIDATEKEY'"
        );

        if( $response->error_no != "00" ) {
            
            $messageStack = \Yii::$container->get('message_stack');
            $messageStack->add('ValorPay APP KEYS Error: ('.$response->error_no.') '.$response->mesg.', '.$response->desc, 'header', 'error');
            
            if ( tep_db_num_rows($get_validate_value)>0 ) {
                tep_db_query(
                    "UPDATE ".TABLE_PLATFORMS_CONFIGURATION." SET configuration_value='No' 
                    WHERE configuration_key='MODULE_PAYMENT_VALORPAY_VALIDATEKEY' AND platform_id='".(int)$post['platform_id']."'"
                );
            }
            else {
                tep_db_query(
                    "INSERT INTO ".TABLE_PLATFORMS_CONFIGURATION." (configuration_key, configuration_value, platform_id) 
                    VALUES ('MODULE_PAYMENT_VALORPAY_VALIDATEKEY','No','".(int)$post['platform_id']."')"
                );
            }

        }
        else {

            if ( tep_db_num_rows($get_validate_value)>0 ) {
                tep_db_query(
                    "UPDATE ".TABLE_PLATFORMS_CONFIGURATION." SET configuration_value='Yes' 
                    WHERE configuration_key='MODULE_PAYMENT_VALORPAY_VALIDATEKEY' AND platform_id='".(int)$post['platform_id']."'"
                );
            }
            else {
                tep_db_query(
                    "INSERT INTO ".TABLE_PLATFORMS_CONFIGURATION." (configuration_key, configuration_value, platform_id) 
                    VALUES ('MODULE_PAYMENT_VALORPAY_VALIDATEKEY','Yes','".(int)$post['platform_id']."')"
                );
            }

        }

    }

    public function install($platform_id) {
        $languages_id = \common\classes\language::get_id(DEFAULT_LANGUAGE);

        $get_current_status_id = tep_db_fetch_array(tep_db_query(
            "SELECT MAX(orders_status_id) AS current_max_id FROM ".TABLE_ORDERS_STATUS." "
        ));
        $new_status_id = intval($get_current_status_id['current_max_id'])+1;

        //check refunded order status if exist get id or insert then get id
        $order_status_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = 'Refunded' AND language_id = '" . $languages_id . "'");
        if ( tep_db_num_rows($order_status_query) <= 0 ) {
            tep_db_query(
                "INSERT INTO ".TABLE_ORDERS_STATUS." (orders_status_id, orders_status_groups_id, language_id, orders_status_name, 
                orders_status_template, automated, orders_status_template_confirm, orders_status_template_sms, 
                order_evaluation_state_id, order_evaluation_state_default, orders_status_allocate_allow, 
                orders_status_release_deferred, orders_status_send_ga, comment_template_id, hidden) 
                VALUES (".$new_status_id.", 5, ".$languages_id.", 'Refunded', 'Order Status Update', 0, 'Order Status Update', '', 60, 0, 1, 0, -1, 0, 0)"
            );
        }

        //check partial refunded order status if exist get id or insert then get id
        $order_status_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = 'Partially refunded' AND language_id = '" . $languages_id . "'");
        if ( tep_db_num_rows($order_status_query) <= 0 ) {
            tep_db_query(
                "INSERT INTO ".TABLE_ORDERS_STATUS." (orders_status_id, orders_status_groups_id, language_id, orders_status_name, 
                orders_status_template, automated, orders_status_template_confirm, orders_status_template_sms, 
                order_evaluation_state_id, order_evaluation_state_default, orders_status_allocate_allow, 
                orders_status_release_deferred, orders_status_send_ga, comment_template_id, hidden) 
                VALUES (".$new_status_id.", 2, ".$languages_id.", 'Partially refunded', 'Order Status Update', 0, 'Order Status Update', '', 60, 0, 1, 0, -1, 0, 0)"
            );
        }

        //copy ot_valorpay.php file to orderTotal module
        if( file_exists(__DIR__."/ot_valorpay.php") && copy(__DIR__."/ot_valorpay.php",__DIR__."/../orderTotal/ot_valorpay.php") ) {
            unlink(__DIR__."/ot_valorpay.php");
        }

        //copy StoredCards.php file to account folder
        if( file_exists(__DIR__."/StoredCards.php") && copy(__DIR__."/StoredCards.php",__DIR__."/../../../frontend/design/boxes/account/StoredCards.php") ) {
            unlink(__DIR__."/StoredCards.php");
        }

        //copy stored-cards.tpl file to account folder
        if( file_exists(__DIR__."/stored-cards.tpl") && copy(__DIR__."/stored-cards.tpl",__DIR__."/../../../frontend/themes/basic/boxes/account/stored-cards.tpl") ) {
            unlink(__DIR__."/stored-cards.tpl");
        }

        //all these logic are implemented for displaying "Stored Cards" menu in customer "My Account" left panel area.
        //Logic start here
        $microtime = time();
        $theme_query = tep_db_query("select a.theme_name from " . TABLE_THEMES . " a, " . TABLE_PLATFORMS_TO_THEMES . " b where a.id=b.theme_id and b.platform_id=".$platform_id);
        if ( tep_db_num_rows($theme_query) > 0 ) {
            $row = tep_db_fetch_array($theme_query);
            $theme_name = $row["theme_name"];
            
            $block_id2 = 0;
            $block_query = tep_db_query("SELECT id FROM " . TABLE_DESIGN_BOXES . " WHERE block_name = 'account-menu' and theme_name = '".$theme_name."' and widget_name='BlockBox' ");
            if ( tep_db_num_rows($block_query) > 0 ) {
                $block_data = tep_db_fetch_array($block_query);
                $block_id = $block_data["id"];
                $block_query = tep_db_query("SELECT id FROM " . TABLE_DESIGN_BOXES . " WHERE block_name = 'block-".$block_id."' and theme_name = '".$theme_name."' and widget_name='BlockBox' ");
                if ( tep_db_num_rows($block_query) > 0 ) {
                    $block_data = tep_db_fetch_array($block_query);
                    $block_id2 = $block_data["id"];
                }
            }
            
            if( $block_id2 ) {
            
                $block_query = tep_db_query("SELECT id FROM " . TABLE_DESIGN_BOXES . " WHERE block_name = 'block-".$block_id2."' and widget_name = 'account\\AccountLink' and theme_name = '".$theme_name."' ");
                if ( tep_db_num_rows($block_query) > 0 ) {
                    $block_data = tep_db_fetch_array($block_query);
                    $block_id3 = $block_data["id"];
                }
                
                $block_query = tep_db_query("SELECT id FROM " . TABLE_DESIGN_BOXES . " WHERE block_name = 'stored_cards' and widget_name = 'BlockBox' and theme_name = '".$theme_name."' ");
                if ( tep_db_num_rows($block_query) > 0 ) {
                    $block_data = tep_db_fetch_array($block_query);
                    $block_id4 = $block_data["id"];
                }
                
                $block_query = tep_db_query("SELECT id FROM " . TABLE_DESIGN_BOXES . " WHERE block_name = 'block-".$block_id4."' and widget_name = 'BlockBox' and theme_name = '".$theme_name."' ");
                if ( tep_db_num_rows($block_query) > 0 ) {
                    $block_data = tep_db_fetch_array($block_query);
                    $block_id5 = $block_data["id"];
                }
                
                $block_query = tep_db_query("SELECT id FROM " . TABLE_DESIGN_BOXES . " WHERE block_name = 'block-".$block_id5."' and widget_name = 'WidgetsAria' and theme_name = '".$theme_name."' ");
                if ( tep_db_num_rows($block_query) > 0 ) {
                    $block_data = tep_db_fetch_array($block_query);
                    $block_id6 = $block_data["id"];
                }
                
                $block_query = tep_db_query("SELECT id FROM " . TABLE_DESIGN_BOXES . " WHERE block_name = 'block-".$block_id5."-2' and widget_name = 'account\\StoredCards' and theme_name = '".$theme_name."' ");
                if ( tep_db_num_rows($block_query) > 0 ) {
                    $block_data = tep_db_fetch_array($block_query);
                    $block_id7 = $block_data["id"];
                }
                
                $block_query = tep_db_query("SELECT id FROM " . TABLE_DESIGN_BOXES . " WHERE block_name = 'block-".$block_id5."-2' and widget_name = 'Html_box' and theme_name = '".$theme_name."' ");
                if ( tep_db_num_rows($block_query) > 0 ) {
                    $block_data = tep_db_fetch_array($block_query);
                    $block_id8 = $block_data["id"];
                }
                
                $install_query = tep_db_query("SELECT id FROM " . TABLE_DESIGN_BOXES . " WHERE block_name = 'stored_cards' and theme_name = '".$theme_name."' ");
                if ( tep_db_num_rows($install_query) <= 0 ) {
                    
                    tep_db_perform(TABLE_DESIGN_BOXES, array(
                        'microtime' => $microtime,
                        'theme_name' => $theme_name,
                        'block_name' => 'block-'.$block_id2,
                        'widget_name' => 'account\\AccountLink',
                        'widget_params' => '',
                        'sort_order' => 14
                    ));
                    $box_id1 = tep_db_insert_id();

                    tep_db_perform(TABLE_DESIGN_BOXES, array(
                        'microtime' => $microtime,
                        'theme_name' => $theme_name,
                        'block_name' => 'stored_cards',
                        'widget_name' => 'BlockBox',
                        'widget_params' => '',
                        'sort_order' => 1
                    ));
                    $box_id2 = tep_db_insert_id();

                    tep_db_perform(TABLE_DESIGN_BOXES, array(
                        'microtime' => $microtime,
                        'theme_name' => $theme_name,
                        'block_name' => 'block-'.$box_id2,
                        'widget_name' => 'BlockBox',
                        'widget_params' => '',
                        'sort_order' => 1
                    ));
                    $box_id3 = tep_db_insert_id();

                    tep_db_perform(TABLE_DESIGN_BOXES, array(
                        'microtime' => $microtime,
                        'theme_name' => $theme_name,
                        'block_name' => 'block-'.$box_id3,
                        'widget_name' => 'WidgetsAria',
                        'widget_params' => '',
                        'sort_order' => 1
                    ));
                    $box_id4 = tep_db_insert_id();

                    tep_db_perform(TABLE_DESIGN_BOXES, array(
                        'microtime' => $microtime,
                        'theme_name' => $theme_name,
                        'block_name' => 'block-'.$box_id3.'-2',
                        'widget_name' => 'account\\StoredCards',
                        'widget_params' => '',
                        'sort_order' => 2
                    ));
                    $box_id5 = tep_db_insert_id();

                    tep_db_perform(TABLE_DESIGN_BOXES, array(
                        'microtime' => $microtime,
                        'theme_name' => $theme_name,
                        'block_name' => 'block-'.$box_id3.'-2',
                        'widget_name' => 'Html_box',
                        'widget_params' => '',
                        'sort_order' => 1
                    ));
                    $box_id6 = tep_db_insert_id();
                    
                    tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS, array(
                        'box_id' => $box_id2,
                        'microtime' => $microtime,
                        'theme_name' => $theme_name,
                        'setting_name' => 'block_type',
                        'setting_value' => '1',
                        'language_id' => 0,
                        'visibility' => ''
                    ));
                    
                    tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS, array(
                        'box_id' => $box_id3,
                        'microtime' => $microtime,
                        'theme_name' => $theme_name,
                        'setting_name' => 'block_type',
                        'setting_value' => '6',
                        'language_id' => 0,
                        'visibility' => ''
                    ));
                    
                    tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS, array(
                        'box_id' => $box_id3,
                        'microtime' => $microtime,
                        'theme_name' => $theme_name,
                        'setting_name' => 'schema',
                        'setting_value' => '6-3',
                        'language_id' => 0,
                        'visibility' => ''
                    ));
                    
                    tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS, array(
                        'box_id' => $box_id4,
                        'microtime' => $microtime,
                        'theme_name' => $theme_name,
                        'setting_name' => 'aria_name',
                        'setting_value' => 'account-menu',
                        'language_id' => 0,
                        'visibility' => ''
                    ));
                    
                    tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS, array(
                        'box_id' => $box_id6,
                        'microtime' => $microtime,
                        'theme_name' => $theme_name,
                        'setting_name' => 'text',
                        'setting_value' => '<h1>STORED CARDS</h1>',
                        'language_id' => 0,
                        'visibility' => ''
                    ));
                    
                    tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS, array(
                        'box_id' => $box_id1,
                        'microtime' => $microtime,
                        'theme_name' => $theme_name,
                        'setting_name' => 'link',
                        'setting_value' => 'Stored Cards',
                        'language_id' => 0,
                        'visibility' => ''
                    ));
                    
                    tep_db_perform(TABLE_DESIGN_BOXES_SETTINGS, array(
                        'box_id' => $box_id1,
                        'microtime' => $microtime,
                        'theme_name' => $theme_name,
                        'setting_name' => 'text',
                        'setting_value' => 'TEXT_STORED_CARDS',
                        'language_id' => 0,
                        'visibility' => ''
                    ));
                    
                    $json_string = '[{"id":'.$box_id2.',"microtime":"'.$microtime.'","theme_name":"'.$theme_name.'","block_name":"stored_cards","widget_name":"BlockBox","widget_params":"","sort_order":1,"settings":{"0":{"block_type":"1","params":""},"colInRowCarousel":null},"children":{"block-'.$box_id2.'":[{"id":'.$box_id3.',"microtime":"'.$microtime.'","theme_name":"'.$theme_name.'","block_name":"block-'.$box_id2.'","widget_name":"BlockBox","widget_params":"","sort_order":1,"settings":{"0":{"block_type":"6","schema":"6-3","params":""},"colInRowCarousel":null},"children":{"block-'.$box_id3.'":[{"id":'.$box_id4.',"microtime":"'.$microtime.'","theme_name":"'.$theme_name.'","block_name":"block-'.$box_id3.'","widget_name":"WidgetsAria","widget_params":"","sort_order":1,"settings":{"0":{"aria_name":"account-menu","params":""},"colInRowCarousel":null}}],"block-'.$box_id3.'-2":[{"id":'.$box_id6.',"microtime":"'.$microtime.'","theme_name":"'.$theme_name.'","block_name":"block-'.$box_id3.'-2","widget_name":"Html_box","widget_params":"","sort_order":1,"settings":{"0":{"text":"<h1>STORED CARDS<\/h1>","params":""},"colInRowCarousel":null}},{"id":'.$box_id5.',"microtime":"'.$microtime.'","theme_name":"'.$theme_name.'","block_name":"block-'.$box_id3.'-2","widget_name":"account\\StoredCards","widget_params":"","sort_order":2,"settings":{"0":{"params":""},"colInRowCarousel":null}}]}}]}}]';

                    tep_db_perform('design_boxes_cache', array(
                        'block_name' => 'stored_cards',
                        'theme_name' => $theme_name,
                        'json' => $json_string,
                        'serialize' => '',
                        'date_modified' => date("Y-m-d H:i:s")
                    ));

                }
            
            }

        }

        //check if plugin translation key already exist or create new
        $install_query = tep_db_query("SELECT language_id FROM " . TABLE_TRANSLATION . " WHERE translation_key = 'TEXT_STORED_CARDS'");
        if ( tep_db_num_rows($install_query) <= 0 ) {

            tep_db_perform(TABLE_TRANSLATION, array(
                'language_id' => 1,
                'translation_key' => 'TEXT_STORED_CARDS',
                'translation_entity' => 'account',
                'translation_value' => 'Stored Cards',
                'hash' => '0ac48407db45272aa1c1c5ba4ba7025d',
                'not_used' => 0,
                'translated' => 1, 
                'checked' => 1
            ));

        }
        //Logic end here
        
        //check if table already exist or create new
        $install_query = tep_db_query("Show tables like 'valorpay_vault'");
        if ( tep_db_num_rows($install_query) <= 0 ) {
        
            tep_db_query("CREATE TABLE `valorpay_vault` (`customer_id` int(11) NOT NULL, `vault_customer_id` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
        
            tep_db_query("ALTER TABLE `valorpay_vault` ADD PRIMARY KEY (`customer_id`,`vault_customer_id`);");
        
        }

        parent::install($platform_id);

    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_PAYMENT_VALORPAY_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_PAYMENT_VALORPAY_SORT_ORDER');
    }

}