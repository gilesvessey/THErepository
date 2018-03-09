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
		'#maxlength' => 8,
		'#size' => 8,
	];
	
	//E-ISSN
	$form['e_issn'] = [
		'#type' => 'textfield',
		'#title' =>t('E-ISSN'),
		'#maxlength' => 8,
		'#size' => 8,
	];
	
	//L-ISSN
	$form['l_issn'] = [
		'#type' => 'textfield',
		'#title' =>t('L-ISSN'),
		'#maxlength' => 8,
		'#size' => 8,
	];
	
	//LC Call Number
	$form['callnumber']  = [
		'#type' => 'textfield',
		'#title' =>t('LC Number'),
		'#size' => 15,
		'#required' => true,
	];
	
	//Title of Journal
	$form['title'] = [
		'#type' => 'textfield',
		'#title' =>t('Title'),
		'#size' => 30,
	];
	
		
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
	$regISSN = '/^[0-9]{4}-?[0-9]{3}([0-9]|(X|x))$/'; //Accepts an ISSN with or without a hypen
	$regLCCN = '/^[a-zA-Z]([a-zA-Z]|[0-9]|.|-|\s)*$/'; //Will hold the LCCN regex
	
	
	$title = $form_state->getValue('title');
	$l_issn = $form_state->getValue('l_issn');
	$p_issn = $form_state->getValue('p_issn');
	$e_issn = $form_state->getValue('e_issn');
	$callnumber = $form_state->getValue('callnumber');
		
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
	if((preg_match($regISSN, $l_issn) == 0 | preg_match($regISSN, $l_issn) == false) && $l_issn != null) {//If the ISSN is not in the right format and something is there
		$correct = false;
		
		drupal_set_message('Invalid l_issn', 'error');
	}
	
	//Check P-ISSN
	if((preg_match($regISSN, $p_issn) == 0 | preg_match($regISSN, $p_issn) == false) && $p_issn != null) {//If the ISSN is not in the right format and something is there
		$correct = false;
		
		drupal_set_message('Invalid p_issn', 'error');
	}
	
	//Check E-ISSN
	if((preg_match($regISSN, $e_issn) == 0 | preg_match($regISSN, $e_issn) == false) && $e_issn != null) {//If the ISSN is not in the right format and something is there
		$correct = false;
		
		drupal_set_message('Invalid e_issn', 'error');
	}
	
	//Check LCCN
	if((preg_match($regLCCN, $callnumber) == 0 | preg_match($regLCCN, $callnumber) == false) || $callnumber == null) {//If the LCCN is invalid or is missing, line is wrong
		$correct = false;
		
		drupal_set_message('Invalid callnumber', 'error');
	}
				
	//Check that at least one ISSN element has data inside
	$existsISSN = false;
	if(($l_issn != null) | ($p_issn != null) | ($e_issn != null)) {
		$existsISSN = true;
	}
	else {
		drupal_set_message('No ISSN present', 'error');
	}
		
	if($correct && $existsISSN) { //If this line's data is correct and contains at least one ISSN, enter it
		if ($issnOption == 0) {
			$dbAdmin->insert($title, $uid, $l_issn, $p_issn, $e_issn, 0, $callnumber);
		}
		//Replace own assignments
		else if ($issnOption == 1) {
			//Do a query for each ISSN type, look for entries with matching user ID
							
			//Search for L-ISSN
			$results = $dbAdmin->selectByISSN($l_issn);
			foreach($results as $entry) {
				if($entry->user_id == $uid) { //If the uid is matching
					$dbAdmin->deleteById($entry->id);
				}
			}
							
			//Search for P-ISSN
			$results = $dbAdmin->selectByISSN($p_issn);
			foreach($results as $entry) {
				if($entry->user_id == $uid) { //If the uid is matching
								$dbAdmin->deleteById($entry->id);
				}
			}
							
			//Search for E-ISSN
			$results = $dbAdmin->selectByISSN($p_issn);
			foreach($results as $entry) {
				if($entry->user_id == $uid) { //If the uid is matching
					$dbAdmin->deleteById($entry->id);
				}
			}
							
			//Now add the new entry
			$dbAdmin->insert($title, $uid, $l_issn, $p_issn, $e_issn, 0, $callnumber);
			}
		//Replace institution assignments
		else if ($issnOption == 2) {
			//Do a query for each ISSN type, look for entries with matching institution
						
			//Search for L-ISSN
			$results = $dbAdmin->selectByISSN($l_issn);
			foreach($results as $entry) {
				//If the institution is matching
					//Delete this entry
							
			}
						
			//Search for P-ISSN
			$results = $dbAdmin->selectByISSN($p_issn);
			foreach($results as $entry) {
				//If the institution is matching
					//Delete this entry
							
			}
						
			//Search for E-ISSN
			$results = $dbAdmin->selectByISSN($p_issn);
			foreach($results as $entry) {
				//If the institution is matching
					//Delete this entry
							
			}
						
			//Now add the new entry
			$dbAdmin->insert($title, $uid, $l_issn, $p_issn, $e_issn, 0, $callnumber);
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