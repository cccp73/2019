<?php

namespace Drupal\comnews\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SideBar' Block.
 *
 * @Block(
 *   id = "sidebar_block",
 *   admin_label = @Translation("Comnews SideBar block"),
 *   category = @Translation("ComNews"),
 * )
 */
class SideBarBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = array();

    $output['#markup'] = 'Боковик';  

    return $output;
  }

}