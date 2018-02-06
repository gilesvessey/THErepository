<?php
namespace Drupal\issn_database_installer\Controller;
use Drupal\Core\Controller\ControllerBase;

/**
* Controller for the salutation message.
*/

class IssnDatabaseInstallerController extends ControllerBase 
{
/**
* issn_database_installer.
*
* @return string
*/
	public function installed() 
	{
		return ['#markup' => $this->t('Install Complete')];
	}
}
?>