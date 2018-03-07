<?php
/**
* @file
* Contains Drupal\searchInterface\Form\SearchInterfaceForm.
*/

namespace Drupal\searchInterface\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SearchInterfaceForm extends ConfigFormBase{
	
    //@{inheritdoc}
	protected function getEditableConfigNames(){
		return ['searchInterface.settings',];
	}
	//@{inheritdoc}
	
	public function getFormId(){
		return 'searchInterface_form';
	}
	
	//@{inheritdoc}
	public function buildForm(array $form, FormStateInterface $form_state){
		$config = $this->config('searchInterface.settings');
	
		$form['file_content'] = [
			'#type' => 'radios',
			'#title' => $this->t('Upload file:'),
			'#options' => [
				'ISSN' => t('ISSN'),
				'LCCN' => t('LCCN'),
			],
		];
		
		$form['upload_file'] = [
			'#type' => 'file',
			'#title' => $this->t(''),
			//'#multiple' => 
			//'#size' =>
		];
	
		$form['issn_text'] = [
			'#type' => 'textarea',
			'#title' => $this->t('...or Paste Your ISSN Query Below:'),
			'#default_value' => $config->get('issn_text'),
		];
		
		$form['quantity'] = [
			'#type' => 'number',
			'#title' => $this->t('# of Results Per Page:'),
			'#default_value' => $this->t('500'),
			'#min' => '1',
			'#max' => '10000',
			'#size' => '5',
		];
		
		$form['result_option'] = [
			'#type' => 'select',
			'#title' => $this->t('Show Result:'),
			'#options' => [
				'MyCon' => t('My Contribution'),
				'MyOrg' => t('My Organization'),
			],
		];

		$form['auth_available'] = [
			'#type' => 'select',
			'#title' => $this->t('Authorities Available:'),
			'#multiple' => 'TRUE',
			'#options' => [
				'loc' => $this->t('Library of Congress'),
				'har' => $this->t('Harvard'),
				'unb' => $this->t('UNB'),
				'upei' => $this->t('UPEI'),
			],
		];
		
		$form['actions']['move_right'] = [
			'#type' => 'button',
			'#value' => $this->t(' >> '),
		];

		$form['actions']['move_left'] = [
			'#type' => 'button',
			'#value' => $this->t(' << '),
		];
		
		$form['auth_included'] = [
			'#type' => 'select',
			'#title' => $this->t('Authorities Available:'),
			'#multiple' => 'TRUE',
			'#options' => [
				'---' => $this->t('---'),
			],
		];
		
		$form['actions']['download'] = [
			'#type' => 'button',
			'#value' => $this->t('Download'),
		];

		$form['actions']['display'] = [
			'#type' => 'button',
			'#value' => $this->t('Display'),
		];		
		
		return parent::buildForm($form, $form_state);
	}
	
	//@{inheritdoc}
	public function submitForm(array &$form, FormStateInterface $form_state){
		parent::submitForm($form, $form_state);
		
		drupal_set_message($this->t('Complete!'));
	}
}