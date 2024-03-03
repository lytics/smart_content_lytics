<?php

namespace Drupal\smart_content_lytics\Plugin\smart_content\Condition;

use Drupal\smart_content\Condition\ConditionTypeConfigurableBase;

/**
 * Provides a Lytics condition plugin.
 *
 * @SmartCondition(
 *   id = "lytics",
 *   label = @Translation("Lytics"),
 *   group = "lytics",
 *   deriver = "Drupal\smart_content_lytics\Plugin\Derivative\LyticsConditionDeriver"
 * )
 */
class LyticsCondition extends ConditionTypeConfigurableBase
{

  /**
   * {@inheritdoc}
   */
  public function getLibraries()
  {
    $libraries = array_unique(array_merge(parent::getLibraries(), ['smart_content_lytics/condition.lytics']));
    return $libraries;
  }
}
