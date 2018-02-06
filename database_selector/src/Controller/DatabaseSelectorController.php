<?php
namespace Drupal\database_selector\Controller;
use Drupal\Core\Controller\ControllerBase;

/**
* Controller for the salutation message.
*/

class DatabaseSelectorController extends ControllerBase 
{
/**
* Test.
*
* @return string
*/
	
	public function test() 
	{
		$database = \Drupal::database();
		$result = $database->query("SELECT * FROM {title} WHERE id > :id", [':id' => 0]);
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			
			$printOut .= "ID: $id TITLE: $title <br /><br />";
		}
		
		return array('#markup' => $printOut);
	}
}
?>