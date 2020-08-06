<?php

declare(strict_types=1);

namespace Paytrail\MerchantApi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Paytrail <tech@paytrail.com>
 */
class MerchantApi
{
    private const DEFAULT_API_URL = 'https://api.paytrail.com';

    private const METHOD_POST = 'POST';
    private const METHOD_GET = 'GET';
    private const METHOD_DELETE = 'DELETE';

    private const PAYMENT_ENDPOINT = '/merchant/v1/payments';
    private const REFUND_ENDPOINT = '/merchant/v1/refunds';
    private const SETTLEMENT_ENDPOINT = '/merchant/v1/settlements';

    private $client;
    private $merchant;
    private $apiUrl;

    public function __construct(Merchant $merchant, Client $client = null)
    {
        $this->merchant = $merchant;
        $this->client = $client ?? new Client();
        $this->apiUrl = self::DEFAULT_API_URL;
    }

    /**
     * Create refund, success call will result refund token in header location.
     *
     * @param int $paymentId
     * @param array $rows
     * @param string $email
     * @param string|null $notifyUrl
     * @return Response
     */
    public function createRefund(int $paymentId, array $rows, string $email, ?string $notifyUrl = null): Response
    {
        $content = [
            'rows' => $rows,
            'email' => $email,
        ];

        $content['notifyUrl'] = $notifyUrl ?? null;

        $headers = [
            'Refund-Origin' => 'internal',
        ];

        $endpoint = self::PAYMENT_ENDPOINT . "/{$paymentId}/refunds";
        $response = $this->sendRequest($endpoint, self::METHOD_POST, json_encode($content), $headers);

        if (!is_object($response)) {
            return new Failed($response);
        }

        if ($response->getStatusCode() !== 202) {
            return new Failed($this->getFailedMessage($response));
        }

        $location = $response->getHeaders()['Location'][0];
        $locationParts = explode('/', $location);

        // Refund token is last part of location
        return new Success(end($locationParts));
    }

    /**
     * Cancel refund, success call will return nothing, so use refund token as content to indicate cancelled refund.
     *
     * @param string $refundToken
     * @return Response
     */
    public function cancelRefund(string $refundToken): Response
    {
        $endpoint = self::REFUND_ENDPOINT . "/{$refundToken}";
        $response =  $this->sendRequest($endpoint, self::METHOD_DELETE);

        return $this->handleResponse($response, 204);
    }

    /**
     * Get refund details.
     *
     * @param string $refundToken
     * @return Response
     */
    public function getRefundDetails(string $refundToken): Response
    {
        $endpoint = self::REFUND_ENDPOINT . "/{$refundToken}";
        $response = $this->sendRequest($endpoint, self::METHOD_GET);

        return $this->handleResponse($response);
    }

    /**
     * Get settlements between two dates. If end date is not specified, use current date.
     *
     * @param string $fromDate
     * @param string|null $toDate
     * @return Response
     */
    public function getSettlements(string $fromDate, ?string $toDate = null): Response
    {
        $toDate = $toDate ?? date('Y-m-d');
        $endpoint = self::SETTLEMENT_ENDPOINT . "?fromDate={$fromDate}&toDate={$toDate}";
        $response = $this->sendRequest($endpoint, self::METHOD_GET);

        return $this->handleResponse($response);
    }

    /**
     * Get details from settlement.
     *
     * @param string $settlementId
     * @return Response
     */
    public function getSettlementDetails(string $settlementId): Response
    {
        $endpoint = self::SETTLEMENT_ENDPOINT . "/{$settlementId}";
        $response = $this->sendRequest($endpoint, self::METHOD_GET);

        return $this->handleResponse($response);
    }

    /**
     * Get payment details.
     *
     * @param string $paymentId
     * @return Response
     */
    public function getPaymentDetails(string $paymentId): Response
    {
        $endpoint = self::PAYMENT_ENDPOINT . "/{$paymentId}";
        $response = $this->sendRequest($endpoint, self::METHOD_GET);

        return $this->handleResponse($response);
    }

    /**
     * Get payments by order number.
     *
     * @param string $orderNumber
     * @return Response
     */
    public function getPayments(string $orderNumber): Response
    {
        $endpoint = self::PAYMENT_ENDPOINT . "?order_number={$orderNumber}";
        $response = $this->sendRequest($endpoint, self::METHOD_GET);

        return $this->handleResponse($response);
    }

    /**
     * Get timestamp for API request.
     * @return string
     */
    private function getTimestamp(): string
    {
        return (new \DateTime())->format(\DateTimeInterface::RFC3339);
    }

    /**
     * Calculate API authorization hash.
     *
     * @param string $method
     * @param string $endpoint
     * @param string $timestamp
     * @param string $contentMd5
     * @return string
     */
    private function getAuthorizationHash(string $method, string $endpoint, string $timestamp, string $contentMd5): string
    {
        return  base64_encode(hash_hmac('sha256', implode("\n", [
            $method,
            $endpoint,
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
     * @param string $endpoint
     * @param string $method
     * @param string|null $content
     * @param array $headers
     * @return ResponseInterface|string
     */
    private function sendRequest(string $endpoint, string $method, ?string $content = '', array $headers = [])
    {
        $timestamp = $this->getTimestamp();
        $contentMd5 = $this->getContentMd5($content);
        $hash = $this->getAuthorizationHash($method, $endpoint, $timestamp, $contentMd5);

        $requestContent = [
            'headers' => [
                'Timestamp' => $timestamp,
                'Content-MD5' => $contentMd5,
                'Authorization' => 'PaytrailMerchantAPI ' . $this->merchant->id . ':' . $hash,
            ],
        ];

        $requestContent['headers'] = array_merge($requestContent['headers'], $headers);

        $requestContent['body'] = $content;

        try {
            return $this->client->request($method, $this->getApiUrl() . $endpoint, $requestContent);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $e->getResponse();
            }
            return $e->getMessage();
        }
    }

    /**
     * @param ResponseInterface|string $response
     * @param int $acceptedStatusCode
     * @return Response
     */
    private function handleResponse($response, int $acceptedStatusCode = 200): Response
    {
        if (!is_object($response)) {
            return new Failed($response);
        }

        if ($response->getStatusCode() !== $acceptedStatusCode) {
            return new Failed($this->getFailedMessage($response));
        }

        return new Success($response->getBody()->getContents());
    }

    /**
     * Parse message from failed response content.
     *
     * @param ResponseInterface $response
     * @return string
     */
    private function getFailedMessage(ResponseInterface $response): string
    {
        $content = $response->getBody()->getContents();
        $error = json_decode($content)->error;

        return "{$error->title}: {$error->description}, {$error->workaround}";
    }

    /**
     * Get Merchant API URL url.
     *
     * @return string
     */
    private function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * Set Merchant API URL.
     *
     * @param string $apiUrl
     * @return void
     * @internal This function is used only in internal test
     */
    public function setApiUrl(string $apiUrl): void
    {
        $this->apiUrl = $apiUrl;
    }
}
