<?php

namespace Drupal\adimeo\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;

/**
 * Provides a next events block.
 *
 * @Block(
 *   id = "adimeo_next_events",
 *   admin_label = @Translation("Next events"),
 *   category = @Translation("Adimeo")
 * )
 */
class NextEventsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get current route.
    $route_name = \Drupal::routeMatch()->getRouteName();
    // If current route is not node view page, return empty array.
    if ($route_name != 'entity.node.canonical') {
      return [];
    }

    // Get current node.
    /** @var Node $node */
    $node = \Drupal::routeMatch()->getParameter('node');
    // If current node is not an event, return empty array.
    if ($node->bundle() != 'event') {
      return [];
    }

    // Get next events from today having the same event type.
    $nb_events_max = 3;
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'event')
      ->condition('status', 1)
      ->condition('nid', $node->id(), '<>')
      ->condition('field_event_type', $node->get('field_event_type')->target_id)
      ->condition('field_date_range', date('Y-m-d'), '>')
      ->sort('field_date_range', 'ASC')
      ->range(0, $nb_events_max)
      ->accessCheck();

    $nids = $query->execute();

    // If number of events is less than $nb_events_max, add next events having any event type except current event type.
    if (count($nids) < $nb_events_max) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'event')
        ->condition('status', 1)
        ->condition('nid', $node->id(), '<>')
        ->condition('field_event_type', $node->get('field_event_type')->target_id, '<>')
        ->condition('field_date_range', date('Y-m-d'), '>')
        ->sort('field_date_range', 'ASC')
        ->range(0, $nb_events_max - count($nids))
        ->accessCheck();

      $nids = array_merge($nids, $query->execute());
    }

    // If there are no events, return empty array.
    if (empty($nids)) {
      return [];
    }

    // Render next events in view mode teaser.
    $build = [];
    $build['next_events'] = [
      '#theme' => 'item_list',
      '#items' => [],
    ];
    foreach ($nids as $nid) {
      $build['next_events']['#items'][] = \Drupal::entityTypeManager()
        ->getViewBuilder('node')
        ->view(Node::load($nid), 'teaser');
    }

    // Add cache tags to react to event creation or update.
    $build['#cache']['tags'][] = 'node_list:event';
    // Add cache max-age to 6 hours if no event is created or updated.
    $build['#cache']['max-age'] = 21600;
    // Add cache contexts to have a different cache for each path.
    $build['#cache']['contexts'][] = 'url.path';

    return $build;
  }

}
