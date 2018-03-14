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
// t_download = table download
$form['t_download'] = [
	'#type' => 'submit',
	'#value' => $this->t('Downlaod'),
	'#submit' => array('::downloadForm'),
];
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
			'#submit' => array('::downloadForm'),
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

public function downloadForm(array &$form, FormStateInterface $form_state)
{
	//$dbAdmin = new DBAdmin();
	//$recordSet = $dbAdmin->selectAll();

	$fileLocation = "sites/default/files/downloads/"; //recommended this stay the same (NOTE: YOU MUST MANUALLY CREATE THIS FOLDER ONCE)
	$fileName = "Download.csv";
	$file = fopen($fileLocation.$fileName, "w");
	fwrite($file, "Title,Linking ISSN,Print ISSN,Electronic ISSN, LC call number,Source"); //write header to file

	foreach($recordSet as $record)
	{
		$printOut = "\n$record->p_issn,$record->issn_l,$record->e_issn,$record->title,$record->callnumber";

		fwrite($file, $printOut);
	}

	fclose($file);


	$fileName2 = "Download.tsv";
	$file2 = fopen($fileLocation.$fileName2, "w");
	fwrite($file2, "Title\tLinking ISSN\tPrint ISSN\tElectronic ISSN\tLC call number\tSource"); //write header to file

	foreach($recordSet as $record)
	{
		$printOut2 = "\n$record->p_issn\t$record->issn_l\t$record->e_issn\t$record->title\t$record->callnumber";

		fwrite($file2, $printOut2);
	}

	fclose($file2);

	drupal_set_message(t("RESULT: <p>EXPORT AS: <a href=\"$fileLocation$fileName\">.csv</a>\t<a href=\"$fileLocation$fileName2\">.tsv</a></p>"));
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
