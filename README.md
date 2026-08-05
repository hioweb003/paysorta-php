# Paysorta PHP

PHP SDK for [Paysorta](https://paysorta.com) — initiate payments, generate hosted checkout links, and verify transactions.

## Installation

```bash
composer require hiotech/paysorta-php
```

## Requirements

- PHP `^8.0` – `^8.6`


## Quick start

```php
use Hiotech\PaysortaPhp\Payment;

$payment = new Payment();

$response = $payment->redirectToCheckout($secretkey,[
    'firstName' => 'john',
    'lastName'  => 'Doe',
    'email'  => 'customer@example.com',
    'amount' => 1000,
    'currency'     => 'NGN',
    'reference'    => 'ORDER-123',             
    'callBackURL' => 'https://example.com/callback',  
    'metadata'     => ['order_id' => 123]
]);
```

## Usage

### Redirect straight to checkout

```php
$response = $payment->redirectToCheckout($secretkey,[
    'firstName' => 'john',
    'lastName'  => 'Doe',
    'email'        => 'customer@example.com', // required
    'amount'       => 1000,                   // required, numeric minimum 1
    'currency'     => 'NGN',                  // optional, defaults to NGN
    'reference'    => 'ORDER-123',             
    'callBackURL' => 'https://example.com/callback', // optional
    'metadata'     => ['order_id' => 123]    // optional
]);
```


### Verify a transaction

After the customer returns from checkout, verify the payment by its reference:

```php
$result = $payment->verifyPaymentCheckout($secretkey,'PSA_7cdc78f8d732fdb9');
```


## License

MIT
