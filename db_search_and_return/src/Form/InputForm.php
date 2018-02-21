<?php
namespace Drupal\db_search_and_return\Form;

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
			'#attributes' => ['placeholder' => t('title')] //help text that appears inside of field.
		];
		
		$form['issn_l'] = [ 
			'#type' => 'textfield',
			'#size' => '25',
			'#maxlength' => '10', 
			'#attributes' => ['placeholder' => t('issn_l')] 
		];
		
		$form['lcclass'] = [ 
			'#type' => 'textfield',
			'#size' => '25', 
			'#maxlength' => '50', 
			'#attributes' => ['placeholder' => t('lcclass')]
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
	$searchType = "";
	
	if($form_state->getValue('title') != '')
	{
		$recordSet = $dbAdmin->selectByTitle($form_state->getValue('title'));
		$searchType = "Search by Title.";
	}
	else if($form_state->getValue('issn_l') != '')
	{
		$recordSet = $dbAdmin->selectByISSN($form_state->getValue('issn_l'));
		$searchType = "Search by ISSN.";
	}
	else if($form_state->getValue('lcclass') != '')
	{
		$recordSet = $dbAdmin->selectByLCClass($form_state->getValue('lcclass'));
		$searchType = "Search by LC Class.";
	}
	
	$printOut = '';
	foreach($recordSet as $record)
	{
		$printOut .= "$searchType
			ID: $record->id 
			TITLE: $record->title
			ISSN: $record->issn_l";
	}
	
	drupal_set_message("RESULT: $printOut");

	return $form;
  }
	
//Don't worry about anything below here... just giving definitions to virtual methods.
  
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
