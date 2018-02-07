<?php
namespace Drupal\database_tester\Controller;
use Drupal\Core\Controller\ControllerBase;

/**
* Controller for the salutation message.
*/

class DatabaseTesterController extends ControllerBase 
{
/**
* Test.
*
* @return string
*/
	public function insertListing($titleIn, $issnIn, $callNumberIn, $lcclassIn)
	{
		$database = \Drupal::database(); //this is Drupal's version of a connection string, abstracting away underlying database
		
		$database->insert('title');	//table name	
		$fields = [
			'title' => $titleIn,	//table column => value (in this case variable $titleIn)
			'source' => 1,
			];
		$title_id = $database->insert('title') //execute() returns ID of record just inserted.
			->fields($fields)
			->execute();		
		
		$database->insert('issn');		
		$fields = [
			'issn' => $issnIn,
			'title_id' => $title_id,
			];
		$issn_id = $database->insert('issn')
			->fields($fields)
			->execute();
			
		$database->insert('callnumber');		
		$fields = [
			'callnumber' => $callNumberIn,
			];
		$callnumber_id = $database->insert('callnumber')
			->fields($fields)
			->execute();
			
		$database->insert('lcclass');		
		$fields = [
			'lcclass' => $lcclassIn,
			];
		$lcclass_id = $database->insert('lcclass')
			->fields($fields)
			->execute();
		
		$database->insert('callnumber_title');		
		$fields = [
			'callnumber_id' => $callnumber_id,
			'title_id' => $title_id,
			];
		$title_id = $database->insert('callnumber_title')
			->fields($fields)
			->execute();
		
		$database->insert('lcclass_title');		
		$fields = [
			'lcclass_id' => $lcclass_id,
			'title_id' => $title_id,
			];
		$title_id = $database->insert('lcclass_title')
			->fields($fields)
			->execute();	
	}
	
	public function test() 
	{
		//sample of how module programmers would actually insert into database:
		$this->insertListing('Journal du conseil', '0902-3232', 'GC1 .I64', 'GC');
		$this->insertListing('Journal du conseil', '0902-3232', 'HV1 .I64', 'HV');
		$this->insertListing('Seminars in roentgenology', '1558-4658', 'RC78', 'RC');
		$this->insertListing('Canadian index', '0381-6915', 'A119.C2.C35', 'A');
		$this->insertListing('Cultural critique', '1460-2458', 'AC5', 'AC');
		$this->insertListing('The bedside Guardian.', '1759-3417', 'AC5 .M43', 'AC');
		return array('#markup' => 'Done did it.');
	}
}
?>
