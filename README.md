# ValorPayTech Payment Module for OsCommerce v4

![GitHub tag (latest by date)](https://img.shields.io/github/v/tag/ValorPay/plugin-oscommerce?label=stable) ![GitHub](https://img.shields.io/github/license/ValorPay/plugin-oscommerce?color=brightgreen)

This is a Payment Module for OsCommerce v4, that gives you the ability to process payments through payment service providers running on ValorPayTech platform.

## Requirements

  * OsCommerce v4
  * PHP Versions >= 7.0.0  ![GitHub](https://img.shields.io/badge/php-%3E%3D7.0.0-lightgrey)

*Note:* this module has been tested only with OsCommerce v4+.

## Installation (App Shop / Local Storage)

  * [Download the Payment Module archive](https://github.com/ValorPay/plugin-oscommerce/archive/refs/heads/main.zip), unpack it.
  
  * Navigate to ```plugin-oscommerce-main\plugin-oscommerce-main``` folder then pack its contents and give zip file name ```plugin-oscommerce.zip``` 
  
  * Upload ```plugin-oscommerce.zip``` to a ```App Shop / Local Storage```

  * Click + sign in Action column to Install module from there.

## Installation (Manual)

  * [Download the Payment Module archive](https://github.com/ValorPay/plugin-oscommerce/archive/refs/heads/main.zip), unpack it.
  
  * Navigate project folder ```lib\common\modules\orderPayment``` and upload unpack contents from ```plugin-oscommerce-main\plugin-oscommerce-main``` there.

  * Move file ```ot_valorpay.php``` from ```lib\common\modules\orderPayment``` to  ```lib\common\modules\orderTotal``` 

## Deletion 

  * Go to ```App Shop / Local Storage``` delete module from there

  * For Manual Navigate folder ```lib\common\modules\orderPayment``` delete valorpay folder and file from there

  * Go to ```lib\common\modules\orderTotal``` delete ot_valorpay.php from there. 

## Configuration

  * Login inside the __Admin Panel__ and go to ```Modules``` -> ```Payment``` -> ```Online```
  * Check the Payment Module Panel ```ValorPay``` is visible in the list of installed Payment Methods,
    apply filter Show not installed, if for In-Active module use Show inactive filter.
  * Click to ```ValorPay Payment Methods``` and click the button ```Edit``` under the right side panel to expand the available settings
  * Set ```Enable ValorPos``` to ```Yes```, set the correct credentials, select your prefered payment method and additional settings and click ```Update```

  #### Enable SurchargeFee
  * Next go to ```Modules``` -> ```Order structure```
  * Check the Module ```SurchargeFee``` is visible in the list of not installed, install it.
  * Click to ```SurchargeFee``` and click the button ```Edit``` under the right side panel to expand the available settings
  * Set ```Display Surcharge Fee``` to ```Yes```, Sort Order and click ```Update```
  * Drag ```SurchargeFee``` above the Total Module so that surcharge fee if enable must be calculated under grand total. 

## Test data

If you setup the module with default values, you can use the test data to make a test payment:

  * API Id ```rPWqbGUwUOH37S2IeLa8GYu9tK3K7jNY```
  * API Key ```LjTjMu6Asd6ZfNgnQRIBOr54UFYKF6Pi```
  * EPI ```2235560406```
  * Use Sandbox ``Yes``

### Test card details

Use the following test cards to make successful test payment:

  Test Cards:

    * Visa - 4012881888818888- CVV 999
    * Master- 5146315000000055- CVV 998
    * Amex- 371449635392376 -CVV 9997
    * Discover- 6011000993026909-  CVV 996
    * Diners - 3055155515160018 -CVV 996
    * Jcb - 3530142019945859 -cVV 996
    * Visa-4111 1111 1111 1111 -CVV 999
    * MAESTRO-5044 3393 2466 1725 266 -CVV 998

    Expiry Date - 12/25
    Street Address - 8320
    Zip - 85284

  * AVS (Address Verification Service): Zip or Address or Both