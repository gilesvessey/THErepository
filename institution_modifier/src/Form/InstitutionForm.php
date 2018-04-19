<?php
namespace Drupal\institution_modifier\Form;

//Required core classes
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

//use our custom classes
use Drupal\dbclasses\DBAdmin;
use Drupal\dbclasses\DBRecord;

class InstitutionForm extends ConfigFormBase {
  //creates the form
  public function buildForm(array $form, FormStateInterface $form_state) {
    /**
      * establishes a connection to the database and fetch all information
      * from the institution table.
    **/
  $connection = \Drupal::database();
  $query = $connection->query("SELECT * FROM {institution}");
  $results = $query->fetchAll();


// Creates a header for the table to be displayed
  $form['contacts'] = array(
  '#type' => 'table',
  '#caption' => $this
  ->t('<b>Note:</b><br>Deletes are made by clicking the checkbox of the row to be deleted, edits are made by typing in the text field of the item to be changed.<b> Clicking the "Update Table" button applies all changes both editing and deleting</b>'),
  '#header' => array(
  $this->t('Delete'),
  $this->t('id'),
  $this->t('Name'),
  $this->t('Domain'),
  ),
  );

  $i = 0;
  foreach($results as $record)
  {

    $form['contacts'][$i]['operation'] = array(
		'#type' => 'checkbox',
	  );
    $form['contacts'][$i]['Id'] = array(
		'#type' => 'item',
		'#value' => $record->id,
    '#title' => $record->id,
	  );
	  $form['contacts'][$i]['Name'] = array(
		'#type' => 'textfield',
		'#default_value' => $record->name,
	  );
	  $form['contacts'][$i]['Domain'] = array(
		'#type' => 'textfield',
		'#default_value' => $record->domain,
	  );
    $i++;
  }
//A button for the selected data
$form['submit'] = [
  '#type' => 'submit',
  '#value' => t('Update Table'),
];

// creation of form to add data to the institution table
  $form['name'] = [
    '#type' => 'textfield',
    '#title' => $this->t('Enter name of Institution'),
    '#size' => '25',
    '#maxlength' => '150',
    '#default_value' => t('Example'),
    '#prefix' => t('<h4><br ><center><b><u>Add Institution to Table</u></b></center></br></h4>'),
    '#attributes' => ['placeholder' => t('Example')]
  ];
  $form['extension'] = [
    '#type' => 'textfield',
    '#title' => $this->t('Enter domain of Institution'),
    '#size' => '25',
    '#maxlength' => '150',
    '#default_value' => t('@example.com'),
    '#attributes' => ['placeholder' => t('@example.com')]
  ];

  $form['submit2'] = [
    '#type' => 'submit', //standard form button for submission
    '#value' => t('Add'),
    '#submit' => array('::submitForm2'), //the text printed on the submit button
  ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {

    $extension = $form_state->getValue('extension');
    if(preg_match('/^@\w*\W\w*/', $extension) == false)
    {
      $form_state->setErrorByName('extension', $this->t('The extension should be of this format "@upei.ca"'));
    }

    $name = $form_state->getValue('name');
    if(preg_match('/^[a-zA-Z0-9_].+$/', $name) == false)
    {
      $form_state->setErrorByName('name', $this->t('Please enter an affiliation.'));
    }
  }

	/**
	*This method will be called when update/removal is intended.
	*This is the stuff that happens when update/remove button is clicked.
	*/

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
  $dbAdmin = new DBAdmin();
  $i = 0;
  foreach($form_state->getValue('contacts') as $institution)
  {
    $delete = $form_state->getValue(['contacts', $i, 'operation']);
    $new_id = $form_state->getValue(['contacts',$i,'Id']);

    $new_name = $form_state->getValue(['contacts',$i,'Name']);

    $new_domain = $form_state->getValue(['contacts',$i,'Domain']);

    $i++;
    if($delete == 1)
    {
      $dbAdmin->deleteInstitutionById($new_id);
    }
    else {
      $dbAdmin->deleteInstitutionById($new_id);
      $dbAdmin->insertInstitution($new_domain,$new_name);
    }
  }
  drupal_set_message(t("Updates have been applied"));
  }

  /**
	*This method will be called automatically upon submission of the addition form.
	*This is the shit that gets done if the user's input passes validation.
	*/

  public function submitForm2(array &$form, FormStateInterface $form_state) {

    // Handle submitted values in $form_state here.
	$dbAdmin = new DBAdmin();

  $name = $form_state->getValue('name');
  $extension = $form_state->getValue('extension');

	//$dbAdmin->insert($title, 0, $issn_l, $p_issn, $e_issn, $lcclass, $callnumber);
  $dbAdmin->insertInstitution($extension,$name);

	drupal_set_message(t('Submitted'));

	return $form;
  }

   /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'institution_form.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'institution_form_settings';
  }

}
?>
