<?php
namespace Drupal\database_tester02\Controller;
use Drupal\Core\Controller\ControllerBase;

//use our custom classes
use Drupal\dbclasses\DBAdmin;
use Drupal\dbclasses\DBRecord;

/**
* Controller for the salutation message.
*/

class Tester extends ControllerBase {
/**
* Test.
*
* @return string
*/
		
	public function test() 
	{
		$dbAdmin = new DBAdmin();
		
		//insert a few records to test
		$dbAdmin->insert('Abacus', 0, '0001-3072', '0001-3072', '1467-6281', 'A', 'A91-2');
		$dbAdmin->insert('AAUP Bulletin', 0, '0001-026X', '0001-026X', '', 'L', 'LB2301 .A3');
		$dbAdmin->insert('Rocky Mountain communication r', 0, '1542-6394', '1542-6394', '', 'L', 'LB2326.4');
		$dbAdmin->insert('Acta Biotheoretica', 0, '0001-5342', '0001-5342', '1572-8358', 'QH', 'QH301');
		$dbAdmin->insert('Alabama Lawyer', 0, '0002-4287', '0002-4287', '', 'SD', 'SD1 .F73');
		$dbAdmin->insert('Bulletin - Council on the Study of Religion', 0, '0002-7170', '0002-7170', '', 'SD', 'SD11 .A4576');
		$dbAdmin->insert('American Annals of the Deaf', 0, '0002-726X', '0002-726X', '1543-0375', 'HD', 'HD2346.U5 .N332');
		$dbAdmin->insert('Bulletin of the American Schools of Oriental Research', 0, '0003-097X', '0003-097X', '2161-8062', 'HD', 'HD2346.U5 .S638');
		$dbAdmin->insert('The American Statistician', 0, '0003-1305', '0003-1305', '1537-2731', 'A', 'A91-2');
		$dbAdmin->insert('Area', 0, '0004-0894', '0004-0894', '1475-4762', 'A', 'K91-2');
		$dbAdmin->insert('Babel', 0, '0005-3503', '0005-3503', '', 'K', 'K22 .U84');
		$dbAdmin->insert('Banking', 0, '0005-5492', '0005-5492', '', 'K', 'K22 .U84');
		
		//search by ID
		$recordSet = $dbAdmin->selectById(6);
		
		$printOut = '';
		
		foreach($recordSet as $record)
		{
			$printOut .= "$record->id $record->title $record->source $record->issn_l $record->p_issn $record->e_issn $record->lcclass $record->callnumber<br />"; //a single record
		}
		
		//search by Title
		$recordSet = $dbAdmin->selectByTitle('Babel');
		
		$printOut .= '<br /><br />'; //formatting	
		
		foreach($recordSet as $record)
		{
			$printOut .= "$record->id $record->title $record->source $record->issn_l $record->p_issn $record->e_issn $record->lcclass $record->callnumber<br />"; //a single record
		}
		
		//search by ISSN
		$recordSet = $dbAdmin->selectByISSN('0002-4287');
		
		$printOut .= '<br /><br />';
		
		foreach($recordSet as $record)
		{
			$printOut .= "$record->id $record->title $record->source $record->issn_l $record->p_issn $record->e_issn $record->lcclass $record->callnumber<br />"; //a single record
		}
		
		//search by LC Class
		$recordSet = $dbAdmin->selectByLCClass('K22 .U84');
		
		$printOut .= '<br /><br />';
		
		foreach($recordSet as $record)
		{
			$printOut .= "$record->id $record->title $record->source $record->issn_l $record->p_issn $record->e_issn $record->lcclass $record->callnumber<br />"; //a single record
		}
		
		return array('#markup' => $printOut); //print to webpage
	}
}
?>