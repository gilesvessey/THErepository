<?php
/**
 * @file
 * Contains \Drupal\upload\Form\UploadForm.
 */
namespace Drupal\upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class UploadForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
   $form['file'] = array(
      '#type' => 'managed_file',
      '#name' => 'file',
      '#title' => t('Upload a file into the database here:'),
      '#size' => 20,
      '#description' => t(''),
      '#upload_validators' => $validators,
      '#upload_location' => 'public://my_files/',
    );

    $form['issn_option'] = array (
      '#prefix' => "  For more info about upload requirements, click here.",
      '#type' => 'radios',
      '#title' => ('For matching ISSNs:'),
      '#default_value' => 0,
      '#options' => array(
        0 =>t('Add new LC assignments'),
        1 =>t('Replace all LC assignments (Owned by me)'),
	2=>t('Replace all LC assignments (Owned by my institution)')
       ),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
    public function validateForm(array &$form, FormStateInterface $form_state) {

      

    }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

   // drupal_set_message($this->t('@can_name ,Your application is being submitted!', array('@can_name' => $form_state->getValue('candidate_name'))));

    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }

   }
} 