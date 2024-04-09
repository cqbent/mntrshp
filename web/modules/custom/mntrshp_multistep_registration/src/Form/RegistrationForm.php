<?php declare(strict_types = 1);

namespace Drupal\mntrshp_multistep_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Mntrshp multistep registration form.
 */
final class RegistrationForm extends FormBase {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'mntrshp_multistep_registration_example';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = $form_state->get('step');
    if ($step == NULL) {
      $form_state->set('step', 1);
      $step = 1;
    }

    $form_display = $this->entityTypeManager->getStorage('entity_form_display')->load('user.user.default');

    switch ($step) {
      case 1:

        $form['username'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Username'),
          '#required' => TRUE,
        ];
        $form['email'] = [
          '#type' => 'email',
          '#title' => $this->t('Email Address'),
          '#required' => TRUE,
        ];
        $form['actions']['next'] = [
          '#type' => 'submit',
          '#value' => $this->t('Next'),
          '#submit' => ['::submitForm', '::stepOneSubmit'],
        ];
        break;

      case 2:
        $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user');
        foreach ($fields as $field_name => $field_definition) {
          if (!empty($field_definition->getTargetBundle())) {
            $field_definition->
            $form[$field_name] = [
              '#type' => $field_definition->getType(), // Change this based on field type
              '#title' => $field_definition->getLabel(),
              '#default_value' => '', // Set default value if needed
              '#required' => $field_definition->isRequired(),
            ];
          }
        }
        break;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if (mb_strlen($form_state->getValue('message')) < 10) {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('Message should be at least 10 characters.'),
    //     );
    //   }
    // @endcode
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('step') == 1) {
      $form_state->set('step', 2);
      $form_state->setRebuild(TRUE);
    } else {
      // Final submission logic.
      $values = $form_state->getValues();
      $user = User::create();
      // Map form values to user fields.
      $user->setUsername($values['username']);
      $user->setEmail($values['email']);
      // Set custom fields.
      $user->save();

      // Redirect or other final actions.
    }
  }

  public function stepOneSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

}
