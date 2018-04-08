<?php
namespace Drupal\results_page\Form;

// Required core classes
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

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
        echo print_r($form_state->getValues());
        // ~~~Reception of search filter data below~~~
        $dbadmin = new DBAdmin();
        if ($form_state->get('submitted') === 1) {
            $config = $this->config('results_page.settings');
            
            $searchtype = $form_state->get('searchtype');
            $searchterm = $form_state->get('searchterm');
            $institutions = $form_state->get('institutions');
            $recordSet = $this->getRecordSet($searchtype, $searchterm, $institutions);
            
            $fileLocation = "sites/default/files/downloads/"; // recommended this stay the same (NOTE: YOU MUST MANUALLY CREATE THIS FOLDER ONCE)
            $fileName = "Download.csv";
            $file = fopen($fileLocation . $fileName, "w");
            fwrite($file, "title,l_issn,p_issn,e_issn,lc,source\n"); // write header to file
            $doublequotes = "\"\"";
            foreach ($recordSet as $record) {
                $printOut = $doublequotes . "$record->title" . $doublequotes . ",$record->issn_l,$record->p_issn,$record->e_issn,$record->callnumber,$record->source,\n";
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
            
            $url1 = Url::fromUri('base:sites/default/files/downloads/Download.csv');
            $url2 = Url::fromUri('base:sites/default/files/downloads/Download.tsv');
            
            $form_state->getValue('multiselect');
            $header = [];
            if ($form_state->getValue('editoroptions') === '1' || $form_state->getValue('editoroptions') === '2') {
                $header = array( // Header for editable results page
                    t('Edit'),
                    t('Title'),
                    t(' Linking  ISSN'),
                    t('Print ISSN'),
                    t('Electronic ISSN'), // THE SPACES IN THESE HEADER NAMES ARE NON BREAKING SPACES. Replacing them with regular spaces will mess up the table width.
                    t('LC Call Number'),
                    t('Source'),
                    t('Added By')
                );
            } else {
                $header = array( // Header for read only results page
                    t('Title'),
                    t(' Linking  ISSN'),
                    t('Print ISSN'),
                    t('Electronic ISSN'), // THE SPACES IN THESE HEADER NAMES ARE NON BREAKING SPACES. Replacing them with regular spaces will mess up the table width.
                    t('LC Call Number'),
                    t('Source')
                );
            }
            
            $records = count($recordSet);
            
            $form['download_csv'] = [
                
                '#title' => $this->t('Download as csv'),
                '#type' => 'link',
                '#url' => $url1,
                '#prefix' => '<p><b>',
                '#suffix' => '</b></p>'
            ];
            $form['download_tsv'] = [
                
                '#title' => $this->t('Download as tsv'),
                '#type' => 'link',
                '#url' => $url2,
                '#prefix' => '<p><b>',
                '#suffix' => '</b></p>'
            ];
            $tableclass = array();
            if ($form_state->getValue('editoroptions') === '1' || $form_state->getValue('editoroptions') === '2') {
                $tableclass = [
                    'input-table2'
                ]; // Puts the table more over to the left as it's wider, this gets picked up in tables.css
            } else {
                $tableclass = [
                    'input-table1'
                ]; // Puts the table over to the left... less for the less wide table.
            }
            
            $form['table'] = array(
                '#type' => 'table',
                '#caption' => ('Showing first ' . min($form_state->get('resultsshown'), $records) . ' rows out of ' . $records . ' total results.'),
                '#empty' => 'No results to be shown.',
                '#header' => $header,
                '#attributes' => [
                    'class' => $tableclass
                ]
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
                    
                    $form['table'][$counter]['Title'] = array(
                        '#type' => 'item',
                        '#value' => $record->title,
                        '#description' => $record->title,
                        '#size' => 13
                    
                    );
                    
                    $form['table'][$counter]['Linking ISSN'] = [
                        '#type' => 'container'
                    ];
                    if (! $record->issn_l) {
                        $form['table'][$counter]['Linking ISSN']['editable'] = array(
                            '#type' => 'textfield',
                            '#default_value' => $record->issn_l,
                            '#size' => 8,
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
                            '#value' => $record->issn_l,
                            '#size' => 13,
                            '#states' => array(
                                'visible' => array(
                                    ':input[name="table[' . $counter . '][Edit]"]' => array(
                                        'checked' => FALSE
                                    )
                                )
                            )
                        );
                    } else {
                        $form['table'][$counter]['Linking ISSN']['uneditable'] = array(
                            '#type' => 'item',
                            '#description' => $record->issn_l,
                            '#value' => $record->issn_l,
                            '#size' => 13
                        );
                    }
                    
                    $form['table'][$counter]['Print ISSN'] = [
                        '#type' => 'container'
                    ];
                    if (! $record->p_issn) {
                        $form['table'][$counter]['Print ISSN']['editable'] = array(
                            '#type' => 'textfield',
                            '#default_value' => $record->p_issn,
                            '#size' => 8,
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
                            '#value' => $record->p_issn,
                            '#size' => 13,
                            '#states' => array(
                                'visible' => array(
                                    ':input[name="table[' . $counter . '][Edit]"]' => array(
                                        'checked' => FALSE
                                    )
                                )
                            )
                        );
                    } else {
                        $form['table'][$counter]['Print ISSN']['uneditable'] = array(
                            '#type' => 'item',
                            '#description' => $record->p_issn,
                            '#value' => $record->p_issn,
                            '#size' => 13
                        );
                    }
                    $form['table'][$counter]['Electronic ISSN'] = [
                        '#type' => 'container'
                    ];
                    if (! $record->e_issn) {
                        $form['table'][$counter]['Electronic ISSN']['editable'] = array(
                            '#type' => 'textfield',
                            '#default_value' => $record->e_issn,
                            '#size' => 8,
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
                            '#value' => $record->e_issn,
                            '#size' => 13,
                            '#states' => array(
                                'visible' => array(
                                    ':input[name="table[' . $counter . '][Edit]"]' => array(
                                        'checked' => FALSE
                                    )
                                )
                            )
                        );
                    } else {
                        $form['table'][$counter]['Electronic ISSN']['uneditable'] = array(
                            '#type' => 'item',
                            '#description' => $record->e_issn,
                            '#value' => $record->e_issn,
                            '#size' => 13
                        
                        );
                    }
                    
                    $form['table'][$counter]['LC Call Number'] = [
                        '#type' => 'container'
                    ];
                    $form['table'][$counter]['LC Call Number']['editable'] = array(
                        '#type' => 'textfield',
                        '#default_value' => $record->callnumber,
                        '#size' => 8,
                        '#states' => array(
                            'visible' => array(
                                ':input[name="table[' . $counter . '][Edit]"]' => array(
                                    'checked' => TRUE
                                )
                            )
                        )
                    );
                    $form['table'][$counter]['LC Call Number']['hiddenID'] = [
                        '#type' => 'item',
                        '#value' => $record->id
                    ];
                    $form['table'][$counter]['LC Call Number']['uneditable'] = array(
                        '#type' => 'item',
                        '#description' => $record->callnumber,
                        '#value' => $record->callnumber,
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
                        '#size' => 13
                    );
                    $form['table'][$counter]['Added By'] = [
                        '#type' => 'item',
                        '#description' => \Drupal\user\Entity\User::load($record->user)->getDisplayName(),
                        '#size' => 13
                    ];
                    $form['editbutton'] = [
                        '#type' => 'submit',
                        '#value' => $this->t('Submit Changes'),
                        '#submit' => array(
                            '::submitEdits'
                        )
                    ];
                    
                    $form['editoroptions'] = [ // This ensures when the edits are submitted, we're still in editor mode when the page refreshes
                        '#type' => 'radios',
                        '#title' => '',
                        '#options' => [
                            1 => ''
                        ],
                        '#default_value' => 1,
                        
                    
                    ];
                    $form['searchtype'] = [
                        '#type' => 'value',
                        '#value' => $searchtype
                    ];
                    $form['searchterm'] = [
                        '#type' => 'value',
                        '#value' => $searchterm
                    ];
                    $form['searchtype'] = [
                        '#type' => 'value',
                        '#value' => $searchtype
                    ];
                    
                    $counter ++;
                }
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
                $editorRadioOptions[0] = t('Public search');
                $editorRadioOptions[2] = t('Only display my contributions');
                $editorOptionsTitle = 'Editor Options';
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
                '#default_value' => 0
            ];
            $form['editoroptions'] = [
                '#type' => 'radios',
                '#title' => $editorOptionsTitle,
                '#options' => $editorRadioOptions,
                '#default_value' => 0
            ];
            $form['input_checkbox'] = [
                '#type' => 'checkbox',
                '#title' => $this->t('Search via file upload')
            
            ];
            $form['upload_container'] = [
                '#type' => 'container',
                '#states' => array(
                    'visible' => array(
                        ':input[name="input_checkbox"]' => array(
                            'checked' => TRUE
                        )
                    )
                )
            ];
            
            $form['pastebox'] = [
                '#type' => 'textarea',
                '#default_value' => $config->get('textbox'),
                '#cols' => 10,
                '#states' => array(
                    'visible' => array(
                        ':input[name="input_checkbox"]' => array(
                            'checked' => FALSE
                        )
                    )
                )
            ];
            $form['upload_container']['fileupload'] = [
                '#type' => 'managed_file',
                '#size' => 20,
                '#upload_location' => 'public://uploads/',
                '#upload_validators' => array(
                    'file_validate_extensions' => array(
                        'txt'
                    )
                ),
                '#required' => FALSE,
                '#multiple' => FALSE
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
     * This is the stuff that gets done if the user's input passes validation.
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $dbadmin = new DBAdmin();
        // Handle submitted values in $form_state here.
        if ($form_state->getValue('file_content') === '0') {
            $searchtype = 'issn';
            $searchterm = $form_state->getValue('pastebox'); // This is the text box field
        } else if ($form_state->getValue('file_content') === '1') {
            $searchtype = 'lccn';
            $searchterm = $form_state->getValue('pastebox');
        } else if ($form_state->getValue('file_content') === '2') {
            $searchtype = 'all';
            $searchterm = '';
        }
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
        if ($form_state->getValue('input_checkbox')) // Attempt to take in file input
        {
            $fid = $form_state->getValue('fileupload');
            if (array_key_exists('0', $fid)) // File was in fact uploaded
            {
                $fid = $fid[0];
                $database = \Drupal::database(); // Drupal saves references in its database to all files uploaded via managed_file
                $sql = "SELECT uri FROM file_managed WHERE fid=" . $fid . ";"; // find the uri of the file just added
                $fileURI = db_query($sql);
                $fileLocation = '';
                foreach ($fileURI as $record2) {
                    $fileLocation = $record2->uri;
                    // public:// is the folder that Drupal allows users to download/access files from, etc
                    $fileLocation = str_replace('public://', 'sites/default/files/', $fileLocation); // format the file location properly
                }
                $fileContents = '';
                $fileHandle = fopen($fileLocation, "rw"); // open file
                if ($fileHandle) // if no error
                {
                    while (! feof($fileHandle)) { // until end of file...
                        
                        $record3 = fgets($fileHandle);
                        $fileContents .= $record3 . "\n";
                    }
                }
                $searchterm = $fileContents;
            } else // File upload was chosen but no file was uploaded
            {
                $form_state->set('submitted', 0);
                drupal_set_message('Please upload a file, and try your search again.', 'error');
            }
        }
        
        if (! $form_state->hasValue('searchterm')) {
            $form_state->set('searchterm', $searchterm);
        }
        
        if (! $form_state->hasValue('searchtype')) {
            $form_state->set('searchtype', $searchtype);
        }
        if (! $form_state->hasValue('institutions')) {
            $form_state->set('institutions', $chosenInstList);
        }
        if (! $form_state->hasValue('resultsshown')) {
            $form_state->set('resultsshown', $form_state->getValue('quantity'));
        }
        
        // for ($i = 0; !$form_state->getValue('table'[$i]['Edit']); $i++) //Keeps going until the next checkbox doesn't exist
        // {
        
        // {
        
        // echo $form_state->getValue('table'[$i]['LC Call Number']['editable']);
        // }
        // }
        
        $form_state->set('submitted', 1);
        $form_state->setRebuild();
        
        return $form;
    }

    public function downloadForm(array &$form, FormStateInterface $form_state)
    {
        $recordSet = $this->getRecordSet($form_state->get('searchtype'), $form_state->get('searchterm'), $form_state->get('institutions'));
        $fileLocation = "sites/default/files/downloads/"; // recommended this stay the same (NOTE: YOU MUST MANUALLY CREATE THIS FOLDER ONCE)
        $fileName = "Download.csv";
        $file = fopen($fileLocation . $fileName, "w");
        fwrite($file, "title,l_issn,p_issn,e_issn,lc,source\n"); // write header to file
        $doublequotes = "\"\"";
        foreach ($recordSet as $record) {
            $printOut = $doublequotes . "$record->title" . $doublequotes . ",$record->issn_l,$record->p_issn,$record->e_issn,$record->callnumber,$record->source,\n";
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

    public function submitEdits(array &$form, FormStateInterface $form_state)
    {
        $editLines = [];
        for ($i = 0, $f = 0; array_key_exists($i, $form_state->getValues()['table']); $i ++) {
            if ($form_state->getValues()['table'][$i]['Edit'] === '1') // If this line was checked for editing
            {
                $form['table'][$i]['Edit'] = ['#type' => 'checkbox',
                    '#default_value' => FALSE
                ];//Unchecks checkbox for when page reloads
                $currentLine = $form_state->getValues()['table'][$i];
                $title = $currentLine['Title'];
                
                if (array_key_exists('editable', $currentLine['LC Call Number']))
                    $lc = $currentLine['LC Call Number']['editable'];
                else
                    $lc = $currentLine['LC Call Number']['uneditable'];
                
                if (array_key_exists('editable', $currentLine['Print ISSN']))
                    $p_issn = $currentLine['Print ISSN']['editable'];
                else
                    $p_issn = $currentLine['Print ISSN']['uneditable'];
                
                if (array_key_exists('editable', $currentLine['Electronic ISSN']))
                    $e_issn = $currentLine['Electronic ISSN']['editable'];
                else
                    $e_issn = $currentLine['Electronic ISSN']['uneditable'];
                
                if (array_key_exists('editable', $currentLine['Linking ISSN']))
                    $l_issn = $currentLine['Linking ISSN']['editable'];
                else
                    $l_issn = $currentLine['Linking ISSN']['uneditable'];
                $id = $currentLine['LC Call Number']['hiddenID'];
                
                $editLines[$f] = [
                    $title,
                    $p_issn,
                    $e_issn,
                    $l_issn,
                    $lc,
                    $id,
                ];
                $f ++;
                // echo 'Title: ' . $title . ' Print: ' . $p_issn . ' Electronic: ' . $e_issn . ' Linking ' . $l_issn . ' LC: ' . $lc;
            }
        }
        
        $dbadmin = new DBAdmin();
        for ($g = 0; $g < count($editLines); $g ++) { // insert($title, $l_issn, $p_issn, $e_issn, $lc)
            
            $messages = $dbadmin->insert($editLines[$g][0], $editLines[$g][3], $editLines[$g][1], $editLines[$g][2], $editLines[$g][4]);
            echo print_r($messages);
            if ($messages[0] != '0') // No errors, we can delete the old entry.
            {
                $id = $editLines[$g][5];
                $dbadmin->deleteLCById($id);
                

                
            } else {
                for ($q = 0; $q < count($messages[1]); $q ++) {
                    drupal_set_message($messages[1][$q]);
                }
            }
        }
        $form['editoroptions'] = [ // This ensures when the edits are submitted, we're still in editor mode when the page refreshes
            '#type' => 'radios',
            '#title' => '',
            '#options' => [
                1 => ''
            ],
            '#default_value' => 1,
            
            
        ];
        $form['searchtype'] = [
            '#type' => 'value',
            '#value' => $form_state->getValue($searchtype)
        ];
        $form['searchterm'] = [
            '#type' => 'value',
            '#value' => $form_state->getValue($searchterm)
        ];
        $form_state->setRebuild();
        //return $form;
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
                        if (! array_key_exists('userID', $institutions)) // if the special key userID is not set, continue as normal
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
                        if (! array_key_exists('userID', $institutions)) // if userID is not set, continue as normal
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
                    if (! array_key_exists('userID', $institutions)) // if userID is not set, continue as normal
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

    public function massageFormValues(array $values, array $form, FormStateInterface $form_state)
    {
        for ($i = 0; $i < count($values); $i ++) {
            if (isset($values[$i]['upload_container']['fileupload'])) {
                $values[$i]['fileupload'] = $values[$i]['upload_container']['fileupload'];
            }

        }
        return $values;
    }

    /**
     *
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return [
            'results_page.settings'
        ];
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'results_page_settings';
    }
}
?>
