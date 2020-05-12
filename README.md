# merchant-api
Library for using Paytrail Merchant API services

## Installation

```
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

## Creating refund

```php
$paymentId = 100000000000
$customerEmail = 'customer@nomail.com';
$notifyUrl = 'https://url/to/shop/notification';

$products = [
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

$merchantApi->createRefund($paymentId, $products, $notifyUrl, $customerEmail);
```
Response is sent to `$notifyUrl`, so you need to catch it there.

Both `$notifyUrl` and `$customerEmail` are optional parameters, or can be sent as `null`.

## Cancelling refund

```php
$merchantApi->cancelRefund($refundToken);
```

## Getting refund details

```php
$merchantApi->getRefundDetails($refundId);
```

## Getting settlements

Get settlements between two dates.

```php
$merchantApi->getSettlements($fromDate, $toDate));
```

If `$toDate` is not set, it defaults to current date.

Dates should be in `Y-m-d` format.

## Getting settlement details

```php
$merchantApi->getSettlementDetails($settlementId);
```

## Getting payment details

```php
$merchantApi->getPaymentDetails($paymentId);
```