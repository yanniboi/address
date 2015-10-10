<?php

/**
 * @file
 * Contains \Drupal\address\Form\ZoneForm.
 */

namespace Drupal\address\Form;

use Drupal\address\ZoneMemberManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ZoneForm extends EntityForm {

  /**
   * The zone member plugin manager.
   *
   * @var \Drupal\address\ZoneMemberManager
   */
  protected $memberManager;

  /**
   * Creates a ZoneForm instance.
   *
   * @param \Drupal\address\ZoneMemberManager $memberManager
   *   The zone member plugin manager.
   */
  public function __construct(ZoneMemberManager $memberManager) {
    $this->memberManager = $memberManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.address.zone_member')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $formState) {
    $zone = $this->entity;
    $userInput = $formState->getUserInput();

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

    $wrapperId = Html::getUniqueId('zone-members-ajax-wrapper');
    $form['members'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Type'),
        $this->t('Zone member'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'zone-member-order-weight',
        ],
      ],
      '#weight' => 5,
      '#prefix' => '<div id="' . $wrapperId . '">',
      '#suffix' => '</div>',
    ];

    $index = 0;
    foreach ($this->entity->getMembers() as $key => $member) {
      $memberForm = &$form['members'][$index];
      $memberForm['#attributes']['class'][] = 'draggable';
      $memberForm['#weight'] = isset($userInput['members'][$index]) ? $userInput['members'][$index]['weight'] : NULL;

      $memberForm['type'] = [
        '#type' => 'markup',
        '#markup' => $member->getPluginDefinition()['name'],
      ];
      $memberParents = ['members', $index, 'form'];
      $memberFormState = $this->buildMemberFormState($memberParents, $formState);
      $memberForm['form'] = $member->buildConfigurationForm([], $memberFormState);
      $memberForm['form']['#element_validate'] = ['::memberFormValidate'];

      $memberForm['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $member->getName()]),
        '#title_display' => 'invisible',
        '#default_value' => $member->getWeight(),
        '#attributes' => [
          'class' => ['zone-member-order-weight'],
        ],
      ];
      $memberForm['remove'] = [
        '#type' => 'submit',
        '#name' => 'remove_member' . $index,
        '#value' => $this->t('Remove'),
        '#limit_validation_errors' => [],
        '#submit' => ['::removeMemberSubmit'],
        '#member_index' => $index,
        '#ajax' => [
          'callback' => '::membersAjax',
          'wrapper' => $wrapperId,
        ],
      ];

      $index++;
    }

    // Sort the members by weight. Ensures weight is preserved on ajax refresh.
    uasort($form['members'], ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);

    $plugins = [];
    foreach ($this->memberManager->getDefinitions() as $plugin => $definition) {
      $plugins[$plugin] = $definition['name'];
    }
    $form['members']['_new'] = [
      '#tree' => FALSE,
    ];
    $form['members']['_new']['type'] = [
      '#prefix' => '<div class="zone-member-new">',
      '#suffix' => '</div>',
    ];
    $form['members']['_new']['type']['plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Zone member type'),
      '#title_display' => 'invisible',
      '#options' => $plugins,
      '#empty_value' => '',
    ];
    $form['members']['_new']['type']['add_member'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#validate' => ['::addMemberValidate'],
      '#submit' => ['::addMemberSubmit'],
      '#ajax' => [
        'callback' => '::membersAjax',
        'wrapper' => $wrapperId,
      ],
    ];
    $form['members']['_new']['member'] = [
      'data' => [],
    ];
    $form['members']['_new']['operations'] = [
      'data' => [],
    ];

    return parent::form($form, $formState);
  }

  /**
   * Ajax callback for member operations.
   */
  public function membersAjax(array $form, FormStateInterface $formState) {
    return $form['members'];
  }

  /**
   * Validation callback for adding a zone member.
   */
  public function addMemberValidate(array $form, FormStateInterface $formState) {
    if (!$formState->getValue('plugin')) {
      $formState->setErrorByName('plugin', $this->t('Select a zone member type to add.'));
    }
  }

  /**
   * Submit callback for adding a zone member.
   */
  public function addMemberSubmit(array $form, FormStateInterface $formState) {
    $pluginId = $formState->getValue('plugin');
    $member = $this->memberManager->createInstance($pluginId);
    $this->entity->addMember($member);
    $formState->setRebuild();
  }

  /**
   * Submit callback for removing a zone member.
   */
  public function removeMemberSubmit(array $form, FormStateInterface $formState) {
    $memberIndex = $formState->getTriggeringElement()['#member_index'];
    $member = $form['members'][$memberIndex]['form']['#member'];
    $this->entity->removeMember($member);
    $formState->setRebuild();
  }

  /**
   * Validation callback for the embedded zone member form.
   */
  public function memberFormValidate($memberForm, FormStateInterface $formState) {
    $member = $memberForm['#member'];
    $memberFormState = $this->buildMemberFormState($memberForm['#parents'], $formState);
    $member->validateConfigurationForm($memberForm, $memberFormState);
    // Update form state with values that might have been changed by the plugin.
    $formState->setValue($memberForm['#parents'], $memberFormState->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    parent::submitForm($form, $formState);

    foreach ($formState->getValue(['members']) as $memberIndex => $values) {
      $memberForm = $form['members'][$memberIndex]['form'];
      $member = $memberForm['#member'];
      $memberFormState = $this->buildMemberFormState($memberForm['#parents'], $formState);
      $member->submitConfigurationForm($memberForm, $memberFormState);
      // Update form state with values that might have been changed by the plugin.
      $formState->setValue($memberForm['#parents'], $memberFormState->getValues());
      // Update the member weight.
      $configuration = $member->getConfiguration();
      $configuration['weight'] = $values['weight'];
      $member->setConfiguration($configuration);
      // Update the member on the entity.
      $this->entity->getMembers()->addInstanceId($member->getId(), $configuration);
    }
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

  /**
   * Builds the form state passed to zone members.
   *
   * @param array $memberParents
   *   The parents array indicating the position of the member form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The parent form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The new member form state.
   */
  protected function buildMemberFormState($memberParents, FormStateInterface $formState) {
    $memberValues = $formState->getValue($memberParents, []);
    $memberUserInput = (array) NestedArray::getValue($formState->getUserInput(), $memberParents);
    $memberFormState = new FormState();
    $memberFormState->setValues($memberValues);
    $memberFormState->setUserInput($memberUserInput);

    return $memberFormState;
  }

}
