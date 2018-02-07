<?php

namespace Drupal\search_interface\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Search Interface' Block.
 *
 * @Block(
 *   id = "search_interface_block",
 *   admin_label = @Translation("Search Interface Block"),
 *   category = @Translation("Search Interface"),
 * )
 */
class SearchInterfaceBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#markup' => $this->t('Hello, World!'),
    );
  }
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['search_interface_block_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Who'),
      '#description' => $this->t('Who do you want to say hello to?'),
      '#default_value' => isset($config['search_interface_block_name']) ? $config['search_interface_block_name'] : '',
    );
    return $form;
  }
  
   /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['search_interface_name'] = $values['search_interface_name'];
  }

}