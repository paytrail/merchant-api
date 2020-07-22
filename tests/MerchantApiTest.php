<?php

declare(strict_types=1);

namespace tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use Paytrail\MerchantApi\Merchant;
use Paytrail\MerchantApi\MerchantApi;
use PHPUnit\Framework\TestCase;

class MerchantApiTest extends TestCase
{
    public function getMerchantApi(MockHandler $mock)
    {
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $merchant = Merchant::create('13466', '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ');
        return new MerchantApi($merchant, $client);
    }

    public function testFailedMessagesGetsParsedProperly()
    {
        $error = new \StdClass();
        $error->title = 'Error';
        $error->description = 'This is Error';
        $error->workaround = 'Foobar';

        $apiResponse = new \StdClass();
        $apiResponse->error = $error;

        $responseBody = json_encode($apiResponse);
        $mock = new MockHandler([
            new Response(400, [], $responseBody),
        ]);

        $expectedMessage = 'Error: This is Error, Foobar';

        $merchantApi = $this->getMerchantApi($mock);
        $response = $merchantApi->cancelRefund('abc');

        $this->assertSame($expectedMessage, $response->getError());
        $this->assertFalse($response->isSuccess());
    }

    public function testErrorsWithoutContentGetsParsedProperly()
    {
        $mock = new MockHandler([
            new RequestException('Foobar', new Request('POST', 'Error')),
        ]);

        $merchantApi = $this->getMerchantApi($mock);
        $response = $merchantApi->getPayments('123');

        $this->assertSame('Foobar', $response->getError());
        $this->assertFalse($response->isSuccess());
    }

    public function testRefundTokenCanBeExtractedFromSuccessMessage()
    {
        $refundToken = hash('md5', microtime());

        $responseHeaders = [
            'Location' => 'https://api.paytrail.com/payment/asdasdasd/' . $refundToken,
        ];

        $mock = new MockHandler([
            new Response(202, $responseHeaders),
        ]);
        $merchantApi = $this->getMerchantApi($mock);

        $refundRows = [
            [
                'title' => 'foo',
                'amount' => 10,
            ],
        ];

        $response = $merchantApi->createRefund(1234, $refundRows, 'foo@bar.com', 'https://notifyUrl.com');

        $this->assertSame($refundToken, $response->getContent());
        $this->assertTrue($response->isSuccess());
    }
}
