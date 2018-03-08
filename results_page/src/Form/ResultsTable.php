<?php
namespace Drupal\results_page\Form;

//Required core classes
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

//use our custom classes
use Drupal\dbclasses\DBAdmin;
use Drupal\dbclasses\DBRecord;

class ResultsTable extends ConfigFormBase {

/**
 *This method puts the form together (defines fields).
 */
public function buildForm(array $form, FormStateInterface $form_state) {
//After submission, if there were any invalid lines, print them to this table
if($form_state->get('submitted') == 1) {
$config = $this->config('results_page.settings');
$dbadmin = new DBAdmin();
$searchtype = $form_state->get('searchtype');
$searchterm = $form_state->get('searchterm');

$recordset;

if ($searchtype == 'issn')
$recordSet = $dbadmin->selectByISSN($searchterm);
else if ($searchtype == 'lccn')
$recordSet = $dbadmin->selectByLC($searchterm);
else if ($searchtype == 'all')
$recordSet = $dbadmin->selectAll();

$records = count($recordSet);
if ($records >= $form_state->get('resultsshown'))
{
$form['table'] = array(
			'#type' => 'table',
		    '#caption' => ('Showing first ' . $form_state->get('resultsshown') . ' rows out of ' . $records . ' total results.'),
		    '#empty' => 'No results to be shown.',
			'#header' => array(
t('Title'),
t('Linking ISSN'),
t('Print ISSN'),
t('Electronic ISSN'),
t('LC Call Number'),
t('Source'),
),
);
}
else
{
$form['table'] = array(
			'#type' => 'table',
		    '#caption' => ('Showing first ' . $records . ' rows out of ' . $records . ' total results.'),
		    '#empty' => 'No results to be shown.',
			'#header' => array(
t('Title'),
t('Linking ISSN'),
t('Print ISSN'),
t('Electronic ISSN'),
t('LC Call Number'),
t('Source'),
),
);
}
//Print the values of each row into the table
$counter = 0;
foreach($recordSet as $record) {

if ($counter >= $form_state->get('resultsshown')) //This is what stops the page from displaying more than your requested num of results
break;

$form['table'][$counter]['Title'] = array(
				'#type' => 'item',
				'#description' => $record->title,
);


$form['table'][$counter]['Linking ISSN'] = array(
				'#type' => 'item',
				'#description' => $record->issn_l,
);


$form['table'][$counter]['Print ISSN'] = array(
				'#type' => 'item',
				'#description' => $record->p_issn,
);


$form['table'][$counter]['Electronic ISSN'] = array(
				'#type' => 'item',
				'#description' => $record->e_issn,
);


$form['table'][$counter]['LC Call Number'] = array(
				'#type' => 'item',
				'#description' => $record->callnumber,
);

$form['table'][$counter]['Source'] = array(
				'#type' => 'item',
				'#description' => $record->source,
);

$counter++;
}
}
else { //If no form data is received, display the input form

$config = $this->config('searchInterface.settings');

$form['file_content'] = [
			'#type' => 'radios',
			'#title' => $this->t('Search By File:'),
			'#options' => [
				'ISSN' => t('ISSN'),
				'LCCN' => t('LCCN'),
],
];

$form['upload_file'] = [
			'#type' => 'file',
			'#title' => $this->t(''),
//'#multiple' =>
//'#size' =>
];

$form['issn_text'] = [
			'#type' => 'textarea',
			'#title' => $this->t('...or Paste A List Of ISSNs Below:'),
			'#default_value' => $config->get('issn_text'),
];

$form['quantity'] = [
			'#type' => 'number',
			'#title' => $this->t('# of Previewed Results:'),
			'#default_value' => $this->t('50'),
			'#min' => '1',
			'#max' => '10000',
			'#size' => '5',
];

$form['actions']['download'] = [
			'#type' => 'button',
			'#value' => $this->t('Download'),
];

$form['actions']['display'] = [
			'#type' => 'submit',
			'#value' => $this->t('Display'),
];
}
return $form;
}

/**
 *This method will be called automatically upon submission.
 *This is the shit that gets done if the user's input passes validation.
 */
public function submitForm(array &$form, FormStateInterface $form_state) {

// Handle submitted values in $form_state here.

if ($form_state->getValue('issn_text') != "")
{
$searchtype = 'issn';
$searchterm = $form_state->getValue('issn_text');
}
else
$searchtype = 'all';

$form_state->set('searchterm', $searchterm);
$form_state->set('searchtype', $searchtype);
$form_state->set('resultsshown', $form_state->getValue('quantity'));

//drupal_set_message(t('Search Results')); //Found this a little ugly, maybe we'll bring it back at some point
$form_state->set('submitted', 1);
$form_state->setRebuild();

return $form;
}

/**
 * {@inheritdoc}
 */
protected function getEditableConfigNames() {
return [
      'results_page.settings',
];
}

/**
 * {@inheritdoc}
 */
public function getFormId() {
return 'results_page_settings';
}

}
?>