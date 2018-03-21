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
        // After submission, if there were any invalid lines, print them to this table
        if ($form_state->get('submitted') === 1) {
            $config = $this->config('results_page.settings');
            $dbadmin = new DBAdmin();
            $searchtype = $form_state->get('searchtype');
            $searchterm = $form_state->get('searchterm');
            $recordSet = $this->getRecordSet($searchtype, $searchterm);
            
            $records = count($recordSet);
            $form['searchtype'] = array(
                '#type' => 'item',
                '#description' => $searchtype
            );
            $form['searchterm'] = array(
                '#type' => 'item',
                '#description' => $searchterm
            );
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
                '#header' => array(
                    t('Title'),
                    t('Linking ISSN'),
                    t('Print ISSN'),
                    t('Electronic ISSN'),
                    t('LC Call Number'),
                    t('Source')
                )
            );
            
            // Print the values of each row into the table
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
        } else { // If no form data is received, display the input form
            
            $config = $this->config('searchInterface.settings');
            
            $form['file_content'] = [
                '#type' => 'radios',
                '#title' => $this->t('Search By File:'),
                '#options' => [
                    0 => t('ISSN'),
                    1 => t('LCCN'),
                    2 => t('Display Entire Database (for testing...)')
                ],
                '#default_value' => 2
            ];
            
            $form['upload_file'] = [
                '#type' => 'file',
                '#title' => $this->t('')
                // '#multiple' =>
                // '#size' =>
            ];
            
            $form['textbox'] = [
                '#type' => 'textarea',
                '#title' => $this->t('...or Paste A List Below:'),
                '#default_value' => $config->get('textbox')
            ];
            
            $form['quantity'] = [
                '#type' => 'number',
                '#title' => $this->t('# of Previewed Results:'),
                '#default_value' => $this->t('50'),
                '#min' => '1',
                '#max' => '10000',
                '#size' => '5'
            ];
            
            $form['actions']['download'] = [
                '#type' => 'button',
                '#value' => $this->t('Download')
            ];
            
            $form['actions']['display'] = [
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
        
        // Handle submitted values in $form_state here.
        if ($form_state->getValue('file_content') === '0') {
            $searchtype = 'issn';
            $searchterm = $form_state->getValue('textbox');
        } else if ($form_state->getValue('file_content') === '1') {
            $searchtype = 'lccn';
            $searchterm = $form_state->getValue('textbox');
        } else if ($form_state->getValue('file_content') === '2')
            $searchtype = 'all';
        
        $form_state->set('searchterm', $searchterm);
        $form_state->set('searchtype', $searchtype);
        $form_state->set('resultsshown', $form_state->getValue('quantity'));
        
        // drupal_set_message(t('Search Results')); //Found this a little ugly, maybe we'll bring it back at some point
        $form_state->set('submitted', 1);
        $form_state->setRebuild();
        
        return $form;
    }

    public function downloadForm(array &$form, FormStateInterface $form_state)
    {
        //$recordSet = $this->getRecordSet($form_state->get('searchtype'), $form_state->get('searchterm'));
        $dbAdmin = new DBAdmin();
        $recordSet = $dbAdmin->selectAll();
        $fileLocation = "sites/default/files/downloads/"; //recommended this stay the same (NOTE: YOU MUST MANUALLY CREATE THIS FOLDER ONCE)
        $fileName = "Download.csv";
        $file = fopen($fileLocation.$fileName, "w");
        fwrite($file, "Title,Linking ISSN,Print ISSN,Electronic ISSN,LC call number,Source\n"); //write header to file
        foreach($recordSet as $record)
        {
            $printOut = "$record->title,$record->issn_l,$record->p_issn,$record->e_issn,$record->callnumber,\n";
            fwrite($file, $printOut);
        }
        fclose($file);
        $fileName2 = "Download.tsv";
        $file2 = fopen($fileLocation.$fileName2, "w");
        fwrite($file2, "Title\tLinking ISSN\tPrint ISSN\tElectronic ISSN\tLC call number\tSource\n"); //write header to file
        foreach($recordSet as $record)
        {
            $printOut2 = "$record->title\t$record->issn_l\t$record->p_issn\t$record->e_issn\t$record->callnumber\t\n";
            fwrite($file2, $printOut2);
        }
        fclose($file2);
        drupal_set_message(t("RESULT: <p>EXPORT AS: <a href=\"$fileLocation$fileName\">.csv</a>\t<a href=\"$fileLocation$fileName2\">.tsv</a></p>"));
        return $form;
    }
    

    public function getRecordSet($searchtype, $searchterm)
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
                // echo "<br />ISSN after str_replace: " . t($issn);
                
                if (strpos($issn, "-") === false) // If $issn doesn't contain a hyphen
                    $issn = (substr($issn, 0, 4) . '-' . substr($issn, 4, 7)); // Put one there (breaks if anything precedes the issn, cleansing is key here)
                                                                               // echo "<br />ISSN after hyphen check: " . t($issn);
                $newRecordSet = null;
                $newRecordSet = $dbadmin->selectByISSN($issn); // gets a list of results from the next ISSN query
                foreach ($newRecordSet as $record) // goes through that list of results row by row
                {
                    array_push($recordSet, $record); // pushes each additional result on to the grand record set
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
                    array_push($recordSet, $record); // pushes each additional result on to the grand record set
                }
            }
        } else if ($searchtype === 'all')
            $recordSet = $dbadmin->selectAll();
        return $recordSet;
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