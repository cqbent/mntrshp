<?php

namespace Drupal\mntrshp_multistep_registration;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFormInterface;
class CustomRegistrationFormAlter {

  public $stepFields = [
    1 => [
      'name',
      'mail',
      'pass',
      'account',
      'field_first_name',
    ],
    2 => [
      'field_last_name',
      'field_areas_of_interest',
      'field_personal_letter_field',
      'field_personal_video',
      'field_general_skills',
      'field_city',
      'field_state',
      'field_zip',
      'actions'
    ],
  ];
  public function alterForm(&$form, FormStateInterface $form_state, $form_id) {
    //$form['field_first_name']['widget'][0]['value']['#default_value'] = 'Charles';
    // Initialize the step if it's not set
    if ($form_state->get('step') == NULL) {
      $form_state->set('step', 1);
      $form_state->set('step_values', []);
    }

    $validation = [];
    // Disable all the fields to begin.
    foreach ($this->stepFields as $page => $fields) {
      if ($page != $form_state->get('step')) {
        foreach ($fields as $field) {
          $form[$field]['#access'] = FALSE;
        }
      }
      else {
        foreach ($fields as $field) {
          array_push($validation, [$field]);
        }
      }
    }

    // Add our custom validation function to the start of the array.
    array_unshift($form['#validate'], [$this, 'preSubmitForm']);

    // Add navigation buttons
    $this->addNavigationButtons($form, $form_state, $this->stepFields, $validation);

  }

  private function addNavigationButtons(&$form, FormStateInterface $form_state, $steps, $validation) {
    $form['custom_registration_actions'] = [
      '#type' => 'actions',
      '#weight' => 100,
    ];
    $step = $form_state->get('step');

    // 'Previous' button
    if ($step > 1) {
      $form['custom_registration_actions']['previous'] = [
        '#type' => 'submit',
        '#value' => t('Previous'),
        '#submit' => [[$this, 'prevNextSubmit']],
        '#limit_validation_errors' => $validation,
      ];
    }

    // 'Next' or 'Submit' button
    if ($step < count($steps)) {
      $form['custom_registration_actions']['next'] = [
        '#type' => 'submit',
        '#value' => t('Next'),
        '#submit' => [[$this, 'prevNextSubmit']],
        '#limit_validation_errors' => $validation,
      ];
    } else {
      $form['actions']['submit']['#access'] = TRUE;
    }
  }

  public function prevNextSubmit(array &$form, FormStateInterface $form_state) {
    $step_values = $form_state->get('step_values');
    $step = $form_state->get('step');
    // add to step_values from current step
    foreach($this->stepFields[$step] as $field) {
      $step_values[$step][$field] = $form_state->getValue($field);
      //var_dump($step_values[$step][$field]);
      //$form[$field]['#default_value'] = $step_values[$step][$field];
    }
    // set form_state values from non-current step_values
    $stepNumbers = array_keys($this->stepFields);
    foreach($stepNumbers as $stepNumber) {
      if ($stepNumber != $step) {
        foreach ($this->stepFields[$stepNumber] as $field) {
          if (isset($step_values[$stepNumber][$field])) {
            $form_state->setValue($field, $step_values[$stepNumber][$field]);
          }
        }
      }
    }
    $form_state->set('step', $form_state->getValue('next') ? $step + 1 : $step - 1);
    $form_state->set('step_values', $step_values);
    if ($form_state->getValue('next')) {
      $form_state->setValue('roles', []);
      $entity_form = $form_state->getFormObject();
      $entity_updated = $entity_form->buildEntity($form, $form_state);
      $entity_form->setEntity($entity_updated);
    }
    $form_state->setRebuild(TRUE);

    //var_dump($form_state->get('step_values'));

  }

  public function nextSubmit(array &$form, FormStateInterface $form_state) {
    $step_values = $form_state->get('step_values') ?? [];
    $step = $form_state->get('step');
    // add to step_values
    foreach($this->stepFields[$step] as $field) {
      $step_values[$step][$field] = $form_state->getValue($field);
    }
    $form_state->set('step', $step + 1);
    $form_state->set('step_values', $step_values);
    $form_state->setRebuild(TRUE);
  }

  public function previousSubmit(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');
    $step_values = $form_state->get('step_values');
    $form_state->setValue('field_first_name', 'Charles');
    // set values from step_values and then discard current step_values
    foreach($this->stepFields[$step] as $field) {
      //$form_state->setValue($field, $step_values[$step][$field]);
      //unset($step_values[$step][$field]);
    }

    $form_state->set('step', $step - 1);
    $form_state->set('step_values', $step_values);
    $form_state->setRebuild(TRUE);


  }

  public function preSubmitForm(array &$form, FormStateInterface $form_state) {
    // Load the submitted values.
    $step_values = $form_state->get('step_values');
    $submitted_values = $form_state->getValues();
    // Put all the paged values back into the form_state values.
    foreach ($step_values as $page_num => $fields) {
      foreach ($fields as $key => $value) {
        $submitted_values[$key] = $value;
      }
    }
    // Save the form_state values for further processing.
    $form_state->setValues($submitted_values);
  }
}
