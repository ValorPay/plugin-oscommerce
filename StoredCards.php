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

namespace frontend\design\boxes\account;

use Yii;
use yii\base\Widget;
use frontend\design\IncludeTpl;
use frontend\design\SplitPageResults;
use common\helpers\Date as DateHelper;

class StoredCards extends Widget
{

    public $file;
    public $params;
    public $settings;
	
    /**
	 * Sandbox vault get payment profile URL
	 */
	const WC_VALORPAY_VAULT_GET_PAYMENT_PROFILE_SANDBOX_URL = 'https://demo.valorpaytech.com/api/valor-vault/getpaymentprofile/%s';

	/**
	 * Sandbox vault get payment profile URL
	 */
	const WC_VALORPAY_VAULT_DELETE_PAYMENT_PROFILE_SANDBOX_URL = 'https://demo.valorpaytech.com/api/valor-vault/deletepaymentprofile/%s/%s';
	
    public function init()
    {
        parent::init();
    }

    public function run()
    {

        //delete payment profile
        if( $_GET["profile_id"] ) {
            $this->delete_payment_profile();
        }

        $success_message = '';
        if( $_GET["success_message"] ) {
            $success_message = $_GET["success_message"];
        }

        $payment_profile = $this->get_payment_profile();
		 
        return IncludeTpl::widget(['file' => 'boxes/account/stored-cards.tpl', 'params' => [
            'mainData' => $this->params['mainData'],
            'total_count' => count($payment_profile),
            'payment_profile' => $payment_profile,
            'settings' => $this->settings,
            'success_message' => $success_message,
            'brand_logo_path' => tep_href_link('lib/common/modules/orderPayment/valorpay/images')
        ]]);

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
	 * Get the API URL.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	function get_valorpay_vault_url($_vault_customer_id, $list=false, $profile_id=0) {
		if( $_vault_customer_id && $list ) {
			$api_url = sprintf(self::WC_VALORPAY_VAULT_GET_PAYMENT_PROFILE_SANDBOX_URL,$_vault_customer_id);
			if ( 'Yes' === MODULE_PAYMENT_VALORPAY_SANDBOX ) {
				$api_url = sprintf(self::WC_VALORPAY_VAULT_GET_PAYMENT_PROFILE_SANDBOX_URL,$_vault_customer_id);
			}
		}
		if( $_vault_customer_id && $profile_id ) {
			$api_url = sprintf(self::WC_VALORPAY_VAULT_DELETE_PAYMENT_PROFILE_SANDBOX_URL,$_vault_customer_id,$profile_id);
			if ( 'Yes' === MODULE_PAYMENT_VALORPAY_SANDBOX ) {
				$api_url = sprintf(self::WC_VALORPAY_VAULT_DELETE_PAYMENT_PROFILE_SANDBOX_URL,$_vault_customer_id,$profile_id);
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
	function post_vault_transaction( $payload, $_vault_customer_id=0, $list = false, $profile_id = 0 ) {
		
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
		elseif( $profile_id ) {

			$api_url  = $this->get_valorpay_vault_url($_vault_customer_id, false, $profile_id);

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

        return $parsed_response;
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
                        $profiles[$k]["card_brand"]      = $single_data->card_brand;
                        $profiles[$k]["cardholder_name"] = $single_data->cardholder_name;
                        $profiles[$k]["masked_pan"]      = substr($single_data->masked_pan,4); 
                        $profiles[$k]["delete_url"]      = tep_href_link('account', 'page_name=stored_cards&action=delete&profile_id='.$single_data->payment_id, 'SSL');
                        $k++;
                    }
                }
            }
        }
        return $profiles;
	}

	/**
	 * Delete Payment Profile API Action
	 *
	 * @since 1.0.0
	 * @param int      $_vault_customer_id vault customer id.
	 *
	 * @return object JSON response
	 */
	function delete_payment_profile() {

        $customer_id = \Yii::$app->user->getId();
        if( $customer_id ) {
            $_vault_customer_id = $this->get_vault_customer_id($customer_id);
            if( $_vault_customer_id ) {
                $profile_id = $_GET["profile_id"];
                if( $profile_id ) {
                    $delete_profile =  $this->post_vault_transaction( array(), $_vault_customer_id, false, $profile_id );
                    if( $delete_profile->status == "OK" ) {
                        tep_redirect(tep_href_link('account', 'page_name=stored_cards&success_message=' . urlencode("The record is successfully deleted."), 'SSL'));
                    }
                    else 
                        tep_redirect(tep_href_link('account', 'page_name=stored_cards&error_message=' . urlencode("Sorry could not delete record."), 'SSL'));
                }
                else 
                    tep_redirect(tep_href_link('account', 'page_name=stored_cards&error_message=' . urlencode("Card ID is missing."), 'SSL'));
            }
            else
                tep_redirect(tep_href_link('account', 'page_name=stored_cards&error_message=' . urlencode("Vault Customer ID is missing."), 'SSL'));
        }
        else
            tep_redirect(tep_href_link('account', 'page_name=stored_cards&error_message=' . urlencode("Customer ID is missing."), 'SSL'));
		
	}

}