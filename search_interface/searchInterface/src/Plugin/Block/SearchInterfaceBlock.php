<?php
/*
* @file
* Contains Drupal\searchInterface\Plugin\Block\searchInterfaceBlock
*/

namespace Drupal\searchInterface\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
* Provides a 'Search Interface' block.
*
* @Block(
*   id = "search_interface_block",
*   admin_label = @Translation("Search Interface Block"),
*   category = @Translation("Forms"),
* )
*/

class SearchInterfaceBlock extends BlockBase {
	
	public function build() {
		
	//	$form = \Drupal::formBuilder()->getForm('Drupal\searchInterface\Form\SearchInterfaceForm');
		
		return ['#markup' => $this->t('Search Interface Place Here'),];
	}
	
	protected function blockAcces(AccountInterface $account) {
		return AccessResult::allowedIfHasPermission($account, 'access content');
	}
	
	public function blockForm($form, FormStateInterface $form_state) {
		$config = $this->getConfiguration();
		
		return $form;
	}
	
	public function blockSubmit($form, FormStateInterface $form_state) {
		$this->configuration['searchInterface_settings' = $form_state->getValue('searchInterface_settings');
}