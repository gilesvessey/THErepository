<?php
namespace Drupal\file_upload\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dbclasses\DBAdmin;
use Drupal\dbclasses\DBRecord;
use Drupal\Core\File\File;
class FileUploadForm extends FormBase {
	
  private $fileLocation = "sites/default/files/downloads/"; // recommended this stay the same (NOTE: YOU MUST MANUALLY CREATE THIS FOLDER ONCE)
  private $fileName = "";	
	
  public function buildForm(array $form, FormStateInterface $form_state) {
	  
	//Link to info page. Opens in a new window/tab
	$form['info_link'] = [
		'#type' => 'item',
		'#markup' => "<a href='about_upload' target='_blank'>For more info about upload requirements, click here.</a>",
	];  	  
	$form['upload_link'] = [
	    '#type' => 'item',
	    '#markup' => "See also: <a href='single_upload' target='_blank'>Single Insertion Page</a>",
	];
	  
	//After submission, if there were any invalid lines, print them to this table
	if($form_state->get('submitted') == 1 && $form_state->get('lineError') == 1) {
		$form['t_download'] = [
                '#type' => 'submit',
				'#prefix' => "<b>Invalid Lines:</b><br>",
                '#value' => $this->t('Download'),
                '#submit' => array(
                    '::downloadForm'
                )
		];
		
		$form['table'] = array(
			'#type' => 'table',
			'#header' => array(
				t('Line #'),
				t('p_issn'),
				t('e_issn'),
				t('l_issn'),
				t('lc'),
				t('title'),
				t('Reason(s)'),
			),
		);
		
		//Print the values of each row into the table
		$counter = 0;
		foreach($form_state->get('tabledata') as $row) {
			
			//Line number
			$form['table'][$counter]['Line #'] = array(
				'#type' => 'item',
				'#description' => $row[0],
			);
			
			//P-issn
			$form['table'][$counter]['p_issn'] = array(
				'#type' => 'item',
				'#description' => $row[1],
			);
			
			//E-issn
			$form['table'][$counter]['e_issn'] = array(
				'#type' => 'item',
				'#description' => $row[2],
			);
			
			//L-issn
			$form['table'][$counter]['l_issn'] = array(
				'#type' => 'item',
				'#description' => $row[3],
			);
			
			//LC
			$form['table'][$counter]['lc'] = array(
				'#type' => 'item',
				'#description' => $row[4],
			);
			
			//Title
			$form['table'][$counter]['title'] = array(
				'#type' => 'item',
				'#description' => $row[5],
			);
			
			//Reason for line being declined
			$form['table'][$counter]['Reason(s)'] = array(
				'#type' => 'item',
				'#description' => $row[6],
			);
			
			$counter++;
		}
	}
	else { //Otherwise, print all the input elements
			
		//File upload element
		$form['file_upload'] = [
			'#type' => 'managed_file',
			'#title' => t('Upload a file into the database here:'),
			'#size' => 20,
			'#upload_location' => 'public://uploads/',
			'#upload_validators' => array('file_validate_extensions' => array('csv tsv')),
			'#required' => true,
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
					1 =>t('Replace existing LC assignments (Owned by me)'),
					2 =>t('Replace existing LC assignments (Owned by my institution)')
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
			'#value' => t('Submit'), //the text printed on the submit button
		];
	}
	
    return $form;
  }
	
	
  public function validateForm(array &$form, FormStateInterface $form_state) {
	//using userID in fileName forces each user to only ever have a most one uploaded file on the server at a time.
	//also, needed file names to be more predictable than truly random names (for the purpose of sending email reports AND allowing reports
	//to be displayable in the download() method.
	$userID = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id()); //get the current user's info
	$this->fileName = "Invalids". $userID->get('uid')->value .".csv"; //generate a name on submit
	  
	drupal_set_message(t("NOTE: Large files will take time to process.<br />
							ONCE you submit your file, you may close this page.<br />
							You will be emailed a report when your information is processed.<br />
							Your report will be saved until your next upload."));
  }
	
  public function submitForm(array &$form, FormStateInterface $form_state) {
	//clear drupal_set_message()
	$messages = drupal_get_messages();
	
   	// Handle submitted values in $form_state here.
	$dbAdmin = new DBAdmin();
	
	$database = \Drupal::database(); //Drupal saves references in its database to all files uploaded via managed_file
	$sql = "SELECT uri FROM file_managed WHERE status=0 ORDER BY fid DESC LIMIT 1;"; //find the uri of the file just added
	$result = db_query($sql);
	$fileLocation = '';
	$fileExtension = '';
	
	foreach($result as $record)
	{
		$fileLocation = $record->uri;
		//public:// is the folder that Drupal allows users to download/access files from, etc
		$fileLocation = str_replace('public://', 'sites/default/files/', $fileLocation); //format the file location properly
	}
	
	$file = []; //instantiate $file which will be an array of arrays
	$headers = [];
	$isHeader = TRUE;
	
	//determine delimiter
	$fileExtension = pathinfo($fileLocation, PATHINFO_EXTENSION);
	$delimiter = '';
	if($fileExtension == 'tsv')
		$delimiter = "\t";
	else //default is comma
		$delimiter = ',';
		
	$fileHandle = fopen($fileLocation, "rw"); //open file
	if($fileHandle) //if no error
	{		
		while (!feof($fileHandle)) { //until end of file...
			$record = fgetcsv($fileHandle, 0, $delimiter);
			
			if($isHeader) //first line of file is header...
			{
				$tempHeaders = [];
				array_push($tempHeaders, $record);
				$headers[0] = trim($tempHeaders[0][0]); //trim to make sure no trailing white space or line breaks
				$headers[1] = trim($tempHeaders[0][1]);
				$headers[2] = trim($tempHeaders[0][2]);
				$headers[3] = trim($tempHeaders[0][3]);
				$headers[4] = trim($tempHeaders[0][4]);
				$isHeader = FALSE;
			}
			else
			{
				array_push($file, $record);
			}
		}
		
		fclose($fileHandle);
	} else { //problem in opening file
		echo "Unable to access file.";
	}
	
	//Get the user's id to put as the source
	$user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
	$uid = $user->get('uid')->value;
	
	//Get the issn radio button option
	$issnOption = $form_state->getValue('issn_option');
	
	$headersCorrect = true; //Holds whether the headers are correct or not
	
	//Check that there are 5 elements in the header
	if(count($headers) != 5) {
		$headersCorrect = false;
		$numOfHeaders = count($headers);
		drupal_set_message("Wrong number of elements in header ($numOfHeaders present instead of 5)", 'error');
	}
	else {
		//Get positions for each piece of input data based on the headers
		$titlePos = -1;
		$p_issnPos = -1;
		$l_issnPos = -1;
		$e_issnPos = -1;
		$lcPos = -1;
		
		$counter = 0; //Current header position
		
		//Headers must be of title, p_issn, l_issn, e_issn, lc
		foreach($headers as $header) {
			if(strcmp($header,'title') == 0)  {
				if($titlePos != -1) { //If title already was assigned, headers are wrong
						$headersCorrect = false;
						drupal_set_message('title column appears twice in header', 'error');
				}
				else { //Otherwise get the position
						$titlePos = $counter;
				}
			}
			else if(strcmp($header,'p_issn') == 0)  {
				if($p_issnPos != -1) { //If p_issn already was assigned, headers are wrong
						$headersCorrect = false;
						drupal_set_message("p_issn column appears twice in header", 'error'); 
				}
				else { //Otherwise get the position
					$p_issnPos = $counter;
				}
			}
			else if(strcmp($header,'l_issn') == 0)  {
				if($l_issnPos != -1) { //If l_issn already was assigned, headers are wrong
					$headersCorrect = false;
					drupal_set_message('l_issn column appears twice in header', 'error');
				}
				else { //Otherwise get the position
						$l_issnPos = $counter;
				}
			}
			else if(strcmp($header,'e_issn') == 0)  {
				if($e_issnPos != -1) { //If e_issn already was assigned, headers are wrong
					$headersCorrect = false;
					drupal_set_message('e_issn column appears twice in header', 'error');
				}
				else { //Otherwise get the position
					$e_issnPos = $counter;
				}
			}
			else if(strcmp($header,'lc') == 0)  {
				if($lcPos != -1) { //If lc already was assigned, headers are wrong
					$headersCorrect = false;
					drupal_set_message('LC column appears twice in header', 'error');
				}
				else { //Otherwise get the position
					$lcPos = $counter;
				}
			}
			else { //If the header value is none of the accepted values, header is wrong
				$headersCorrect = false;
				drupal_set_message('Invalid value present in header, or missing value: ' . $header, 'error');
			}
			
			$counter++; //Increment current header position
		}
	}		
	
	if($headersCorrect){ //Only read in data if the headers are correct
	
		$lineCount = 1; //Holds current line number, starts at 1 because of headers
		$errorCount = 0; //Holds number of invalid lines
		
		foreach($file as $line) {
			
			$lineCount++; //Increment line counter	
				
			//Read in the line, using the header positions we got earlier
			$title = $line[$titlePos];
			$l_issn = $line[$l_issnPos];
			$p_issn = $line[$p_issnPos];
			$e_issn = $line[$e_issnPos];
			$lc = $line[$lcPos];
			
			//Trim whitespace, helps with invalids output file
			$l_issn = trim($l_issn);
			$p_issn = trim($p_issn);
			$e_issn = trim($e_issn);
			$title = trim($title);
			$lc = trim($lc);
			
			$reason = ""; //Holds reasons for error if there is one
	
			//Enter data based on which radio button was pressed
					
			if(count($line) != 5) {
				$reason = "Wrong number of elements in line. (Check quoting)";
				$form_state->set(['tabledata', $lineCount], [$lineCount, $p_issn, $e_issn, $l_issn, $lc, $title, $reason]);
				$errorCount++;
			}
			//Add new assignments, nothing special
			else if ($issnOption == 0) {
				$insert = $dbAdmin->insert($title, $l_issn, $p_issn, $e_issn, $lc);
				if($insert[0] == 0) { //If there are errors
					foreach($insert[1] as $error) { //Add them to reason
						$reason .= $error . ', ';
					}
					$reason = rtrim($reason, ','); //trim the last comma
					$reason = '"' . $reason . '"'; //Put the reason in quotes
					$form_state->set(['tabledata', $lineCount], [$lineCount, $p_issn, $e_issn, $l_issn, $lc, $title, $reason]);
					$errorCount++;
				}	
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
					foreach($insert[1] as $error) { //Add them to reason
						$reason .= $error . ', ';
					}
					$reason = rtrim($reason, ','); //trim the last comma
					$reason = '"' . $reason . '"'; //Put the reason in quotes
					$form_state->set(['tabledata', $lineCount], [$lineCount, $p_issn, $e_issn, $l_issn, $lc, $title, $reason]);
					$errorCount++;
				}
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
					$results = $dbAdmin->selectByISSN($p_issn);
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
					foreach($insert[1] as $error) { //Add them to reason
						$reason .= $error . ', ';
					}
					$reason = rtrim($reason, ','); //trim the last comma
					$reason = '"' . $reason . '"'; //Put the reason in quotes
					$form_state->set(['tabledata', $lineCount], [$lineCount, $p_issn, $e_issn, $l_issn, $lc, $title, $reason]);
					$errorCount++;
				}
			}
		}
	}
	
	if($errorCount > 0) {
		$form_state->set('lineError', 1); //Note there is one error at least
		
		//create the file	  
		$file = fopen($this->fileLocation . $this->fileName, "w");
		fwrite($file, "Line#,p_issn,e_issn,l_issn,lc,title,Reason(s)\n"); //write header to file
		foreach($form_state->get('tabledata') as $row) {
			$printOut = "$row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6]\n"; //write each invalid line
			fwrite($file, $printOut);
		}
		fclose($file);
	
		// send an email message when done
		$user = \Drupal::currentUser();
		$to = $user->getEmail();
		$from = "noreply@upei.ca"; //this can be changed to a real address if desired
		$subject = "ISSN Upload Report";
		$body = "Your file has been processed. Your report is available <a href='http://issn.researchspaces.ca/".$this->fileLocation . $this->fileName."'> HERE.</a>.";
		simple_mail_send($from, $to, $subject, $body);
	}
	
	if($headersCorrect) {
		drupal_set_message("Upload Complete");
		drupal_set_message("File contained " . ($lineCount - 1) . " entries");
		if($form_state->get('lineError') == 1) {
			drupal_set_message($errorCount . " entries were invalid and were not uploaded to the database (More info below)", 'warning');
			
		}
		$form_state->set('submitted', 1); //Form has been submitted
		$form_state->setRebuild(); //Update the form elements
	}
	 
	
	
	return $form;
  }
  public function downloadForm(array &$form, FormStateInterface $form_state) {
	//Serve the file to the user
	//Note the file is already created by this point
	header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.$this->fileName.'"');
	readfile($this->fileLocation . $this->fileName);
		
	exit;
  }
  
  protected function getEditableConfigNames() {
    return [
      'file_upload_form.settings',
    ];
  }
  
  public function getFormId() {
    return 'file_upload_form';
  }
}
?>
