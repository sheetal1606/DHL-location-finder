<?php

namespace Drupal\dhl_location_finder\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\dhl_location_finder\Services\DhlApi;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for find_location.
 */
class LocationFinder extends ControllerBase implements ContainerInjectionInterface
{
    /**
     * The Drupal formBuilder.
     *
     * @var \Drupal\Core\Form\FormBuilderInterface
     */
    protected $formBuilder;

    /**
     * The HTTP client.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    /**
     * DHL api key.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $config;

    /**
     * DhlApi.
     *
     * @var \Drupal\dhl_location_finder\Services\DhlApi
     */
    protected $dhlApi;

    /**
     * Constructor for LocationFinder.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface  $config
     *   A ConfigFactoryInterface object.
     * @param \GuzzleHttp\ClientInterface                 $http_client
     *   A Guzzle client object.
     * @param \Drupal\Core\Form\FormBuilderInterface      $formBuilder
     *   A FormBuilderInterface object.
     * @param \Drupal\dhl_location_finder\Services\DhlApi $dhlApi
     *   The DhlApi service.
     */
    public function __construct(
        ConfigFactoryInterface $config,
        ClientInterface $http_client,
        FormBuilderInterface $formBuilder,
        DhlApi $dhlApi
    ) {
        $this->httpClient = $http_client;
        $this->formBuilder = $formBuilder;
        $this->config = $config->get('dhl_location_finder.settings');
        $this->dhlApi = $dhlApi;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('config.factory'),
            $container->get('http_client'),
            $container->get('form_builder'),
            $container->get('dhl_location_finder.dhl_api'),
        );
    }

    /**
     * Returns location finder form and locations result.
     *
     * @return array
     *   result from dhl location finder api
     */
    public function showLocations()
    {
        $api_key = $this->config->get('api_key');
        if ($api_key == null || $api_key == '') {
            $config_url = Url::fromRoute('dhl_location_finder.settings');
            return [
            '#markup' => $this->t(
                "Please add you DHL api key at <a href=':url'>DHL api configuration</a> to access this application. 
        If you do not have access to given link please contact administrator.",
                [
                ':url' => $config_url->toString(),
                ]
            ),
            ];
        }
        $searchform = $this->formBuilder()->getForm('Drupal\dhl_location_finder\Form\LocationFinderForm');
        $build['form'] = $searchform;
        $searchFormContainer = $searchform['container'];

        $country = '';
        $city = '';
        $post_code = '';


        // Set form submitted values.
        if (isset($searchFormContainer['country']['#value']) && $searchFormContainer['country']['#value'] != null) {
            $country = $searchFormContainer['country']['#value'];
        }
        if (isset($searchFormContainer['city']['#value']) && $searchFormContainer['city']['#value'] != null) {
            $city = $searchFormContainer['city']['#value'];
        }
        if (isset($searchFormContainer['post_code']['#value']) && $searchFormContainer['post_code']['#value'] != null) {
            $post_code = $searchFormContainer['post_code']['#value'];
        }

        // Check if values are set if yes then call api.
        if ($country != '' && $city != '' && $post_code != '') {
            // Code to get country iso2 code.
            $countryApi = 'https://public.opendatasoft.com/api/explore/v2.1/catalog/datasets/countries-codes/records';
            $countryQuery = [
            'select' => 'iso2_code',
            'where' => 'label_en like "' . $country . '"',
            'limit' => 1,
            ];
            try {
                $request = $this->httpClient->request('GET', $countryApi, ['query' => $countryQuery]);
                $response = $request->getBody()->getContents();
                $countryCodeResult = Json::decode($response);
                if (isset($countryCodeResult['total_count']) && $countryCodeResult['total_count'] != 0) {
                    // Call DHL location finder api if we found country code.
                    $countryCode = $countryCodeResult['results'][0]['iso2_code'];
                    $query = [
                    'countryCode' => $countryCode,
                    'addressLocality' => $city,
                    'postalCode' => $post_code,
                    ];
                    try {
                        $locations = $this->dhlApi->getLocations($api_key, $query);
                        $build['result'] = [
                        '#theme' => 'dhl_locations',
                        '#locations' => $locations,
                        '#empty_value' => $this->t("No offices found for given location.
                        Please update your search parameters to update results."),
                        ];
                    } catch (ClientException | RequestException $e) {
                        $build['result'] = [
                        '#markup' => $this->t("System has encounterd an issue.
                        Please check logs for more information."),
                        ];
                        $this->getLogger('DHL location finder')->error($e->getMessage());
                    }
                } else {
                    $build['result'] = [
                    '#markup' => $this->t(
                        "System can not find country ':country'. Please check if you have entered correct country.",
                        [
                        ':country' => $country,
                        ]
                    ),
                    ];
                }
            } catch (ClientException | RequestException $e) {
                $build['result'] = [
                '#markup' => $this->t("System has encounterd an issue. Please check logs for more information."),
                ];
                $this->getLogger('DHL location finder')->error($e->getMessage());
            }
        }
        return $build;
    }
}
