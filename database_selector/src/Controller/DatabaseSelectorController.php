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

		
		$result = $database->query("SELECT * FROM {institution} WHERE id > :id", [':id' => 0]); 

		$printOut .= "<b>Institutions: </b><br /><br />";
		
		foreach($result as $record) 
		{

			$id = $record->id;

			$extension = $record->domain;

			$name = $record->name;

			$printOut .= "ID: $id EXTENSION: $extension NAME: $name<br /><br />"; 

		}
		
		$printOut .= "<b>Users: </b><br /><br />";
		
		$result = $database->query("SELECT * FROM {user_institution} WHERE id > :id", [':id' => 0]);
		
		foreach($result as $record) //queries come back as multi-record recordsets

		{

			$id = $record->id; //assigning local variable $id the returned record's id

			$user_id = $record->user_id;

			$institution_id = $record->institution_id;

			$printOut .= "ID: $id USER: $user_id INSTITUTION: $institution_id<br /><br />"; 

		}
		
		

		

		return array('#markup' => $printOut); //put that html into Drupal's markup area

	}

}

?>

