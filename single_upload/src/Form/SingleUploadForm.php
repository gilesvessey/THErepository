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
		'#markup' => "<a href='about_upload' target='_blank'>For more info about upload requirements, click here.</a>",
		
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

  public function validateForm(array &$form, FormStateInterface $form_state) {}
   
  public function submitForm(array &$form, FormStateInterface $form_state) {
	$dbAdmin = new DBAdmin();
	$uid = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id())->get('uid')->value; //Get current user id
	
	//Get the issn radio button option
	$issnOption = $form_state->getValue('issn_option');
	
	//Get the input data
	$title = $form_state->getValue('title');
	$l_issn = $form_state->getValue('l_issn');
	$p_issn = $form_state->getValue('p_issn');
	$e_issn = $form_state->getValue('e_issn');
	$lc = $form_state->getValue('lc');
	
	//If user didn't quote the title, add quotes
	if(((substr($title, 0, 1) != '"') || (substr($title, -1, 1) != '"')) && $title != null)
		$title = '"' . $title . '"';
	
	//Normal upload
	if ($issnOption == 0) {
		$insert = $dbAdmin->insert($title, $l_issn, $p_issn, $e_issn, $lc);
		if($insert[0] == 0) { //If there are errors
			foreach($insert[1] as $error) //Print them
				drupal_set_message($error, 'error');
		}
		else //Otherwise, success
			drupal_set_message('Entry uploaded successfully!');
	}
	//Replace own assignments
	else if ($issnOption == 1) {
		//Do a query for each ISSN type, look for entries with matching user ID
		if($l_issn != null) {
			//Search for L-ISSN
			$results = $dbAdmin->selectByISSN($l_issn);
			foreach($results as $entry) {
				if($entry->user == $uid) { //If the uid is matching
					$dbAdmin->deleteLCById($entry->id);
				}
			}
		}			
		if($p_issn != null) {
			//Search for P-ISSN
			$results = $dbAdmin->selectByISSN($p_issn);
			foreach($results as $entry) {
				if($entry->user == $uid) { //If the uid is matching
					$dbAdmin->deleteLCById($entry->id);
				}
			}
		}		
		if($e_issn != null) {
			//Search for E-ISSN
			$results = $dbAdmin->selectByISSN($e_issn);
			foreach($results as $entry) {
				if($entry->user == $uid) { //If the uid is matching
					$dbAdmin->deleteLCById($entry->id);
				}
			}
		}					
		//Now add the new entry
		$insert = $dbAdmin->insert($title, $l_issn, $p_issn, $e_issn, $lc);
		if($insert[0] == 0) { //If there are errors
			foreach($insert[1] as $error) //Print them
				drupal_set_message($error, 'error');
		}
		else //Otherwise, success
			drupal_set_message('Entry uploaded successfully!');
	}
	//Replace institution assignments
	else if ($issnOption == 2) {
	//Do a query for each ISSN type, look for entries with matching institution
		if($l_issn != null) {
			//Search for L-ISSN
			$results = $dbAdmin->selectByISSN($l_issn);
			foreach($results as $entry) {
				$entryInstitution = $dbAdmin->getUserInstitution($entry->user); //Get the institution name corresponding to this entry
				$userInstitution = $dbAdmin->getUserInstitution($uid); //Get user's institution
				if(strcmp($userInstitution, $entryInstitution) == 0) { //If the institutions are the same
					$dbAdmin->deleteLCById($entry->id); //Delete this entry
				}
			}
		}				
		if($p_issn != null) {
			//Search for P-ISSN
			$results = $dbAdmin->selectByISSN($p_issn);
			foreach($results as $entry) {
				$entryInstitution = $dbAdmin->getUserInstitution($entry->user); //Get the institution name corresponding to this entry
				$userInstitution = $dbAdmin->getUserInstitution($uid); //Get user's institution
				if(strcmp($userInstitution, $entryInstitution) == 0) { //If the institutions are the same
					$dbAdmin->deleteLCById($entry->id); //Delete this entry
				}		
			}
		}
		if($e_issn != null) {			
			//Search for E-ISSN
			$results = $dbAdmin->selectByISSN($e_issn);
			foreach($results as $entry) {
				$entryInstitution = $dbAdmin->getUserInstitution($entry->user); //Get the institution name corresponding to this entry
				$userInstitution = $dbAdmin->getUserInstitution($uid); //Get user's institution
				if(strcmp($userInstitution, $entryInstitution) == 0) { //If the institutions are the same
					$dbAdmin->deleteLCById($entry->id); //Delete this entry
				}			
			}
		}				
		//Now add the new entry
		$insert = $dbAdmin->insert($title, $l_issn, $p_issn, $e_issn, $lc);
		if($insert[0] == 0) { //If there are errors
			foreach($insert[1] as $error) //Print them
				drupal_set_message($error, 'error');
		}
		else //Otherwise, success
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