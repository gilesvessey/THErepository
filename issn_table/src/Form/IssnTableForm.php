<?php
namespace Drupal\issn_table\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

// use our custom classes
use Drupal\dbclasses\DBAdmin;
use Drupal\dbclasses\DBRecord;

class IssnTableForm extends FormBase {
	public function getFormId() {
		return 'issn_table_form';
	}
	
	public function buildForm(array $form, FormStateInterface $form_state) {
		
		$dbadmin = new DBAdmin();
		
		$header = [];
		$header = array( // Header for editable ISSN Table.
			t('Linking ISSN'),
            t('Print ISSN'),
            t('Electronic ISSN'),
        );
		
		$form['table'] = array(
			'#type' => 'table',
            '#caption' => 'A complete equivalence list of ISSNs',
            '#empty' => 'No results to be shown.',
            '#header' => $header
        );
		
		return $form;
	}
	
	public function validateForm(array &$form, FormStateInterface $form_state) {
		
	}
	
	public function submitForm(array &$form, FormStateInterface $form_state) {
		
	}
}