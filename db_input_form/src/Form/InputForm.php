<?php
namespace Drupal\db_input_form\Form;

//Required core classes
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

//use our custom classes
use Drupal\dbclasses\DBAdmin;
use Drupal\dbclasses\DBRecord;

class InputForm extends ConfigFormBase {

  /**
  *This method puts the form together (defines fields).
  */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Build our Form API array here.
	
	$form['title'] = [ //the array "form" holds all the fields of the that will be printed to screen.
			'#type' => 'textfield', //a standard, single-line text field that accepts input.
			'#size' => '50', //physical width of the filed.
			'#maxlength' => '150', //accepts up to 50 characters max.
			'#required' => TRUE,
			'#attributes' => ['placeholder' => t('title')] //help text that appears inside of field.
		];
		
		$form['issn_l'] = [ 
			'#type' => 'textfield',
			'#size' => '25',
			'#maxlength' => '10', 
			'#attributes' => ['placeholder' => t('issn_l')] 
		];
		
		$form['p_issn'] = [ 
			'#type' => 'textfield',
			'#size' => '25', 
			'#maxlength' => '10', 
			'#attributes' => ['placeholder' => t('p_issn')] 
		];
		
		$form['e_issn'] = [ 
			'#type' => 'textfield',
			'#size' => '25', 
			'#maxlength' => '10', 
			'#attributes' => ['placeholder' => t('e_issn')] 
		];
		
		$form['lcclass'] = [ 
			'#type' => 'textfield',
			'#size' => '25', 
			'#maxlength' => '50', 
			'#attributes' => ['placeholder' => t('lcclass')]
		];
		
		$form['callnumber'] = [ 
			'#type' => 'textfield',
			'#size' => '25',
			'#maxlength' => '150', 
			'#attributes' => ['placeholder' => t('callnumber')]
		];
		
		$form['submit'] = [
			'#type' => 'submit', //standard form button for submission
			'#value' => t('Submit.'), //the text printed on the submit button
		];

    return $form;
  }

	/**
	*This method will be called automatically upon submission.
	*This is the shit that gets done if the user's input passes validation.
	*/	
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Handle submitted values in $form_state here.
	$dbAdmin = new DBAdmin();
	
	$title = $form_state->getValue('title');
	$issn_l = $form_state->getValue('issn_l');
	$p_issn = $form_state->getValue('p_issn');
	$e_issn = $form_state->getValue('e_issn');
	$lcclass = $form_state->getValue('lcclass');
	$callnumber = $form_state->getValue('callnumber');
		
	$dbAdmin->insert($title, 0, $issn_l, $p_issn, $e_issn, $lcclass, $callnumber);
	
	drupal_set_message(t('Submitted'));

	return $form;
    //return parent::submitForm($form, $form_state);
  }
  
   /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'input_form.settings',
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'input_form_settings';
  }

}
?>