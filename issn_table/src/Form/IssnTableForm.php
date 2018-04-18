<?php
namespace Drupal\issn_table\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

// use our custom classes
use Drupal\dbclasses\DBAdmin;
use Drupal\dbclasses\DBRecord;

class IssnTableForm extends FormBase
{
    
    public function getFormId()
    {
        return 'issn_table_form';
    }
    
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        
        
        $form['upload_link'] = [
            '#type' => 'item',
            '#markup' => "See also: <a href='issn_upload'>ISSN Linking List Upload Page</a>",
        ];
        $dbadmin = new DBAdmin();
        if ($form_state->get('submitted') === 1) {
            $values = $form_state->getValues();
            $searchterm = $values['searchterm'];
            $searchtype = $values['searchtype'];
            $recordSet = $this->getRecordSet($searchterm, $searchtype);
            $header = [];
            $header = array( // Header for editable ISSN Table.
                t('Modify'),
                t('Title'),
                t('Linking ISSN'),
                t('Print ISSN'),
                t('Electronic ISSN')
            );
            
            $form['table'] = array(
                '#type' => 'table',
                '#caption' => 'A complete equivalence list of ISSNs',
                '#empty' => 'No results to be shown.',
                '#header' => $header
            );
            
            $counter = 0;
            foreach ($recordSet as $record) {
                $form['table'][$counter]['Edit'] = [ // Edit checkbox
                    '#type' => 'checkbox',
                    '#default_value' => FALSE
                ];
                $title = str_replace("\"", '', $record[4]);
                $form['table'][$counter]['Title'] = [
                    '#type' => 'container'
                ];
                $form['table'][$counter]['Title']['uneditable'] = [
                    '#type' => 'item',
                    '#description' => $title,
                    '#value' => $title,
                    '#states' => array(
                        'visible' => array(
                            ':input[name="table[' . $counter . '][Edit]"]' => array(
                                'checked' => FALSE
                            )
                        )
                    )
                ];
                $form['table'][$counter]['Title']['editable'] = [
                    '#type' => 'textfield',
                    '#default_value' => $title,
                    '#size' => 15,
                    '#states' => array(
                        'visible' => array(
                            ':input[name="table[' . $counter . '][Edit]"]' => array(
                                'checked' => TRUE
                            )
                        )
                    )
                    
                ];
                $form['table'][$counter]['Title']['issnID'] = [
                    '#type' => 'value',
                    '#value' => $record[0]
                ];
                $form['table'][$counter]['Linking ISSN'] = [
                    '#type' => 'container'
                ];
                $form['table'][$counter]['Linking ISSN']['uneditable'] = [
                    '#type' => 'item',
                    '#description' => $record[1],
                    '#value' => $record[1],
                    '#states' => array(
                        'visible' => array(
                            ':input[name="table[' . $counter . '][Edit]"]' => array(
                                'checked' => FALSE
                            )
                        )
                    )
                ];
                $form['table'][$counter]['Linking ISSN']['editable'] = [
                    '#type' => 'textfield',
                    '#default_value' => $record[1],
                    '#size' => 8,
                    '#states' => array(
                        'visible' => array(
                            ':input[name="table[' . $counter . '][Edit]"]' => array(
                                'checked' => TRUE
                            )
                        )
                    )
                    
                ];
                
                $form['table'][$counter]['Print ISSN'] = [
                    '#type' => 'container'
                ];
                $form['table'][$counter]['Print ISSN']['uneditable'] = [
                    '#type' => 'item',
                    '#description' => $record[2],
                    '#value' => $record[2],
                    '#states' => array(
                        'visible' => array(
                            ':input[name="table[' . $counter . '][Edit]"]' => array(
                                'checked' => FALSE
                            )
                        )
                    )
                ];
                $form['table'][$counter]['Print ISSN']['editable'] = [
                    '#type' => 'textfield',
                    '#default_value' => $record[2],
                    '#size' => 8,
                    '#states' => array(
                        'visible' => array(
                            ':input[name="table[' . $counter . '][Edit]"]' => array(
                                'checked' => TRUE
                            )
                        )
                    )
                    
                ];
                $form['table'][$counter]['Electronic ISSN'] = [
                    '#type' => 'container'
                ];
                $form['table'][$counter]['Electronic ISSN']['uneditable'] = [
                    '#type' => 'item',
                    '#description' => $record[3],
                    '#value' => $record[3],
                    '#states' => array(
                        'visible' => array(
                            ':input[name="table[' . $counter . '][Edit]"]' => array(
                                'checked' => FALSE
                            )
                        )
                    )
                ];
                $form['table'][$counter]['Electronic ISSN']['editable'] = [
                    '#type' => 'textfield',
                    '#default_value' => $record[3],
                    '#size' => 8,
                    '#states' => array(
                        'visible' => array(
                            ':input[name="table[' . $counter . '][Edit]"]' => array(
                                'checked' => TRUE
                            )
                        )
                    )
                    
                ];
                
                $form['modifyoptions'] = [
                    '#type' => 'select',
                    '#options' => [
                        'Edit selected entries',
                        'Delete selected entries'
                    ],
                    '#attributes' => [
                        'class' => [
                            'modifyoptions'
                        ]
                    ]
                ];
                $form['editbutton'] = [
                    '#type' => 'submit',
                    '#value' => $this->t('Submit Changes')
                    
                ];
                
                $form['searchterm'] = [
                    '#type' => 'value',
                    '#value' => $searchterm,
                    
                ];
                
                $counter ++;
            }
        } else // End results
        {
            // Search filter here
            $form['searchterm'] = [
                '#title' => 'Enter your query below...',
                '#type' => 'textfield'
                
            ];
            $form['searchtype'] = [
                '#type' => 'radios',
                '#title' => 'Search by...',
                '#options' => [
                    0 => 'ISSN',
                    1 => 'Title'
                ],
                '#default_value' => 0
            ];
            $form['display'] = [
                '#type' => 'submit',
                '#value' => $this->t('Fetch Results')
                
            ];
        }
        
        return $form;
    }
    
    public function validateForm(array &$form, FormStateInterface $form_state)
    {}
    
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValues();
        $dbadmin = new DBAdmin();
        //Edit submitting stuff below
        if (array_key_exists('Edit', $values['table'][0]))
        {
            
            
            $editLines = [];
            for ($i = 0, $f = 0; array_key_exists($i, $form_state->getValues()['table']); $i ++) {
                if ($form_state->getValues()['table'][$i]['Edit'] === '1') // If this line was checked for editing
                {
                    
                    
                    $currentLine = $form_state->getValues()['table'][$i];
                    $title = $currentLine['Title']['editable'];
                    
                    
                    
                    
                    $p_issn = $currentLine['Print ISSN']['editable'];
                    
                    
                    
                    $e_issn = $currentLine['Electronic ISSN']['editable'];
                    
                    
                    
                    $l_issn = $currentLine['Linking ISSN']['editable'];
                    
                    $id = $currentLine['Title']['issnID'];
                    
                    $editLines[$f] = [
                        $title,
                        $l_issn,
                        $p_issn,
                        $e_issn,
                        $id,
                    ];
                    $f ++;
                    // echo 'Title: ' . $title . ' Print: ' . $p_issn . ' Electronic: ' . $e_issn . ' Linking ' . $l_issn . ' LC: ' . $lc;
                }
            }
            $deleteCount = 0;
            $editCount = 0;
            for ($g = 0; $g < count($editLines); $g ++) { // insert($title, $l_issn, $p_issn, $e_issn, $lc)
                if ($form_state->getValue('modifyoptions') === '0') //We're editing
                {
                    $messages = $dbadmin->editISSN($editLines[$g][4], $editLines[$g][1], $editLines[$g][2], $editLines[$g][3], $editLines[$g][0]);
                    if ($messages[0] != '0') // No errors, we can delete the old entry.
                    {
                        $editCount++;
                        
                        
                    } else {
                        for ($q = 0; $q < count($messages[1]); $q ++) {
                            drupal_set_message($messages[1][$q], 'error');
                        }
                    }
                }
                
                else //We're deleting
                {
                    $dbadmin->deleteISSN($editLines[$g][4]);
                    $deleteCount++;
                    
                }
            }
            if ($deleteCount != 0 && $deleteCount != 1)
                drupal_set_message($deleteCount . ' entries were deleted!');
                if ($deleteCount === 1)
                    drupal_set_message('One entry was deleted!');
                    
                    if ($editCount != 0 && $editCount != 1)
                        drupal_set_message($editCount . ' entries were edited successfully!');
                        if ($editCount === 1)
                            drupal_set_message('One entry was edited successfully!');
                            
                            
                            
                            $form['searchtype'] = [
                                '#type' => 'value',
                                '#value' => $form_state->getValue('searchtype')
                            ];
                            $form['searchterm'] = [
                                '#type' => 'value',
                                '#value' => $form_state->getValue('searchterm')
                            ];
                            
                            
                            
                            
                            
                            $input = $form_state->getUserInput();
                            unset($input['table']); //Ensures the previously generated table is wiped so we can repopulate it after the edit
                            unset($input['modifyoptions']);
                            $form_state->setUserInput($input);
                            
                            
        }
        
        
        $form_state->set('submitted', 1);
        $form_state->setRebuild();
    }
    
    public function getRecordSet($searchterm, $searchtype)
    {
        $dbadmin = new DBAdmin();
        $recordSet = $dbadmin->selectAllISSN();
        
        $tableData = [];
        $i = 0;
        
        foreach ($recordSet as $record) {
            if ($searchtype === '0') // searching by issn
            {
                $pattern = '/\s*/m';
                $searchterm = preg_replace($pattern, '', t($searchterm)); // Removes any form of white space from the ISSN we're searching for
                if (strpos($searchterm, "-") === false) // If $issn doesn't contain a hyphen
                    $searchterm = (substr($searchterm, 0, 4) . '-' . substr($searchterm, 4, 7));
                    if ($record[1] === $searchterm || $record[2] === $searchterm || $record[3] === $searchterm) {
                        $tableData[$i] = $record;
                        $i ++;
                    }
            }
            else // Searching by title
            {
                $title = str_replace("\"", '', $record[4]);
                if (stripos($title, $searchterm) !== false) {
                    $tableData[$i] = $record;
                    $i ++;
                }
            }
        }
        return $tableData;
    }
}