<?php
namespace Drupal\search_interface\Controller;
use Drupal\Core\Controller\ControllerBase;
/**
* Controller for search interface.
*/

class SearchInterfaceController extends ControllerBase 
{
/**
* Search Interface
*
* @return array
*/
	public function content() {
		return array(
			'#type' => 'markup',
			'#markup' => $this->t('Hello, World!'),
		);
	}
}
?>	