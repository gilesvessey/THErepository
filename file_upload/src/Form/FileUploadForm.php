<?php
namespace Drupal\file_upload\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dbclasses\DBAdmin;
use Drupal\dbclasses\DBRecord;
use Drupal\Core\File\File;

class FileUploadForm extends FormBase {
	
  public function buildForm(array $form, FormStateInterface $form_state) {
	  
	//After submission, if there were any invalid lines, print them to this table
	if($form_state->get('submitted') == 1 && $form_state->get('lineError') == 1) {
		$form['table'] = array(
			'#type' => 'table',
			'#prefix' => "<b>Invalid Lines:</b>",
			'#header' => array(
				t('Line #'),
				t('p_issn'),
				t('e_issn'),
				t('l_issn'),
				t('callnumber'),
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
			
			//Callnumber
			$form['table'][$counter]['callnumber'] = array(
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
	
		//Link to info page. Opens in a new window/tab
		$form['info_link'] = [
			'#type' => 'item',
			'#markup' => "<a href='uploadinfo' target='_blank'>For more info about upload requirements, click here.</a>",
		];
			
		//File upload element
		$form['file_upload'] = [
			'#type' => 'managed_file',
			'#title' => t('Upload a file into the database here:'),
			'#size' => 20,
			'#upload_location' => 'public://uploads/',
			'#upload_validators' => array('file_validate_extensions' => array('csv tsv')),
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
				1 =>t('Replace existing LC assignments (Owned by me)'),
				2 =>t('Replace existing LC assignments (Owned by my institution)')
			),
		];
				
		//Submit button
		$form['submit'] = [
			'#type' => 'submit', //standard form button for submission
			'#value' => t('Submit'), //the text printed on the submit button
		];
	}
	
    return $form;
  }

	
	
   public function validateForm(array &$form, FormStateInterface $form_state) {

     	
		//$file = file_save_upload('file_upload');
		//$form_state->setValue('file_upload', $file);
		
		//if (!isset($file)) {
		//	form_set_error('file_upload', t('Error the file didnt work!!!.'));
		//}
		//else {
			//if(isset($file)) {
			
			//}
		//}
		
		
		
		/*
		if (isset($file)) {
			// File upload was attempted.
				if ($file) {
				 // Put the temporary file in form_values so we can save it on submit.
					 $form_state['values']['file_upload'] = $file;
			 }
			 else {
				// File upload failed.
					form_set_error('file_upload', t('The file could not be uploaded.'));
				}
		}
		*/

    }
	
	
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle submitted values in $form_state here.
	$dbAdmin = new DBAdmin();
	
	//Need to read in the file
	//Read the headers, make sure they are all there, and note the order of them.
	//As we're reading we need to check for missing required fields. If there's something missing, just don't upload that file. Store the data in an array of bad lines.
	//Need to store the line number as we go.
	//Need to verify the data of each thing, using regular expressions.
	
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
			$record = fgets($fileHandle); //each line in the file is one record
			
			if($isHeader) //first line of file is header...
			{
				$tempHeaders = [];
				array_push($tempHeaders,explode("$delimiter",$record));
				$headers[0] = trim($tempHeaders[0][0]); //trim to make sure no trailing white space or line breaks
				$headers[1] = trim($tempHeaders[0][1]);
				$headers[2] = trim($tempHeaders[0][2]);
				$headers[3] = trim($tempHeaders[0][3]);
				$headers[4] = trim($tempHeaders[0][4]);
				$isHeader = FALSE;
			}
			else
			{
				array_push($file,explode("$delimiter",$record)); //each record is itself an array of items (ISSN, title, etc)
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
	
	//Regular expressions for data checking
	//$regTitle = '/^([a-zA-Z]|\s)*$/'; //Title, any combination of letters and whitespace, or nothing since it's optional
	$regISSN = '/^[0-9]{4}-?[0-9]{3}([0-9]|(X|x))$/'; //Accepts an ISSN with or without a hypen
	$regLCCN = '/^([a-zA-Z]{1,3}).*$/';
	#$regLCCN = '/^([a-zA-Z]{1,3})(([0-9]{0,4})|([0-9]{0,4}\.([0-9]{1,4})))(\.[a-zA-Z][0-9]{0,3}){0,2}.*$/';
	
	/*
	The above commented regLCCN enforces pretty strict formatting for LCs. The format is the following:
	1-3 letters (for the class) followed by, 
	0-4 digits or 0-4 digits followed be a dot followed by another 0-4 digits for the subject (subject is optional) followed by,
	1 letter followed by up to 3 letters for a cutter. You can have 0-2 cutters.
	Anything after that is ok

	Some examples of valid LCs with this regular expression: a, ab, abc, abc1, abc1234, abc1234.1, abc.1234.1234, abc.1234.1234.a1, abc.1.a123.a123
	Some examples of invalid LCs with this regular expression: 1, abcd, a12345, a1234.12345

	The problem with this is that sometimes people don't follow this format very strictly, and they put alot of 
	random stuff in their LC making following a regular expression rather difficult. A few examples of this found
	in given database: "GV723.N3 .{Ohorn}3", "GV848.5.A1 .R6514 (FRENCH) (JUV)", "GV862 .N55 INTERNET", "CA1CI51-61".

	If you find that this regular expression is to strict use the following instead

	$regLCCN = '/^([a-zA-Z]{1,3}).*$/';
	
	If in the future, you discover you want to be that strict with the formatting of lcs, use that line instead.
	.*/
	
	$headersCorrect = true; //Holds whether the headers are correct or not
	
	//Test headers in place of file headers
	//$headerTest = ['p_issn', 'l_issn', 'e_issn', 'title', 'callnumber']; //Example first line of file

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
		$callnumberPos = -1;
		
		$counter = 0; //Current header position
		
		//Headers must be of title, p_issn, l_issn, e_issn, callnumber
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
			else if(strcmp($header,'callnumber') == 0)  {
				if($callnumberPos != -1) { //If callnumber already was assigned, headers are wrong
					$headersCorrect = false;
					drupal_set_message('callnumber column appears twice in header', 'error');
				}
				else { //Otherwise get the position
					$callnumberPos = $counter;
				}
			}
			else { //If the header value is none of the accepted values, header is wrong
				$headersCorrect = false;
				drupal_set_message('Invalid value present in header: ' . $header, 'error');
			}
			
			$counter++; //Increment current header position
		}
	}	
		
	
	if($headersCorrect){ //Only read in data if the headers are correct
	
		$lineCount = 0; //Holds current line number
		$errorCount = 0; //Holds number of invalid lines
		
		foreach($file as $line) {
		//while(! feof($file)) {
			//$line = fgetcsv($file);
			
			$lineCount++; //Increment line counter	
				
			//Read in the line, using the header positions we got earlier
			$title = $line[$titlePos];
			$l_issn = $line[$l_issnPos];
			$p_issn = $line[$p_issnPos];
			$e_issn = $line[$e_issnPos];
			$callnumber = $line[$callnumberPos];
				
			//Verify the data is correct
			$correct = true; //For checking if the data is correct
				
			//Check title
			/*
			if(preg_match($regTitle, $title) == 0 | preg_match($regTitle, $title) == false) {//If the title has unaccepted characters
				$correct = false; 
				
				$test = $form_state->get(['tabledata', $lineCount]);
				if(isset($test)) { //If error is already present on line concatenate the reason
					$reason .= ", Invalid title";
				}
				else { //Otherwise create a new error reason
					$reason = 'Invalid title';
				}
				
				$form_state->set(['tabledata', $lineCount], [$lineCount, $p_issn, $e_issn, $l_issn, $callnumber, $title, $reason]);
			}
			*/
			
			//Check L-ISSN
			if((preg_match($regISSN, $l_issn) == 0 | preg_match($regISSN, $l_issn) == false) && $l_issn != null) {//If the ISSN is not in the right format and something is there
				$correct = false;
				
				$test = $form_state->get(['tabledata', $lineCount]);
				if(isset($test)) { //If error is already present on line concatenate the reason
					$reason .= ", Invalid l_issn";
				}
				else { //Otherwise create a new error reason
					$reason = 'Invalid l_issn';
				}
				
				$form_state->set(['tabledata', $lineCount], [$lineCount, $p_issn, $e_issn, $l_issn, $callnumber, $title, $reason]);
			}
			
			//Check P-ISSN
			if((preg_match($regISSN, $p_issn) == 0 | preg_match($regISSN, $p_issn) == false) && $p_issn != null) {//If the ISSN is not in the right format and something is there
				$correct = false;
				
				$test = $form_state->get(['tabledata', $lineCount]);
				if(isset($test)) { //If error is already present on line concatenate the reason
					$reason .= ", Invalid p_issn";
				}
				else { //Otherwise create a new error reason
					$reason = "Invalid p_issn";
				}
				
				$form_state->set(['tabledata', $lineCount], [$lineCount, $p_issn, $e_issn, $l_issn, $callnumber, $title, $reason]);
			}
			
			//Check E-ISSN
			if((preg_match($regISSN, $e_issn) == 0 | preg_match($regISSN, $e_issn) == false) && $e_issn != null) {//If the ISSN is not in the right format and something is there
				$correct = false;
				
				$test = $form_state->get(['tabledata', $lineCount]);
				if(isset($test)) { //If error is already present on line concatenate the reason
					$reason .= ", Invalid e_issn";
				}
				else { //Otherwise create a new error reason
					$reason = "Invalid e_issn";
				}
				
				$form_state->set(['tabledata', $lineCount], [$lineCount, $p_issn, $e_issn, $l_issn, $callnumber, $title, $reason]);
			}
			//Check LCCN
			
			//to make things easier, we will trim the whitespace out of the callnumber, and check the trimmed call number for validation instead.
			$trimmed_callnumber = str_replace(' ', '', $callnumber);
			
			if((preg_match($regLCCN, $trimmed_callnumber) == 0 | preg_match($regLCCN, $trimmed_callnumber) == false) || $trimmed_callnumber == null) {//If the LCCN is invalid or is missing, line is wrong
				$correct = false;
				
				$test = $form_state->get(['tabledata', $lineCount]);
				if(isset($test)) { //If error is already present on line concatenate the reason
					$reason .= ", Invalid callnumber";
				}
				else { //Otherwise create a new error reason
					$reason = "Invalid callnumber";
				}
				
				$form_state->set(['tabledata', $lineCount], [$lineCount, $p_issn, $e_issn, $l_issn, $callnumber, $title, $reason]);
			}
				
			//Check that at least one ISSN element has data inside
			$existsISSN = false;
			if(($l_issn != null) | ($p_issn != null) | ($e_issn != null)) {
					$existsISSN = true;
			}
			else {
				$test = $form_state->get(['tabledata', $lineCount]);
				if(isset($test)) { //If error is already present on line concatenate the reason
					$reason .= ', No issn values present';
				}
				else { //Otherwise create a new error reason
					$reason = 'No issn values present';
				}
				
				$form_state->set(['tabledata', $lineCount], [$lineCount, $p_issn, $e_issn, $l_issn, $callnumber, $title, $reason]);
				
			}
			
			if(!$correct || !$existsISSN) {
				$form_state->set('lineError', 1); //Note that at least one line has an error, so error table will be displayed
				$errorCount++;
			}	
				
			if($correct && $existsISSN) { //If this line's data is correct and contains at least one ISSN, enter it
				
				//Enter data based on which radio button was pressed
					
				//Add new assignments, nothing special
				if ($issnOption == 0) {
					$dbAdmin->insert($title, $uid, $l_issn, $p_issn, $e_issn, 0, $callnumber);
				}
				//Replace own assignments
				else if ($issnOption == 1) {
					//Do a query for each ISSN type, look for entries with matching user ID
						
					//Search for L-ISSN
					$results = $dbAdmin->selectByISSN($l_issn);
					foreach($results as $entry) {
						if($entry->source == $uid) { //If the uid is matching
							$dbAdmin->deleteById($entry->id);
						}
					}
						
					//Search for P-ISSN
					$results = $dbAdmin->selectByISSN($p_issn);
					foreach($results as $entry) {
						if($entry->source == $uid) { //If the uid is matching
							$dbAdmin->deleteById($entry->id);
						}
					}
						
					//Search for E-ISSN
					$results = $dbAdmin->selectByISSN($p_issn);
					foreach($results as $entry) {
						if($entry->source == $uid) { //If the uid is matching
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
			}
		}
	}
	
	//$fclose($file); //Close the file
	
	if($headersCorrect) {
		drupal_set_message("Upload Complete");
		drupal_set_message("File contained " . $lineCount . " entries");
		if($form_state->get('lineError') == 1) {
			drupal_set_message($errorCount . " entries were invalid and were not uploaded to the database (More info below)", 'warning');
			
		}
		$form_state->set('submitted', 1); //Form has been submitted
		$form_state->setRebuild(); //Update the form elements
	}
	
	return $form;
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
