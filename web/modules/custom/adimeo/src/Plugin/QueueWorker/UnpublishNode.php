<?php

namespace Drupal\adimeo\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\Entity\Node;

/**
 * Defines 'adimeo_unpublish_node' queue worker.
 *
 * @QueueWorker(
 *   id = "adimeo_unpublish_node",
 *   title = @Translation("Unpublish node"),
 *   cron = {"time" = 60}
 * )
 */
class UnpublishNode extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Load node and check if it exists.
    if ($node = Node::load($data)) {
      // Unpublish node.
      $node->set('status', 0);
      $node->save();
    }
  }

}
