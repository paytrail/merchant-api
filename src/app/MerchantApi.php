<?php

declare(strict_types=1);

namespace Paytrail\MerchantApi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;

/**
 * @author Paytrail <tech@paytrail.com>
 */
class MerchantApi
{
    const API_URL = 'https://api.paytrail.com';

    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    const METHOD_DELETE = 'DELETE';

    private $client;
    private $merchant;

    public function __construct(Merchant $merchant)
    {
        $this->merchant = $merchant;
        $this->client = new Client();
    }

    public function createRefund(int $paymentId, array $products, ?string $notifyUrl = null, ?string $email = null)
    {
        $content = [
            'rows' => $products,
        ];

        if ($email !== null) {
            $content['email'] = $email;
        }

        if ($notifyUrl !== null) {
            $content['notifyUrl'] = $notifyUrl;
        }

        $jsonContent = json_encode($content);

        $url = '/merchant/v1/payments/' . $paymentId . '/refunds';
        return $this->sendRequest($url, self::METHOD_POST, $jsonContent);
    }

    public function cancelRefund(string $refundToken)
    {
        $url = '/merchant/v1/refunds/' . $refundToken;
        return $this->sendRequest($url, self::METHOD_DELETE);
    }

    public function getRefundDetails(string $refundId)
    {
        $url = '/merchant/v1/refunds/' . $refundId;
        return $this->sendRequest($url, self::METHOD_GET);
    }

    public function getSettlements(string $fromDate, ?string $toDate = null)
    {
        $toDate = $toDate ?? date('Y-m-d');
        $url = '/merchant/v1/settlements?fromDate=' . $fromDate . '&toDate=' . $toDate;
        return $this->sendRequest($url, self::METHOD_GET);
    }

    public function getSettlementDetails(string $settlementId)
    {
        $url = '/merchant/v1/settlements/' . $settlementId;
        return $this->sendRequest($url, self::METHOD_GET);
    }

    public function getPaymentDetails(string $paymentId)
    {
        $url = '/merchant/v1/payments/' . $paymentId;
        return $this->sendRequest($url, self::METHOD_GET);
    }

    private function getTimestamp(): string
    {
        return (new \DateTime())->format(\DateTimeInterface::RFC3339);
    }

    private function getAuthorizationHash(string $method, string $url, string $timestamp, string $contentMd5): string
    {
        return  base64_encode(hash_hmac('sha256', implode("\n", [
            $method,
            $url,
            'PaytrailMerchantAPI ' . $this->merchant->id,
            $timestamp,
            $contentMd5,
        ]), $this->merchant->secret, true));
    }

    private function getContentMd5(string $content): string
    {
        return base64_encode(hash('md5', $content, true));
    }

    /**
     * Send request to Paytrail rest api.
     *
     * @param string $content
     *
     * @return Response
     */
    private function sendRequest(string $url, string $method, ?string $content = null): ?Response
    {
        $timestamp = $this->getTimestamp();
        $contentMd5 = $this->getContentMd5($content);
        $hash = $this->getAuthorizationHash($method, $url, $timestamp, $contentMd5);

        $requestContent = [
            'headers' => [
                'Timestamp' => $timestamp,
                'Content-MD5' => $contentMd5,
                'Authorization' => 'PaytrailMerchantAPI ' . $this->merchant->id . ':' . $hash,
                'Refund-Origin' => 'internal',
            ],
        ];

        if ($content !== null) {
            $requestContent['body'] = $content;
        }

        try {
            return $this->client->request($method, self::API_URL . $url, $requestContent);
        } catch (ClientException $e) {
            return $e->getResponse();
        } catch (ConnectException $e) {
            var_dump($e);
            return null;
        }
    }
}
