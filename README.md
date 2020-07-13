# merchant-api
Library for using Paytrail Merchant API services

## Installation

```bash
composer require paytrail/merchant-api
```

## Usage
Create Merchant API object

```php
use Paytrail\MerchantApi\Merchant;
use Paytrail\MerchantApi\MerchantApi;

$merchant = Merchant::create($merchantId, $merchantSecret);
$merchantApi = new MerchantApi($merchant);
```

All API calls will return Either `Success` or `Failed` object. Both of them extend `Response` object.
Response object have methods; `getContent()` and `getErrror()`.
If API call is success, `getContent()` will return response content from response itself.
On failed calls `getContent()` returns null and `getError()` return error message.
Both methods will return string.

You can check response status.
```php
$response->isSuccess();
```

## Creating refund

```php
$paymentId = 100000000000
$customerEmail = 'customer@nomail.com';
$notifyUrl = 'https://url/to/shop/notification';

$rows = [
    [
        'amount' => 1000,
        'description' => 'Some Product',
        'vatPercent' => 2400,
    ],
    [
        'amount' => 2000,
        'description' => 'Other Product',
        'vatPercent' => 2400,
    ],
];

$refundToken = $merchantApi->createRefund($paymentId, $rows, $customerEmail, $notifyUrl)->getContent();
```

Paytrail API will send response to `$notifyUrl`. This is optional, but highly recommended parameter.

Request returns refund token, which can be used to get refund details or cancel refund.

**Note:** Refunds are created with payment id. If you only have order number, but not payment id.
You can use `getPayments()` method to get payment id for order number.
If you use [E2 interface](https://docs.paytrail.com/payments/e2-interface/), you get Payment Id from return parameters after customer has completed payment.

More info about creating refunds in [documentation](https://docs.paytrail.com/refunds/create/).

## Cancelling refund

```php
$cancelledRefundToken = $merchantApi->cancelRefund($refundToken)->getContent();
```

Cancel refund by refund token. Response will return cancelled refund token.

More info about cancelling refund in [documentation](https://docs.paytrail.com/refunds/cancel/).

## Getting refund details

```php
$refundDetails = $merchantApi->getRefundDetails($refundId)->getContent();
$detailsObject = json_decode($refundDetails);
```

`$refundDetails` is JSON encoded object.

More info about refund details in [documentation](https://docs.paytrail.com/refunds/details/).

## Getting settlements

Get settlements between two dates.

```php
$settlements = $merchantApi->getSettlements($fromDate, $toDate)->getContent();
$settlementsArray = json_decode($settlements);
```

If `$toDate` is not set, it defaults to current date.
Dates must be in `Y-m-d` format.

Response is array containing settlement objects

More info about settlements in [documentation](https://docs.paytrail.com/settlements/list/).

## Getting settlement details

```php
$settlementDetails = $merchantApi->getSettlementDetails($settlementId)->getContent();
$settlementDetailsObject = json_decode($settlementDetails);
```

`$settlementId` is `id` value from settlement object in settlement details array.

More info about settlement details in [documentation](https://docs.paytrail.com/settlements/querying-settlement-details/).

## Getting payments

```php
$payments = $merchantApi->getPayments($orderNumber)->getContent();
$paymentsArray = json_decode($payments);
```

Payment array contains payment objects.

 This will return all payments with order number. Old payment interfaces won't return Payment id.
 You can use this method to get all payments by order number.
 Payment object id is `$paymentId` value used to create refunds and query payment details.
 
 More info about getting payments by order number in [documentation](https://docs.paytrail.com/settlements/payments-by-order-number/).
 
## Getting payment details

```php
$paymentDetails = $merchantApi->getPaymentDetails($paymentId)->getContent();
$paymentDetailsObject = json_decode($paymentDetails);
```

`$paymentId` is payment id used to create refund.

More info about payment details in [documentation](https://docs.paytrail.com/settlements/payment-details/).