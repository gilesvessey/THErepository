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
		$database = \Drupal::database(); //Drupal's dope version of a connection string, abstracts away the databay
		$result = $database->query("SELECT * FROM {title} WHERE id > :id", [':id' => 0]); //:id is how we do variables, Drupal doesn't use normal SQL it uses its own version that abastracts away the underlying database
		
		foreach($result as $record) //queries come back as multi-record recordsets
		{
			$id = $record->id; //assigning local variable $id the returned record's id
			$title = $record->title;
			
			$printOut .= "ID: $id TITLE: $title <br /><br />"; //html that shit up!
		}
		
		return array('#markup' => $printOut); //put that html into Drupal's markup area
	}
}
?>
