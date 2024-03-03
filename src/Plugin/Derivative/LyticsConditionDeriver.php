<?php

namespace Drupal\smart_content_lytics\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides condition plugin definitions for Lytics fields.
 *
 * @see Drupal\smart_content_lytics\Plugin\smart_content\Condition\LyticsCondition
 */
class LyticsConditionDeriver extends DeriverBase implements ContainerDeriverInterface
{
  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * LyticsConditionDeriver constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerInterface $logger)
  {
    $this->configFactory = $configFactory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id)
  {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.channel.default')
    );
  }

  // Custom sorting function to sort fields by label instead of key name.
  public static function sortByLabel($a, $b)
  {
    return strcmp($a['label'], $b['label']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition)
  {
    $this->derivatives = [];
    $lyticsFields = $this->getAvailableSchemaAttributes();
    // ksort($lyticsFields);
    uasort($lyticsFields, [static::class, 'sortByLabel']);
    foreach ($lyticsFields as $key => $lyticsField) {
      $this->derivatives[$key] = $lyticsField + $base_plugin_definition;
    }
    return $this->derivatives;
  }

  /**
   * Function to return a static list of all available Lytics profile fields.
   *
   * @return array
   *   An array of fields from Lytics.
   */
  protected function getAvailableSchemaAttributes()
  {
    $lyticsConfig = $this->configFactory->get('lytics.settings');
    $apiToken = $lyticsConfig->get('apitoken');
    $allowedFieldsEndpoint = 'https://api.lytics.io/api/account/setting/api_whitelist_fields';
    $fullSchemaEndpoint = 'https://api.lytics.io/v2/schema/user/field';
    $headers = [
      'Authorization' => $apiToken,
    ];

    // Make a GET request to the Lytics Schema API to get all fields.
    $response = \Drupal::httpClient()->get($fullSchemaEndpoint, ['headers' => $headers]);
    $schemaData = json_decode($response->getBody(), TRUE);
    $allLyticsFields = $schemaData['data'];

    // Make a GET request to determine the fields that are surfaced in the schema.
    $response = \Drupal::httpClient()->get($allowedFieldsEndpoint, ['headers' => $headers]);
    $accountData = json_decode($response->getBody(), TRUE);
    $surfacedFields = $accountData['data']['value'];

    // Only surface fields which have been allowed by the Lytics API.
    $output = [];
    foreach ($allLyticsFields as $field) {
      if (in_array($field['id'], $surfacedFields)) {
        $output[$field['id']] = [
          'label' => $field['shortdesc'],
          'type'  => $this->mapType($field['type']),
        ];
      }
    }

    // Log count of fields surfaced.
    $this->logger->debug('Lytics found ' . count($allLyticsFields) . ' total fields, ' . count($surfacedFields) . ' allowed fields, and ' . count($output) . ' matched fields');

    return $output;
  }

  /**
   * Internal function used to map Lytics data types to Smart Content types.
   *
   * @param string $type
   *   The Lytics data type.
   *
   * @return string
   *   The mapped Smart Content type.
   */
  protected function mapType($type)
  {
    $map = [
      'int' => 'number',
      'string' => 'textfield',
      'number' => 'number',
      'bool' => 'boolean',
      'ts[]string' => 'array_textfield',
      '[]time' => 'array_textfield',
      'date' => 'textfield',
      '[]string' => 'array_textfield',
      'map[string]number' => 'key_value',
      'map[string]int' => 'key_value',
      'map[string]bool' => 'key_value',
      'map[string]value' => 'key_value',
      'map[string]intsum' => 'key_value',
      'map[string]time' => 'key_value',
      'map[string]string' => 'key_value',
    ];

    return $map[strtolower($type)] ?? 'array_textfield';
  }
}
