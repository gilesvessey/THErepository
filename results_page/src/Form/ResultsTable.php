<?php
namespace Drupal\results_page\Form;

// Required core classes
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

// use our custom classes
use Drupal\dbclasses\DBAdmin;
use Drupal\dbclasses\DBRecord;

class ResultsTable extends ConfigFormBase
{
    
    /**
     * This method puts the form together (defines fields).
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        // ~~~Reception of search filter data below~~~
        $dbadmin = new DBAdmin();
        if ($form_state->get('submitted') === 1) {
            $config = $this->config('results_page.settings');
            
            $searchtype = $form_state->get('searchtype');
            $searchterm = $form_state->get('searchterm');
            $institutions = $form_state->get('institutions');
            $recordSet = $this->getRecordSet($searchtype, $searchterm, $institutions);
            $form_state->getValue('multiselect');
            $header = [];
            if ($form_state->getValue('editoroptions') === '1' || $form_state->getValue('editoroptions') === '2') {
                $header = array( // Header for editable results page
                    t('Edit'),
                    t('Title'),
                    t('Linking ISSN'),
                    t('Print ISSN'),
                    t('Electronic ISSN'),
                    t('LC Call Number'),
                    t('Source'),
                    t('Added By')
                );
            } else {
                $header = array( // Header for read only results page
                    t('Title'),
                    t('Linking ISSN'),
                    t('Print ISSN'),
                    t('Electronic ISSN'),
                    t('LC Call Number'),
                    t('Source')
                );
            }
            
            $records = count($recordSet);
            $form['t_download'] = [
                '#type' => 'submit',
                '#value' => $this->t('Download'),
                '#submit' => array(
                    '::downloadForm'
                )
            ];
            
            $form['table'] = array(
                '#type' => 'table',
                '#caption' => ('Showing first ' . min($form_state->get('resultsshown'), $records) . ' rows out of ' . $records . ' total results.'),
                '#empty' => 'No results to be shown.',
                '#header' => $header
            );
            
            // ~~~Code for inputting data into the table below~~~
            if ($form_state->getValue('editoroptions') === '1' || $form_state->getValue('editoroptions') === '2') // This displays the user's institution's data (inst editors and up only)
            {
                $counter = 0;
                foreach ($recordSet as $record) {
                    
                    if ($counter >= $form_state->get('resultsshown')) // This is what stops the page from displaying more than your requested num of results
                        break;
                        $form['table'][$counter]['Edit'] = [ // Edit checkbox
                            '#type' => 'checkbox',
                            '#default_value' => FALSE
                        ];
                        
                        $form['table'][$counter]['Title'] = [ // Container to told editable/ uneditable form fields
                            '#type' => 'container'
                        ];
                        $form['table'][$counter]['Title']['editable'] = array(
                            '#type' => 'textfield',
                            '#default_value' => $record->title,
                            '#size' => 13,
                            '#states' => array(
                                'visible' => array(
                                    ':input[name="table[' . $counter . '][Edit]"]' => array(
                                        'checked' => TRUE
                                    )
                                )
                            )
                        );
                        $form['table'][$counter]['Title']['uneditable'] = array(
                            '#type' => 'item',
                            '#description' => $record->title,
                            '#size' => 13,
                            '#states' => array(
                                'visible' => array(
                                    ':input[name="table[' . $counter . '][Edit]"]' => array(
                                        'checked' => FALSE
                                    )
                                )
                            )
                        );
                        $form['table'][$counter]['Linking ISSN'] = [
                            '#type' => 'container'
                        ];
                        $form['table'][$counter]['Linking ISSN']['editable'] = array(
                            '#type' => 'textfield',
                            '#default_value' => $record->issn_l,
                            '#size' => 13,
                            '#states' => array(
                                'visible' => array(
                                    ':input[name="table[' . $counter . '][Edit]"]' => array(
                                        'checked' => TRUE
                                    )
                                )
                            )
                        );
                        $form['table'][$counter]['Linking ISSN']['uneditable'] = array(
                            '#type' => 'item',
                            '#description' => $record->issn_l,
                            '#size' => 13,
                            '#states' => array(
                                'visible' => array(
                                    ':input[name="table[' . $counter . '][Edit]"]' => array(
                                        'checked' => FALSE
                                    )
                                )
                            )
                        );
                        $form['table'][$counter]['Print ISSN'] = [
                            '#type' => 'container'
                        ];
                        $form['table'][$counter]['Print ISSN']['editable'] = array(
                            '#type' => 'textfield',
                            '#default_value' => $record->p_issn,
                            '#size' => 13,
                            '#states' => array(
                                'visible' => array(
                                    ':input[name="table[' . $counter . '][Edit]"]' => array(
                                        'checked' => TRUE
                                    )
                                )
                            )
                        );
                        $form['table'][$counter]['Print ISSN']['uneditable'] = array(
                            '#type' => 'item',
                            '#description' => $record->p_issn,
                            '#size' => 13,
                            '#states' => array(
                                'visible' => array(
                                    ':input[name="table[' . $counter . '][Edit]"]' => array(
                                        'checked' => FALSE
                                    )
                                )
                            )
                        );
                        $form['table'][$counter]['Electronic ISSN'] = [
                            '#type' => 'container'
                        ];
                        $form['table'][$counter]['Electronic ISSN']['editable'] = array(
                            '#type' => 'textfield',
                            '#default_value' => $record->e_issn,
                            '#size' => 13,
                            '#states' => array(
                                'visible' => array(
                                    ':input[name="table[' . $counter . '][Edit]"]' => array(
                                        'checked' => TRUE
                                    )
                                )
                            )
                        );
                        $form['table'][$counter]['Electronic ISSN']['uneditable'] = array(
                            '#type' => 'item',
                            '#description' => $record->e_issn,
                            '#size' => 13,
                            '#states' => array(
                                'visible' => array(
                                    ':input[name="table[' . $counter . '][Edit]"]' => array(
                                        'checked' => FALSE
                                    )
                                )
                            )
                        );
                        
                        $form['table'][$counter]['LC Call Number'] = [
                            '#type' => 'container'
                        ];
                        $form['table'][$counter]['LC Call Number']['editable'] = array(
                            '#type' => 'textfield',
                            '#default_value' => $record->callnumber,
                            '#size' => 13,
                            '#states' => array(
                                'visible' => array(
                                    ':input[name="table[' . $counter . '][Edit]"]' => array(
                                        'checked' => TRUE
                                    )
                                )
                            )
                        );
                        $form['table'][$counter]['LC Call Number']['uneditable'] = array(
                            '#type' => 'item',
                            '#description' => $record->callnumber,
                            '#size' => 13,
                            '#states' => array(
                                'visible' => array(
                                    ':input[name="table[' . $counter . '][Edit]"]' => array(
                                        'checked' => FALSE
                                    )
                                )
                            )
                        );
                        $form['table'][$counter]['Source'] = array(
                            '#type' => 'item',
                            '#description' => $record->source,
                            '#size' => 13,
                        );
                        $form['table'][$counter]['Added By'] = [
                            '#type' => 'item',
                            '#default_value' => $user = \Drupal\user\Entity\User::load($record->user)->getDisplayName(),
                            '#size' => 13
                        ];
                }
                $counter ++;
            } else // This displays a read only results page
            {
                $counter = 0;
                foreach ($recordSet as $record) {
                    
                    if ($counter >= $form_state->get('resultsshown')) // This is what stops the page from displaying more than your requested num of results
                        break;
                        
                        $form['table'][$counter]['Title'] = array(
                            '#type' => 'item',
                            '#description' => $record->title
                        );
                        
                        $form['table'][$counter]['Linking ISSN'] = array(
                            '#type' => 'item',
                            '#description' => $record->issn_l
                        );
                        
                        $form['table'][$counter]['Print ISSN'] = array(
                            '#type' => 'item',
                            '#description' => $record->p_issn
                        );
                        
                        $form['table'][$counter]['Electronic ISSN'] = array(
                            '#type' => 'item',
                            '#description' => $record->e_issn
                        );
                        
                        $form['table'][$counter]['LC Call Number'] = array(
                            '#type' => 'item',
                            '#description' => $record->callnumber
                        );
                        
                        $form['table'][$counter]['Source'] = array(
                            '#type' => 'item',
                            '#description' => $record->source
                        );
                        
                        $counter ++;
                }
            }
        } else { // Search filter details below
            
            $instList = $dbadmin->getInstitutions();
            $editorOptionsTitle = '';
            $editorRadioOptions = [];
            $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
            $uid = $user->get('uid')->value;
            $userInst = $dbadmin->getUserInstitution($uid);
            if ($user->hasRole('authenticated')) { // This is for regular editors and above only
                $editorRadioOptions[0] = t('Standard search');
                $editorRadioOptions[2] = t('Only display my contributions');
                $editorOptionsTitle = 'Editorial Options';
            }
            if ($user->hasRole('editorial_user')) { // This is for institutional editors and above only
                $editorRadioOptions[1] = t('Display all contributions from ' . $userInst);
            }
            
            $config = $this->config('searchInterface.settings');
            
            $form['file_content'] = [
                '#type' => 'radios',
                '#title' => $this->t('Data type provided:'),
                '#options' => [
                    0 => t('ISSN'),
                    1 => t('LC'),
                    2 => t('Display Entire Database (for testing...)')
                ],
                '#default_value' => 2
            ];
            $form['editoroptions'] = [
                '#type' => 'radios',
                '#title' => $editorOptionsTitle,
                '#options' => $editorRadioOptions,
                '#default_value' => 0
            ];
            
            $form['inputs_table'] = [
                '#type' => 'table',
                '#header' => [
                    t('Paste a list...'),
                    t('...or search with a file')
                ]
            ];
            $form['inputs_table'][0]['Paste a list...'] = [
                '#type' => 'textarea',
                '#default_value' => $config->get('textbox'),
                '#size' => 5
            ];
            $form['inputs_table'][0]['...or search with a file'] = [
                '#type' => 'container',
                '#states' => array(
                    'required' => array(
                        ':input[name="editoroptions"]' => array(
                            'value' => '0'
                        )
                    )
                )
            ];
            $form['inputs_table'][0]['...or search with a file']['fileupload'] = [
                '#type' => 'managed_file',
                '#size' => 20,
                '#upload_location' => 'public://uploads/',
                '#upload_validators' => array(
                    'file_validate_extensions' => array(
                        'txt'
                    )
                ),
                '#required' => FALSE
            ];
            
            $form['multiselect'] = [
                '#type' => 'select',
                '#options' => $instList,
                '#multiple' => TRUE,
                '#validated' => TRUE,
                '#states' => array(
                    'visible' => array(
                        ':input[name="editoroptions"]' => array(
                            'value' => '0'
                        )
                    )
                )
            ];
            
            $form['quantity'] = [
                '#type' => 'number',
                '#title' => $this->t('# of Previewed Results:'),
                '#default_value' => $this->t('50'),
                '#min' => '1',
                '#max' => '10000',
                '#size' => '5'
            ];
            
            $form['download'] = [
                '#type' => 'button',
                '#value' => $this->t('Download')
                
            ];
            
            $form['display'] = [
                '#type' => 'submit',
                '#value' => $this->t('Display')
                
            ];
        }
        return $form;
    }
    
    /**
     * This method will be called automatically upon submission.
     * This is the shit that gets done if the user's input passes validation.
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $dbadmin = new DBAdmin();
        $input_table = $form_state->getValue('inputs_table');
        // Handle submitted values in $form_state here.
        if ($form_state->getValue('file_content') === '0') {
            $searchtype = 'issn';
            $searchterm = $input_table[0]['Paste a list...']; // This is the text box field
        } else if ($form_state->getValue('file_content') === '1') {
            $searchtype = 'lccn';
            $searchterm = $input_table[0]['Paste a list...'];
        } else if ($form_state->getValue('file_content') === '2')
            $searchtype = 'all';
            
            if ($form_state->getValue('editoroptions') === '1') // All lines from an inst editor's institution
            {
                
                $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
                $uid = $user->get('uid')->value;
                $chosenInstList = array();
                $userInst = $dbadmin->getUserInstitution($uid);
                $chosenInstList[0] = $userInst;
            } else if ($form_state->getValue('editoroptions') === '2') // Only that user's lines will be displayed
            {
                
                $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
                $uid = $user->get('uid')->value;
                $chosenInstList = array();
                $userInst = $dbadmin->getUserInstitution($uid);
                $chosenInstList[0] = $userInst;
                $chosenInstList['userID'] = $uid; // This key is only assigned when we need to filter by user
            } else // Standard search, grabs the institution list from the selection boxes
            {
                $multiselect = $form_state->getValue('multiselect');
                $instList = $dbadmin->getInstitutions();
                $chosenInstList = array();
                $f = 0;
                foreach ($multiselect as $key) {
                    $chosenInstList[$f] = $instList[$key];
                    $f ++;
                }
            }
            
            $form_state->set('searchterm', $searchterm);
            $form_state->set('searchtype', $searchtype);
            $form_state->set('institutions', $chosenInstList);
            $form_state->set('resultsshown', $form_state->getValue('quantity'));
            $form_state->set('submitted', 1);
            $form_state->setRebuild();
            
            $database = \Drupal::database(); // Drupal saves references in its database to all files uploaded via managed_file
            $sql = "SELECT uri FROM file_managed WHERE status=0 ORDER BY fid DESC LIMIT 1;"; // find the uri of the file just added
            $fileURI = db_query($sql);
            $fileLocation = '';
            $fileExtension = '';
            
            foreach ($fileURI as $record2) {
                $fileLocation = $record2->uri;
                // public:// is the folder that Drupal allows users to download/access files from, etc
                $fileLocation = str_replace('public://', 'sites/default/files/', $fileLocation); // format the file location properly
            }
            
            $file = []; // instantiate $file which will be an array of arrays
            $headers = [];
            $isHeader = TRUE;
            
            $fileHandle = fopen($fileLocation, "rw"); // open file
            if ($fileHandle) // if no error
            {
                while (! feof($fileHandle)) { // until end of file...
                    if ($form_state->getValue('inputs_table'[0]['...or search with a file']) != null);
                    $record3 = fgets($fileHandle);
                    echo $record3;
                }
            }
            
            return $form;
    }
    
    public function downloadForm(array &$form, FormStateInterface $form_state)
    {
        $recordSet = $this->getRecordSet($form_state->get('searchtype'), $form_state->get('searchterm'), $form_state->get('institutions'));
        $fileLocation = "sites/default/files/downloads/"; // recommended this stay the same (NOTE: YOU MUST MANUALLY CREATE THIS FOLDER ONCE)
        $fileName = "Download.csv";
        $file = fopen($fileLocation . $fileName, "w");
        fwrite($file, "Title,Linking ISSN,Print ISSN,Electronic ISSN,LC call number,Source\n"); // write header to file
        foreach ($recordSet as $record) {
            $printOut = "$record->title,$record->issn_l,$record->p_issn,$record->e_issn,$record->callnumber,$record->source,\n";
            fwrite($file, $printOut);
        }
        fclose($file);
        $fileName2 = "Download.tsv";
        $file2 = fopen($fileLocation . $fileName2, "w");
        fwrite($file2, "Title\tLinking ISSN\tPrint ISSN\tElectronic ISSN\tLC call number\tSource\n"); // write header to file
        foreach ($recordSet as $record) {
            $printOut2 = "$record->title\t$record->issn_l\t$record->p_issn\t$record->e_issn\t$record->callnumber\t$record->source\t\n";
            fwrite($file2, $printOut2);
        }
        fclose($file2);
        drupal_set_message(t("RESULT: <p>EXPORT AS: <a href=\"$fileLocation$fileName\">.csv</a>\t<a href=\"$fileLocation$fileName2\">.tsv</a></p>"));
        // return $form;
    }
    
    public function getRecordSet($searchtype, $searchterm, $institutions)
    {
        $dbadmin = new DBAdmin();
        // ~~~ISSN specific input cleansing below~~~
        
        if ($searchtype === 'issn') {
            // $recordSet = $dbadmin->selectByISSN($searchterm);
            $recordSet = array();
            $issn_array = preg_split('/[\s]+/', $searchterm);
            foreach ($issn_array as $issn) // turn the list of ISSNs into an array of ISSNs
            {
                $pattern = '/\s*/m';
                $issn = preg_replace($pattern, '', t($issn)); // Removes any form of white space from the ISSN we're searching for
                if (strlen($issn) < 8) // Don't search for this input if it's 7 chars or less. (newlines were getting searched for and returning everything in addition. )
                    continue;
                    
                    if (strpos($issn, "-") === false) // If $issn doesn't contain a hyphen
                        $issn = (substr($issn, 0, 4) . '-' . substr($issn, 4, 7)); // Put one there (breaks if anything precedes the issn, cleansing is key here)
                        
                        $newRecordSet = null;
                        $newRecordSet = $dbadmin->selectByISSN($issn); // gets a list of results from the next ISSN query
                        
                        foreach ($newRecordSet as $record) // goes through that list of results row by row
                        {
                            if (in_array($record->source, $institutions, FALSE)) {
                                if (! $institutions['userID']) // if the special key userID is not set, continue as normal
                                {
                                    array_push($recordSet, $record);
                                } else // if it is set, only push records that are from that user ID
                                {
                                    if ($record->user === $institutions['userID']) // Checks if that line was uploaded by the current user, only adds line if it was.
                                        array_push($recordSet, $record);
                                }
                            }
                        }
            }
        } // ~~~LCCN specific input cleansing below~~~
        else if ($searchtype === 'lccn') {
            $recordSet = array();
            $lccn_array = preg_split('/[\s]+/', $searchterm);
            
            foreach ($lccn_array as $lccn) // turn the list of ISSNs into an array of ISSNs
            {
                $pattern = '/\s*/m';
                $lccn = preg_replace($pattern, '', t($lccn)); // Removes any form of white space from the LC we're searching for
                
                $newRecordSet = null;
                $newRecordSet = $dbadmin->selectByLC($lccn); // gets a list of results from the next LC query
                foreach ($newRecordSet as $record) // goes through that list of results row by row
                {
                    if (in_array($record->source, $institutions, FALSE)) {
                        if (! $institutions['userID']) // if userID is not set, continue as normal
                        {
                            array_push($recordSet, $record);
                        } else // if it is set, only push records that are from that user ID
                        {
                            if ($record->user === $institutions['userID']) // Checks if that line was uploaded by the current user, only adds line if it was.
                                array_push($recordSet, $record);
                        }
                    }
                }
            } // ~~~Select all specific input cleansing below~~~
        } else {
            $recordSet = array();
            $newRecordSet = $dbadmin->selectAll();
            
            foreach ($newRecordSet as $record) // goes through that list of results row by row
            {
                if (in_array($record->source, $institutions, FALSE)) {
                    if (! $institutions['userID']) // if userID is not set, continue as normal
                    {
                        array_push($recordSet, $record);
                    } else // if it is set, only push records that are from that user ID
                    {
                        if ($record->user === $institutions['userID']) { // Checks if that line was uploaded by the current user, only adds line if it was.
                            array_push($recordSet, $record);
                        }
                    }
                }
            }
        }
        return $recordSet;
    }
    protected function getEditableConfigNames()
    {
        return [
            'results_page.settings'
        ];
    }
    public function getFormId()
    {
        return 'results_page_settings';
    }
}
?>