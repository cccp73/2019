<?php

namespace Drupal\comnews\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Empty' Block.
 *
 * @Block(
 *   id = "empty_block",
 *   admin_label = @Translation("Comnews Empty block"),
 *   category = @Translation("ComNews"),
 * )
 */
class EmptyBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = array();

    $output['#markup'] = 'Empty';  

    return $output;
  }

}