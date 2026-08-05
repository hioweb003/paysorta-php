<?php

namespace Hiotech\PaysortaPhp;

use Hiotech\PaysortaPhp\Exceptions\PaysortaException;

class Payment extends Client
{
    protected string $secretkey;

    public function __construct(string $secretkey, ?string $baseUrl = null)
    {
        parent::__construct($secretkey, $baseUrl);
        $this->secretkey = $secretkey;
    }

    /**
     * Initiate a payment. Required: email, amount. Optional: currency, reference,
     * callback_url, metadata. A reference is generated when one isn't supplied.
     */
    public function initiate(array $data): array
    {
        if (!isset($data['email']) || empty($data['email'])) {
            throw new PaysortaException('email is required to initiate a payment.');
        }

        if (!isset($data['amount']) || empty($data['amount'])) {
            throw new PaysortaException('Amount is required to initiate a payment.');
        }

       if (!isset($data['amount']) || !is_numeric($data['amount'])) {
            throw new PaysortaException('Amount is required and must be numeric.');
        }

        if ((float) $data['amount'] < 1) {
            throw new PaysortaException('Invalid amount for payment — amount must be at least 1.');
        }

        $data['reference'] ??= $this->generateReference();
        $data['currency'] ??= 'NGN';

        $client = new Client($this->secretkey);

        return $client->post('/collection/process/transaction-initialization', $data);
    } 

    /**
     * Initiate a payment and return the hosted checkout URL to send the customer to.
     */
    public function getCheckoutUrl(array $data): string
    {
        $response = $this->initiate($data);
        $url = $response['data']['paymentURL'] ?? null;

        if (!$url) {
            throw new PaysortaException('Failed to process paysorta checkout URL.', 0, $response);
        }

        return $url;
    }

    /**
     * Initiate a payment and redirect the browser straight to the hosted checkout page.
     */
    public function redirectToCheckout(array $data): void
    {
        $authorizeurl = $this->getCheckoutUrl($data);

        header('Location: ' . $authorizeurl);
        exit;
    }

    /**
     * Verify a transaction by its reference after the customer returns from checkout.
     */
    public  function verifyPaymentCheckout(string $reference): array
    {
        $client = new Client($this->secretkey);
        return $client->get('/transaction-verification?ref=' . rawurlencode($reference));
    }

    protected function generateReference(): string
    {
        return 'PSA_' . bin2hex(random_bytes(8));
    }
}
