<?php

namespace Drupal\kuntaliitto_feeds;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Feed configuration entities.
 */
class FeedConfigEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Feed configuration');
    $header['id'] = $this->t('Machine name');
    $header['feed_status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * @param \Drupal\kuntaliitto_feeds\Entity\FeedConfigEntity $entity
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['feed_status'] = $entity->get('feed_status') ? 'Enabled' : 'Disabled';
    return $row + parent::buildRow($entity);
  }

}
