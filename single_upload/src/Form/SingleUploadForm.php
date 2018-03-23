<?php
namespace Drupal\single_upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dbclasses\DBAdmin;
use Drupal\dbclasses\DBRecord;

class SingleUploadForm extends FormBase {
  public function buildForm(array $form, FormStateInterface $form_state) {
	
	//Link to info page. Opens in a new window/tab
	$form['info_link'] = [
		'#type' => 'item',
		'#markup' => "<a href='uploadinfo' target='_blank'>For more info about upload requirements, click here.</a>",
		
	];
	
	//Single entry elements
	//P-ISSN
	$form['p_issn'] = [
		'#type' => 'textfield',
		'#title' =>t('P-ISSN'),
		'#maxlength' => 9,
		'#size' => 9,
	];
	
	//E-ISSN
	$form['e_issn'] = [
		'#type' => 'textfield',
		'#title' =>t('E-ISSN'),
		'#maxlength' => 9,
		'#size' => 9,
	];
	
	//L-ISSN
	$form['l_issn'] = [
		'#type' => 'textfield',
		'#title' =>t('L-ISSN'),
		'#maxlength' => 9,
		'#size' => 9,
	];
	
	//LC Call Number
	$form['lc']  = [
		'#type' => 'textfield',
		'#title' =>t('LC Number'),
		'#size' => 30,
		'#required' => true,
	];
	
	//Title of Journal
	$form['title'] = [
		'#type' => 'textfield',
		'#title' =>t('Title'),
		'#size' => 50,
	];
	
		
	$user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
			
	//If the user is an institutional editor, show the third button
	if($user->hasRole('editorial_user')) {	
		//Radio buttons for choosing how duplicate LC assignments are handled, 
		// 1 -> Add new ones and don't delete anything
		// 2 -> Add new ones and delete entries with matching ISSNs and user ID
		// 3 -> Add new ones and delete entries with matching ISSNs and institution
		$form['issn_option'] = [
			'#type' => 'radios',
			'#title' => ('For matching ISSNs:'),
			'#default_value' => 0,
			'#options' => array(
				0 =>t('Add new LC assignments'),
				1 =>t('Replace all LC assignments (Owned by me)'),
				2=>t('Replace all LC assignments (Owned by my institution)')
			),
		];
	}
	else { //Otherwise only show two
		$form['issn_option'] = [
			'#type' => 'radios',
			'#title' => ('For matching ISSNs:'),
			'#default_value' => 0,
			'#options' => array(
				0 =>t('Add new LC assignments'),
				1 =>t('Replace existing LC assignments (Owned by me)'),
			),
		];
		
	}
			
	//Submit button
	$form['submit'] = [
		'#type' => 'submit', //standard form button for submission
		'#value' => t('Submit.'), //the text printed on the submit button
	];
	
    return $form;
  }

	
	
   public function validateForm(array &$form, FormStateInterface $form_state) {

    }
	
	
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
	$dbAdmin = new DBAdmin();
	
	//Get the user's id to put as the source
	$user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
	$uid = $user->get('uid')->value;
	
	//Get the issn radio button option
	$issnOption = $form_state->getValue('issn_option');
	
	//Regular expressions for data checking
	//$regTitle = '/^([a-zA-Z]|\s)*$/'; //Title, any combination of letters and whitespace, or nothing since it's optional
	$regISSN = '/^[0-9]{4}-?[0-9]{3}([0-9]|(X|x))$/'; //Accepts an ISSN with or without a hyphen
	$regLC = '/^([a-zA-Z]{1,3}).*$/';
	
	$title = $form_state->getValue('title');
	$l_issn = $form_state->getValue('l_issn');
	$p_issn = $form_state->getValue('p_issn');
	$e_issn = $form_state->getValue('e_issn');
	$lc = $form_state->getValue('lc');
		
	//Verify the data is correct
	$correct = true; //For checking if the data is correct
				
	//Check title
	/*
	if(preg_match($regTitle, $title) == 0 | preg_match($regTitle, $title) == false) {//If the title has unaccepted characters
		$correct = false; 
		
		drupal_set_message('Invalid title', 'error');
	}
	*/
	//Check L-ISSN
	if((preg_match($regISSN, $l_issn) == 0 || preg_match($regISSN, $l_issn) == false) && $l_issn != null) {//If the ISSN is not in the right format and something is there
		$correct = false;
		
		drupal_set_message('Invalid l_issn', 'error');
	}
	
	//Check P-ISSN
	if((preg_match($regISSN, $p_issn) == 0 || preg_match($regISSN, $p_issn) == false) && $p_issn != null) {//If the ISSN is not in the right format and something is there
		$correct = false;
		
		drupal_set_message('Invalid p_issn', 'error');
	}
	
	//Check E-ISSN
	if((preg_match($regISSN, $e_issn) == 0 || preg_match($regISSN, $e_issn) == false) && $e_issn != null) {//If the ISSN is not in the right format and something is there
		$correct = false;
		
		drupal_set_message('Invalid e_issn', 'error');
	}
	
	//Check LC
	$lc = str_replace(" ", "", $lc); //remove spaces first
	if((preg_match($regLC, $lc) == 0 || preg_match($regLC, $lc) == false) || $lc == null) {//If the LC is invalid or is missing, line is wrong
		$correct = false;
		
		drupal_set_message('Invalid lc', 'error');
	}
				
	//Check that at least one of e or p ISSN elements has data inside
	$existsISSN = false;
	if(($p_issn != null) || ($e_issn != null)) {
		$existsISSN = true;
	}
	else {
		drupal_set_message('No p-ISSN or e-ISSN present', 'error');
	}
		
	if($correct && $existsISSN) { //If this line's data is correct and contains at least one ISSN, enter it
	
		//Add hyphens to ISSNs that are missing them
		if(strlen($l_issn) == 8) {
			$tempISSN = substr($l_issn, 0, 4) . '-' . substr($l_issn, -4, 4);
			$l_issn = $tempISSN;
		}
		if(strlen($p_issn) == 8) {
			$tempISSN = substr($p_issn, 0, 4) . '-' . substr($p_issn, -4, 4);
			$p_issn = $tempISSN;
		}
		if(strlen($e_issn) == 8) {
			$tempISSN = substr($e_issn, 0, 4) . '-' . substr($e_issn, -4, 4);
			$e_issn = $tempISSN;
		}
	
		if ($issnOption == 0) {
			$dbAdmin->insert($title, $uid, $l_issn, $p_issn, $e_issn, 0, $lc);
		}
		//Replace own assignments
		else if ($issnOption == 1) {
			//Do a query for each ISSN type, look for entries with matching user ID
			
			if($l_issn != '') {
				//Search for L-ISSN
				$results = $dbAdmin->selectByISSN($l_issn);
				foreach($results as $entry) {
					if($entry->user == $uid) { //If the uid is matching
						$dbAdmin->deleteById($entry->id);
					}
				}
			}
					
			if($p_issn != '') {
				//Search for P-ISSN
				$results = $dbAdmin->selectByISSN($p_issn);
				foreach($results as $entry) {
					if($entry->user == $uid) { //If the uid is matching
						$dbAdmin->deleteById($entry->id);
					}
				}
			}
				
			if($e_issn != '') {
				//Search for E-ISSN
				$results = $dbAdmin->selectByISSN($e_issn);
				foreach($results as $entry) {
					if($entry->user == $uid) { //If the uid is matching
						$dbAdmin->deleteById($entry->id);
					}
				}
			}
							
			//Now add the new entry
			$dbAdmin->insert($title, $uid, $l_issn, $p_issn, $e_issn, 0, $lc);
		}
		//Replace institution assignments
		else if ($issnOption == 2) {
			//Do a query for each ISSN type, look for entries with matching institution
			
			//Search for L-ISSN
			$results = $dbAdmin->selectByISSN($l_issn);
			foreach($results as $entry) {
				$entryInstitution = $dbAdmin->getUserInstitution($entry->user); //Get the institution name corresponding to this entry
				if(strcmp($user->get('field_institution')->value, $entryInstitution) == 0) { //If the institutions are the same
					$dbAdmin->deleteById($entry->id); //Delete this entry
				}
			}
						
			//Search for P-ISSN
			$results = $dbAdmin->selectByISSN($p_issn);
			foreach($results as $entry) {
				$entryInstitution = $dbAdmin->getUserInstitution($entry->user); //Get the institution name corresponding to this entry
				if(strcmp($user->get('field_institution')->value, $entryInstitution) == 0) { //If the institutions are the same
					$dbAdmin->deleteById($entry->id); //Delete this entry
				}		
			}
						
			//Search for E-ISSN
			$results = $dbAdmin->selectByISSN($p_issn);
			foreach($results as $entry) {
				$entryInstitution = $dbAdmin->getUserInstitution($entry->user); //Get the institution name corresponding to this entry
				if(strcmp($user->get('field_institution')->value, $entryInstitution) == 0) { //If the institutions are the same
					$dbAdmin->deleteById($entry->id); //Delete this entry
				}			
			}
						
			//Now add the new entry
			$dbAdmin->insert($title, $uid, $l_issn, $p_issn, $e_issn, 0, $lc);
		}
		
		drupal_set_message('Entry uploaded successfully!');
	}

	return $form;
  }
  

  protected function getEditableConfigNames() {
    return [
      'single_upload_form.settings',
    ];
  }
  

  public function getFormId() {
    return 'single_upload_form';
  }
}
?>