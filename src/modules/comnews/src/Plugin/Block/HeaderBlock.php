<?php

namespace Drupal\comnews\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Header' Block.
 *
 * @Block(
 *   id = "header_block",
 *   admin_label = @Translation("Comnews header block"),
 *   category = @Translation("ComNews"),
 * )
 */
class HeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = array();
    $out = '';

    
    $output['#markup'] = $out;  

    return $output;
  }

}