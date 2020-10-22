<?php

namespace Drupal\comnews\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Footer' Block.
 *
 * @Block(
 *   id = "footer_block",
 *   admin_label = @Translation("Comnews footer block"),
 *   category = @Translation("ComNews"),
 * )
 */
class FooterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = array();

    $output['#markup'] = 'Подвал';  

    return $output;
  }

}