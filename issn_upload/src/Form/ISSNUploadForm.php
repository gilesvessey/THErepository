<?php
namespace Drupal\issn_upload\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dbclasses\DBAdmin;
use Drupal\dbclasses\DBRecord;
use Drupal\Core\File\File;
class ISSNUploadForm extends FormBase {
	
  public function buildForm(array $form, FormStateInterface $form_state) {
	
	  
	//Link to info page. Opens in a new window/tab
	$form['info_link'] = [
		'#type' => 'item',
		'#markup' => "<a href='about_upload' target='_blank'>For more info about upload requirements, click here.</a>",
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
			
			//Title
			$form['table'][$counter]['title'] = array(
				'#type' => 'item',
				'#description' => $row[4],
			);
			
			//Reason for line being declined
			$form['table'][$counter]['Reason(s)'] = array(
				'#type' => 'item',
				'#description' => $row[5],
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
				
		//Submit button
		$form['submit'] = [
			'#type' => 'submit', //standard form button for submission
			'#value' => t('Submit'), //the text printed on the submit button
		];
	}
	
    return $form;
  }
	
	
  public function validateForm(array &$form, FormStateInterface $form_state) {
	
	}
	
  public function submitForm(array &$form, FormStateInterface $form_state) {
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
			$record = fgets($fileHandle); //each line in the file is one record
			
			if($isHeader) //first line of file is header...
			{
				$tempHeaders = [];
				array_push($tempHeaders,explode("$delimiter",$record));
				$headers[0] = trim($tempHeaders[0][0]); //trim to make sure no trailing white space or line breaks
				$headers[1] = trim($tempHeaders[0][1]);
				$headers[2] = trim($tempHeaders[0][2]);
				$headers[3] = trim($tempHeaders[0][3]);
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
	
	$headersCorrect = true; //Holds whether the headers are correct or not
	
	//Check that there are 4 elements in the header
	if(count($headers) != 4) {
		$headersCorrect = false;
		$numOfHeaders = count($headers);
		drupal_set_message("Wrong number of elements in header ($numOfHeaders present instead of 4)", 'error');
	}
	else {
		//Get positions for each piece of input data based on the headers
		$titlePos = -1;
		$p_issnPos = -1;
		$l_issnPos = -1;
		$e_issnPos = -1;
	
		$counter = 0; //Current header position
		
		//Headers must be of title, p_issn, l_issn, e_issn
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
			else { //If the header value is none of the accepted values, header is wrong
				$headersCorrect = false;
				drupal_set_message('Invalid value present in header: ' . $header, 'error');
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
			
			$reason = ""; //Holds reasons for error if there is one
	
					
			$insert = $dbAdmin->insertISSN($l_issn, $p_issn, $e_issn, $title);
			if($insert[0] == 0) { //If there are errors
				foreach($insert[1] as $error) { //Add them to reason
					$reason .= $error . ', ';
				}
				//Trim whitespace before uploading reason error line, helps with the output file
				$l_issn = trim($l_issn);
				$p_issn = trim($p_issn);
				$e_issn = trim($e_issn);
				$title = trim($title);
				$reason = '"' . $reason . '"'; //Put the reason in quotes
				$form_state->set(['tabledata', $lineCount], [$lineCount, $p_issn, $e_issn, $l_issn, $title, $reason]);
				$errorCount++;
			}	
			
			
		}
		
		if($errorCount > 0)
			$form_state->set('lineError', 1); //Note there is one error at least
		
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
	$fileLocation = "sites/default/files/downloads/"; // recommended this stay the same (NOTE: YOU MUST MANUALLY CREATE THIS FOLDER ONCE)
	$fileName = "InvalidsISSN". uniqid() .".txt";
	$file = fopen($fileLocation . $fileName, "w");
	fwrite($file, "Line#,p_issn,e_issn,l_issn,title,Reason(s)\n"); //write header to file
	foreach($form_state->get('tabledata') as $row) {
        $printOut = "$row[0],$row[1],$row[2],$row[3],$row[4],$row[5]\n"; //write each invalid line
        fwrite($file, $printOut);
	}
	fclose($file);
	
	//Serve the file to the user
	header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.$fileName.'"');
	readfile($fileLocation . $fileName);
	
	// send an email message when done
	$user = \Drupal::currentUser();
	$to = $user->getEmail();
	$from = "no-reply@issn.researchspaces.ca"; //this can be changed to a real address if desired
	$subject = "ISSN Upload Report";
	$body = "Your file has been processed. Your report is available <a href='http://www.issn.researchspaces.ca/".$fileLocation . $fileName."'>HERE</a>.";
	simple_mail_send($from, $to, $subject, $body);
		
	exit;
  }
  
  protected function getEditableConfigNames() {
    return [
      'issn_upload_form.settings',
    ];
  }
  
  public function getFormId() {
    return 'issn_upload_form';
  }
}
?>