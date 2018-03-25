<?php
namespace Drupal\institution_modifier\Form;

//Required core classes
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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
$dbadmin = new DBAdmin();
$results = $dbadmin->getInstitutionTable();

// Creates a header for the table to be displayed
  $header = [
     'id' => t('id'),
     'Name' => t('Name'),
     'Domain' => t('Domain'),
   ];

//stores the results from the table in the database to an array for later use
$output = array();
$i=0;
foreach ($results as $record)
{
       $output[$i] = [
         'id' => $record[0],
         'Name' => $record[1],
         'Domain' => $record[2]
       ];
       $i++;
     }

//Creates a table with all all data from the table and checkboxes by the side thanks to tableselect type :).
$form['table'] = [
'#type' => 'tableselect',
'#header' => $header,
'#options' => $output,
'#empty' => t('No users found'),
];
//A button for the selected data
$form['submit'] = [
  '#type' => 'submit',
  '#value' => t('Update'),
];

// creation of form to add data to the institution table
  $form['name'] = [
    '#type' => 'textfield',
    '#title' => $this->t('Add to the Institution Table'),
    '#size' => '40',
    '#maxlength' => '150',
    '#attributes' => ['placeholder' => t('University Of Prince Edward Island')]
  ];
  $form['extension'] = [
    '#type' => 'textfield',
    '#size' => '20',
    '#maxlength' => '150',
    '#attributes' => ['placeholder' => t('@upei.ca')]
  ];

  $form['submit2'] = [
    '#type' => 'submit', //standard form button for submission
    '#value' => t('Add'),
    '#submit' => array('::submitForm2'), //the text printed on the submit button
  ];

    return $form;
  }

	/**
	*This method will be called when removal is intended.
	*This is the shit that gets done if the user's input passes validation.
	*/

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $selected_uids = array_filter($form_state->getValue('values','checkboxes'));
    drupal_set_message(t('you Selected (a) checkbox(es)'));
    //drupal_set_message($this->t('You selected @number', array('@number' => array_filter($form_state->getValue('values','checkboxes')))));
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
    //return parent::submitForm($form, $form_state);
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
