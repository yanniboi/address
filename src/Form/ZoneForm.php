<?php

/**
 * @file
 * Contains \Drupal\address\Form\ZoneForm.
 */

namespace Drupal\address\Form;

use Drupal\address\ZoneMemberManager;
use Drupal\address\Entity\Zone;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ZoneForm extends EntityForm {

  /**
   * The zone storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $zoneStorage;

  /**
   * The zone member plugin manager.
   *
   * @var \Drupal\address\ZoneMemberManager
   */
  protected $zoneMemberManager;

  /**
   * Creates a ZoneForm instance.
   *
   * @param \Drupal\Core\Entity\EntityManager $entityManager
   *   The entity manager.
   * @param \Drupal\address\ZoneMemberManager $zone_member_manager
   *   The zone member plugin manager.
   */
  public function __construct(EntityManager $entityManager, ZoneMemberManager $zone_member_manager) {
    $this->zoneStorage = $entityManager->getStorage('zone');
    $this->zoneMemberManager = $zone_member_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.address.zone_member')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $formState) {
    $input = $formState->getUserInput();
    $zone = $this->entity;

    $form['#tree'] = TRUE;
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $zone->getName(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $zone->getId(),
      '#machine_name' => [
        'exists' => '\Drupal\address\Entity\Zone::load',
        'source' => ['name'],
      ],
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['scope'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scope'),
      '#description' => t('Used to group zones by purpose. Examples: tax, shipping.'),
      '#default_value' => $zone->getScope(),
      '#maxlength' => 255,
    ];
    $form['priority'] = [
      '#type' => 'weight',
      '#title' => $this->t('Priority'),
      '#description' => $this->t('Zones with a higher priority will be matched first.'),
      '#default_value' => (int) $zone->getPriority(),
      '#delta' => 10,
    ];

    return parent::form($form, $formState);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $formState) {
    $this->entity->save();
    drupal_set_message($this->t('Saved the %label zone.', [
      '%label' => $this->entity->label(),
    ]));
    $formState->setRedirect('entity.zone.collection');
  }

}
