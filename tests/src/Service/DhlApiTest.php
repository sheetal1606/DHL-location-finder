<?php

namespace Drupal\dhl_location_finder\Tests;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Drupal\dhl_location_finder\Services\DhlApi;

/**
 * Tests the DhlApi service.
 *
 * @group dhl_location_finder
 */
class DhlApiTest extends KernelTestBase
{
    /**
     * The custom service.
     *
     * @var \Drupal\dhl_location_finder\Services\DhlApi
     */
    protected $dhlApi;

    /**
     * The mock of the HTTP client.
     *
     * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpClient;

    /**
     * {@inheritdoc}
     */
    protected static $modules = ['dhl_location_finder'];

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock of the HTTP client.
        $this->mockHttpClient = $this->createMock(ClientInterface::class);

        // Replace the HTTP client service with the mock.
        $this->container->set('http_client', $this->mockHttpClient);

        // Get the custom service from the container.
        $this->dhlApi = \Drupal::service('dhl_location_finder.dhl_api');
    }

    /**
     * Test the getLocations method of DhlApi.
     */
    public function testGetLocations()
    {
        // Define a mock response body.
        $responseBody = Json::encode(
            [
            'locations' => [
            [
              'url' => '/locations/CGNQB3',
              'location' => [
                  'ids' => [
                      [
                          'locationId' => 'CGNKX5',
                          'provider' => 'express',
                      ],
                  ],
                  'keyword' => '',
                  'keywordId' => '',
                  'type' => 'locker',
              ],
              'name' => 'DHL Packstation 911',
              'distance' => 517,
              'place' => [
                  'address' => [
                      'countryCode' => 'DE',
                      'postalCode' => '53113',
                      'addressLocality' => 'Bonn',
                      'streetAddress' => 'Heinrich-Brüning-Str. 5',
                  ],
                  'geo' => [
                      'latitude' => 50.7158619,
                      'longitude' => 7.1254252,
                  ],
              ],
              'openingHours' => [
                  [
                      'opens' => '00:00:00',
                      'closes' => '23:59:00',
                      'dayOfWeek' => 'http://schema.org/Monday',
                  ],
                  [
                      'opens' => '00:00:00',
                      'closes' => '23:59:00',
                      'dayOfWeek' => 'http://schema.org/Tuesday',
                  ],
                  [
                      'opens' => '00:00:00',
                      'closes' => '23:59:00',
                      'dayOfWeek' => 'http://schema.org/Wednesday',
                  ],
                  [
                      'opens' => '00:00:00',
                      'closes' => '23:59:00',
                      'dayOfWeek' => 'http://schema.org/Thursday',
                  ],
                  [
                      'opens' => '00:00:00',
                      'closes' => '23:59:00',
                      'dayOfWeek' => 'http://schema.org/Friday',
                  ],
                  [
                      'opens' => '00:00:00',
                      'closes' => '23:59:00',
                      'dayOfWeek' => 'http://schema.org/Saturday',
                  ],
                  [
                      'opens' => '00:00:00',
                      'closes' => '23:59:00',
                      'dayOfWeek' => 'http://schema.org/Sunday',
                  ],
              ],
              'closurePeriods' => [],
              'serviceTypes' => [
                  'express:pick-up',
              ],
            ],
            ],
            ]
        );
        // Configure the mock HTTP client to return the mock response.
        $this->mockHttpClient
            ->method('request')
            ->willReturn(new Response(200, [], $responseBody));

        // Define the query parameters and API key.
        $api_key = 'demo-key';
        $query = [
        'countryCode' => 'DE',
        'addressLocality' => 'Prague',
        'postalCode' => '53113',
        ];

        // Call the method under test.
        $result = $this->dhlApi->getLocations($api_key, $query);

        // Define the expected result.
        $expected = [
        [
        'locationName' => 'DHL Packstation 911',
        'address' => [
          'countryCode' => 'DE',
          'postalCode' => '53113',
          'addressLocality' => 'Bonn',
          'streetAddress' => 'Heinrich-Brüning-Str. 5'
        ],
        'openingHours' => [
          'Monday' => '00:00:00 - 23:59:00',
          'Tuesday' => '00:00:00 - 23:59:00',
          'Wednesday' => '00:00:00 - 23:59:00',
          'Thursday' => '00:00:00 - 23:59:00',
          'Friday' => '00:00:00 - 23:59:00',
          'Saturday' => '00:00:00 - 23:59:00',
          'Sunday' => '00:00:00 - 23:59:00',
        ],
        ],
        ];

        // Assert that the result matches the expected output.
        $this->assertEquals($expected, $result, 'DhlApi::getLocations() returns the expected locations data.');
    }
}
