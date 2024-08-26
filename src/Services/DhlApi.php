<?php

namespace Drupal\dhl_location_finder\Services;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide DhlApi service to get locations from dhlapi.
 */
class DhlApi
{
    /**
     * The HTTP client.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    /**
     * Constructor for LocationFinder.
     *
     * @param \GuzzleHttp\ClientInterface $http_client
     *   A Guzzle client object.
     */
    public function __construct(ClientInterface $http_client)
    {
        $this->httpClient = $http_client;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('http_client'),
        );
    }

    /**
     * Get locations from locations from DHL finder API.
     *
     * @param string $api_key
     *   A DHL API key.
     * @param array  $query
     *   An array containing countrycode, postalcode, city.
     *
     * @return array
     *   locations as result from dhl location finder api
     */
    public function getLocations($api_key, $query)
    {
        $apiEndpoint = 'https://api.dhl.com/location-finder/v1/find-by-address';
        $request = $this->httpClient->request(
            'GET',
            $apiEndpoint,
            [
            'query' => $query,
            'headers' => [
            'DHL-API-Key' => $api_key,
            ],
            ]
        );
        $response = $request->getBody()->getContents();
        $locationsResults = Json::decode($response);
        $locations = [];
        foreach ($locationsResults['locations'] as $loactionResult) {
            $locationId = substr($loactionResult['url'], 11);
            if (strpos($locationId, "-")) {
                $locationRealID = explode("-", $locationId);
                $locationId = $locationRealID[1];
            }
            // Only process if location id is even.
            if (strlen($locationId) % 2 == 0) {
                $opningHours = [];
                foreach ($loactionResult['openingHours'] as $dayOfWeek) {
                    $day = substr($dayOfWeek['dayOfWeek'], 18);
                    $opningHours[$day] = $dayOfWeek['opens'] . ' - ' . $dayOfWeek['closes'];
                }
                // Only process if weekends are working.
                if (isset($opningHours['Sunday']) && isset($opningHours['Saturday'])) {
                    $locations[] = [
                    'locationName' => $loactionResult['name'],
                    'address' => $loactionResult['place']['address'],
                    'openingHours' => $opningHours,
                    ];
                }
            }
        }
        \Drupal::logger('locations')->warning('<pre><code>' . print_r($locations, true) . '</code></pre>');
        return $locations;
    }
}
