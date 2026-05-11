<?php

namespace Picqer\BolRetailerV10\Tests;

use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Picqer\BolRetailerV10\Client;
use GuzzleHttp\Client as HttpClient;
use Picqer\BolRetailerV10\Model\AbstractModel;
use Picqer\BolRetailerV10\Model\OrderItem;

class ClientTest extends TestCase
{

    /** @var Client */
    private $client;

    /** @var HttpClient */
    private $httpClientMock;

    public function setup(): void
    {
        $this->httpClientMock = $this->createMock(HttpClient::class);
        $this->client = new Client();
        $this->client->setHttp($this->httpClientMock);

        $this->authenticateByClientCredentials();
    }

    protected function authenticateByClientCredentials()
    {
        $rawResponse = file_get_contents(__DIR__ . '/Fixtures/http/200-token');

        $response = Message::parseResponse($rawResponse);

        $httpClientMock = $this->createMock(HttpClient::class);

        $credentials = base64_encode('secret_id' . ':' . 'somesupersecretvaluethatshouldnotbeshared');
        $httpClientMock->method('request')->with('POST', 'https://login.bol.com/token', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'grant_type' => 'client_credentials',
            ],
        ])->willReturn($response);

        // use the HttpClient mock created in this method for authentication, put the original one back afterwards
        $prevHttpClient = $this->client->getHttp();
        $this->client->setHttp($httpClientMock);

        $this->client->authenticateByClientCredentials('secret_id', 'somesupersecretvaluethatshouldnotbeshared');

        $this->client->setHttp($prevHttpClient);
    }

    public function testMethodReturnsModel()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-order'));
        $this->httpClientMock->method('request')->willReturn($response);

        $order = $this->client->getOrder('test');
        $this->assertInstanceOf(AbstractModel::class, $order);
    }

    public function testMethodUnwrapsMonoFieldResponse()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-reduced-orders'));
        $this->httpClientMock->method('request')->willReturn($response);

        $reducedOrders = $this->client->getOrders();
        $this->assertIsArray($reducedOrders);
    }

    public function testMethodUnwrapsMonoFieldResponse404ToEmptyArray()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/404-not-found'));
        $clientException = new GuzzleClientException(
            'BaseClient error',
            new Request('POST', 'dummy'),
            $response
        );

        $this->httpClientMock->method('request')->willThrowException($clientException);

        $deliveryOptions = $this->client->getDeliveryOptions([]);

        $this->assertEquals([], $deliveryOptions);
    }

    public function testMethodWrapsScalarArgumentToMonoFieldRequest()
    {
        $body = null;
        $this->httpClientMock->method('request')->with('POST')
            ->willReturnCallback(function ($method, $uri, $options) use (&$body) {
                $body = $options['body'] ?? '';
                return Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/202-offers-export'));
            });

        $expectedBody = json_encode([
            'format' => 'CSV'
        ]);

        $this->client->postOfferExport('CSV');

        $this->assertEquals($expectedBody, $body);
    }

    public function testMethodWrapsArrayArgumentToMonoFieldRequest()
    {
        $body = null;
        $this->httpClientMock->method('request')->with('POST')
            ->willReturnCallback(function ($method, $uri, $options) use (&$body) {
                $body = $options['body'] ?? '';
                return Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-delivery-options'));
            });

        $orderItems = array_map(function ($id) {
            $orderItem = new OrderItem();
            $orderItem->orderItemId = $id;
            return $orderItem;
        }, ['1', '2', '3']);

        $expectedBody = json_encode([
            'orderItems' => array_map(function ($id) {
                return ['orderItemId' => $id];
            }, ['1', '2', '3'])
        ]);

        $this->client->getDeliveryOptions($orderItems);

        $this->assertEquals($expectedBody, $body);
    }

    public function testMethodWithMissingFieldDueToEmptyArrayReturnsEmptyArray()
    {
        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/200-reduced-orders-empty'));
        $this->httpClientMock->method('request')->willReturn($response);

        $reducedOrders = $this->client->getOrders();
        $this->assertEquals([], $reducedOrders);
    }

    // --- Economic Operator endpoints (v1) ---

    public function testGetEconomicOperatorSendsCorrectRequest()
    {
        $captured = $this->captureRequestAndReturnFixture('200-economic-operator');

        $operator = $this->client->getEconomicOperator('0c6573a2-a80c-48b7-a03e-d5939f1173fa');

        $this->assertEquals('GET', $captured->method);
        $this->assertStringEndsWith(
            '/retailer/economic-operator/0c6573a2-a80c-48b7-a03e-d5939f1173fa',
            $captured->url
        );
        $this->assertEquals('application/vnd.economic-operator.v1+json', $captured->options['headers']['Accept']);

        $this->assertInstanceOf(\Picqer\BolRetailerV10\Model\EconomicOperator::class, $operator);
        $this->assertEquals('0c6573a2-a80c-48b7-a03e-d5939f1173fa', $operator->id);
        $this->assertInstanceOf(\Picqer\BolRetailerV10\Model\EconomicOperatorAddress::class, $operator->address);
        $this->assertEquals('Utrecht', $operator->address->city);
        $this->assertInstanceOf(\Picqer\BolRetailerV10\Model\ContactInformation::class, $operator->contactInformation);
        $this->assertEquals(\Picqer\BolRetailerV10\Enum\EconomicOperatorStatus::VALID, $operator->status);
    }

    public function testQueryEconomicOperatorSendsCorrectRequest()
    {
        $captured = $this->captureRequestAndReturnFixture('200-economic-operators-page');

        $page = $this->client->queryEconomicOperator('Acme', 1, 25);

        $this->assertEquals('GET', $captured->method);
        $this->assertStringContainsString('/retailer/economic-operators', $captured->url);
        $this->assertEquals(
            ['name' => 'Acme', 'page' => 1, 'page-size' => 25],
            $captured->options['query']
        );
        $this->assertEquals('application/vnd.economic-operator.v1+json', $captured->options['headers']['Accept']);

        $this->assertInstanceOf(\Picqer\BolRetailerV10\Model\EconomicOperatorsPage::class, $page);
        $this->assertCount(1, $page->operators);
        $this->assertInstanceOf(\Picqer\BolRetailerV10\Model\EconomicOperator::class, $page->operators[0]);
        $this->assertInstanceOf(\Picqer\BolRetailerV10\Model\EconomicOperatorPage::class, $page->page);
        $this->assertEquals(1, $page->page->totalCount);
    }

    public function testCreateEconomicOperatorSendsCorrectRequest()
    {
        $captured = $this->captureRequestAndReturnFixture('200-economic-operator');

        $request = new \Picqer\BolRetailerV10\Model\CreateEconomicOperator();
        $request->name = 'Acme B.V.';

        $this->client->createEconomicOperator($request);

        $this->assertEquals('POST', $captured->method);
        $this->assertStringEndsWith('/retailer/economic-operator', $captured->url);
        $this->assertEquals('application/vnd.economic-operator.v1+json', $captured->options['headers']['Accept']);
        $this->assertEquals('application/vnd.economic-operator.v1+json', $captured->options['headers']['Content-Type']);
        $this->assertEquals(json_encode(['name' => 'Acme B.V.']), $captured->options['body']);
    }

    public function testUpdateEconomicOperatorSendsCorrectRequest()
    {
        $captured = $this->captureRequestAndReturnFixture('200-economic-operator');

        $request = new \Picqer\BolRetailerV10\Model\UpdateEconomicOperator();
        $request->name = 'Acme Updated';

        $this->client->updateEconomicOperator('abc-123', $request);

        $this->assertEquals('PUT', $captured->method);
        $this->assertStringEndsWith('/retailer/economic-operator/abc-123', $captured->url);
        $this->assertEquals('application/vnd.economic-operator.v1+json', $captured->options['headers']['Content-Type']);
        $this->assertEquals(json_encode(['name' => 'Acme Updated']), $captured->options['body']);
    }

    public function testDeleteEconomicOperatorSendsCorrectRequest()
    {
        $captured = $this->captureRequestAndReturnFixture('200-economic-operator');

        $this->client->deleteEconomicOperator('abc-123');

        $this->assertEquals('DELETE', $captured->method);
        $this->assertStringEndsWith('/retailer/economic-operator/abc-123', $captured->url);
        $this->assertEquals('application/vnd.economic-operator.v1+json', $captured->options['headers']['Accept']);
    }

    /**
     * Captures the next HTTP request the client makes and returns the given fixture
     * as the response. Returns a stdClass with method, url, options properties
     * that the mock callback writes into.
     */
    private function captureRequestAndReturnFixture(string $fixture): \stdClass
    {
        $captured = new \stdClass();
        $captured->method = null;
        $captured->url = null;
        $captured->options = null;

        $response = Message::parseResponse(file_get_contents(__DIR__ . '/Fixtures/http/' . $fixture));

        $this->httpClientMock->method('request')
            ->willReturnCallback(function ($method, $url, $options) use ($captured, $response) {
                $captured->method = $method;
                $captured->url = $url;
                $captured->options = $options;
                return $response;
            });

        return $captured;
    }
}
