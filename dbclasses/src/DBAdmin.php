<?php
namespace Drupal\dbclasses;
class DBAdmin
{	
	//Checks input data and inserts an entry into the database
	//Returns issn id and an empty array on successful upload
	//Returns 0 and an array of strings containing error messages on unsuccessful upload
	public function insert($title, $l_issn, $p_issn, $e_issn, $lc) {
		$database = \Drupal::database();
		$user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
		
		//Validate and clean input data
		
		//First trim leading and trailing whitespace that may exist
		$title = trim($title);
		$l_issn = trim($l_issn);
		$p_issn = trim($p_issn);
		$e_issn = trim($e_issn);
		$lc = trim($lc);
		
		//Regular expressions for validating inputs
		$regISSN = '/^"?[0-9]{4}-?[0-9]{3}([0-9]|(X|x))"?$/'; //Accepts an ISSN with or without a hyphen, can be in quotes or not
		$regLC = '/^"?([a-zA-Z]{1,3})(([0-9]{0,4})|([0-9]{0,4}\.([0-9]{1,4})))(\.[a-zA-Z][0-9]{0,3}){0,2}"?$/'; //Strict LC, no extra stuff allowed at the end, can be in quotes or not
		
		$errors = []; //Holds error messages
		//Make sure one of p or e issn is present
		if(($p_issn == null) && ($e_issn == null))
			array_push($errors, 'No P or E ISSN Present');
		//L-ISSN, match regex, or blank
		if((preg_match($regISSN, $l_issn) != 1) && $l_issn != null)
			array_push($errors, 'Invalid L-ISSN');
		else if((strpos($l_issn, '-') == false) && $l_issn != null) //Add hyphen if missing
			$l_issn = substr($l_issn, 0, 4) . '-' . substr($l_issn, -4, 4);
		//P-ISSN, match regex, or blank
		if((preg_match($regISSN, $p_issn) != 1) && $p_issn != null)
			array_push($errors, 'Invalid P-ISSN');
		else if((strpos($p_issn, '-') == false) && $p_issn != null) //Add hyphen if missing
			$p_issn = substr($p_issn, 0, 4) . '-' . substr($p_issn, -4, 4);
		//E-ISSN, match regex, or blank
		if((preg_match($regISSN, $e_issn) != 1) && $e_issn != null)
			array_push($errors, 'Invalid E-ISSN');
		else if((strpos($e_issn, '-') == false) && $e_issn != null) //Add hyphen if missing
			$e_issn = substr($e_issn, 0, 4) . '-' . substr($e_issn, -4, 4);
		//LC, match regex, cannot be blank
		$lc = str_replace(" ", "", $lc); //Remove all spaces from LC
		if(preg_match($regLC, $lc) != 1)
			array_push($errors, 'Invalid LC');
		//Title, if not blank it must be quoted
		if(((substr($title, 0, 1) != '"') || (substr($title, -1, 1) != '"')) && $title != null)
			array_push($errors, 'Title Must Be Quoted');
		
		//Trim quotations off issns and lc before upload
		$l_issn = trim($l_issn, '"');
		$p_issn = trim($p_issn, '"');
		$e_issn = trim($e_issn, '"');
		$lc = trim($lc, '"');
		
		//Remove all quotes from title, and replace one set - this is in case there are many sets of quotes
		$title = str_replace('"', "", $title);
		$title = '"' . $title . '"';
		
		//Make all characters in issns and lc uppercase
		$l_issn = strtoupper($l_issn);
		$p_issn = strtoupper($p_issn);
		$e_issn = strtoupper($e_issn);
		$lc = strtoupper($lc);
		
		//Insert only if there are no errors
		if(empty($errors)) {
			$existingISSN_l = null;
			$existingISSN_p = null;
			$existingISSN_e = null;
			
			//Check database for existing issn id's for inputted issns
			if($l_issn != null)
				$existingISSN_l = $this->getISSNIdByL($l_issn);
			if($p_issn != null)
				$existingISSN_p = $this->getISSNIdByP($p_issn);
			if($e_issn != null)
				$existingISSN_e = $this->getISSNIdByE($e_issn);
			
			//only insert the ISSN if that ISSN doesn't already exist
			if($existingISSN_l == null && $existingISSN_p == null && $existingISSN_e == null) {		
				$database->insert('issn');
				$fields = [
					'title' => $title,
					'issn_l' => $l_issn,
					'p_issn' => $p_issn,
					'e_issn' => $e_issn,
				];
				$issn_id = $database->insert('issn')->fields($fields)->execute();
			}
			
			//If we got an existing issn id, set it as the current one
			if($existingISSN_p != null)
				$issn_id = $existingISSN_p;
			else if($existingISSN_e != null)
				$issn_id = $existingISSN_e;
			else if($existingISSN_l != null)
				$issn_id = $existingISSN_l;
					
			//Insert lc assignment
			$database->insert('lc');
			$fields = [
				'issn_id' => $issn_id,
				'lc' => $lc,
				'user_id' => $user->get('uid')->value,
			];
			$lc_id = $database->insert('lc')->fields($fields)->execute();
					
			return [$issn_id, $errors];
		}
		else { //If there are errors, return 0 and list of errors
			return [0, $errors];
		}
	}
	
	public function selectById($id)
	{
		$database = \Drupal::database();
		$sql = "SELECT 
					lc.id as id,
					issn.title as title,
					issn.issn_l as issn_l,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn,
					issn.modified as modified,
					institution.name as name,
					lc.lc as lc,
					lc.user_id as user_id					
				FROM lc
				LEFT OUTER JOIN issn
					ON issn.id = lc.issn_id
					LEFT OUTER JOIN user_institution
						ON user_institution.user_id = lc.user_id
						LEFT OUTER JOIN institution
						ON institution.id = user_institution.institution_id
				WHERE issn.id = $id;
				";
				
		$result = db_query($sql);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$modified = $record->modified;
			$issn_l = $record->issn_l;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			$callnumber = $record->lc;
			$source = $record->name;
			$user = $record->user_id;
			
			$recordSet[$setIndex]  = new DBRecord($id, $title, $source, $issn_l, $p_issn, $e_issn, '', $callnumber, $modified, $user);
			$setIndex++;
		}
		
		return $recordSet;
	}
	
	public function selectAll()
	{
		$database = \Drupal::database();
		$sql = "SELECT 
					lc.id as id,
					issn.title as title,
					issn.issn_l as issn_l,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn,
					lc.lc as lc,
					lc.user_id as user_id					
				FROM lc
				LEFT OUTER JOIN issn
					ON issn.id = lc.issn_id
				";
				
		$result = db_query($sql);
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$issn_l = $record->issn_l;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			$callnumber = $record->lc;
			$user = $record->user_id;
			
			$recordSet[$setIndex]  = new DBRecord($id, $title, 0, $issn_l, $p_issn, $e_issn, '', $callnumber, 0, $user);
			$setIndex++;
		}
		
		return $recordSet;
	}
	
	public function selectByTitle($title)
	{
		$database = \Drupal::database();
		$sql = "SELECT 
					lc.id as id,
					issn.title as title,
					issn.issn_l as issn_l,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn,
					issn.modified as modified,
					institution.name as name,
					lc.lc as lc,
					lc.user_id as user_id					
				FROM lc
				LEFT OUTER JOIN issn
					ON issn.id = lc.issn_id
					LEFT OUTER JOIN user_institution
						ON user_institution.user_id = lc.user_id
						LEFT OUTER JOIN institution
						ON institution.id = user_institution.institution_id
				WHERE issn.title LIKE '%$title%';
				";
				
		$result = db_query($sql);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$modified = $record->modified;
			$issn_l = $record->issn_l;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			$callnumber = $record->lc;
			$source = $record->name;
			$user = $record->user_id;
			
			$recordSet[$setIndex]  = new DBRecord($id, $title, $source, $issn_l, $p_issn, $e_issn, '', $callnumber, $modified, $user);
			$setIndex++;
		}
		
		return $recordSet;
	}
	
	public function selectByISSN($issn) //matches any ISSN type
	{
		$database = \Drupal::database();
		$sql = "SELECT 
					lc.id as id,
					issn.title as title,
					issn.issn_l as issn_l,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn,
					issn.modified as modified,
					institution.name as name,
					lc.lc as lc,
					lc.user_id as user_id					
				FROM lc
				LEFT OUTER JOIN issn
					ON issn.id = lc.issn_id
					LEFT OUTER JOIN user_institution
						ON user_institution.user_id = lc.user_id
						LEFT OUTER JOIN institution
						ON institution.id = user_institution.institution_id
				WHERE issn.issn_l = '$issn'
					OR issn.p_issn = '$issn'
					OR issn.e_issn = '$issn'; 
				";
				
		$result = db_query($sql);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$modified = $record->modified;
			$issn_l = $record->issn_l;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			$callnumber = $record->lc;
			$source = $record->name;
			$user = $record->user_id;
			
			$recordSet[$setIndex]  = new DBRecord($id, $title, $source, $issn_l, $p_issn, $e_issn, '', $callnumber, $modified, $user);
			$setIndex++;
		}
		
		//if(empty($recordSet))
			//$recordSet = null;
		
		return $recordSet;
	}
	
	public function selectByLC($lc)
	{
		$database = \Drupal::database();
		$sql = "SELECT 
					lc.id as id,
					issn.title as title,
					issn.issn_l as issn_l,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn,
					issn.modified as modified,
					institution.name as name,
					lc.lc as lc,
					lc.user_id as user_id					
				FROM lc
				LEFT OUTER JOIN issn
					ON issn.id = lc.issn_id
					LEFT OUTER JOIN user_institution
						ON user_institution.user_id = lc.user_id
						LEFT OUTER JOIN institution
						ON institution.id = user_institution.institution_id
				WHERE lc.lc LIKE '$lc%';
				";
				
		$result = db_query($sql);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$modified = $record->modified;
			$issn_l = $record->issn_l;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			$callnumber = $record->lc;
			$source = $record->name;
			$user = $record->user_id;
			
			$recordSet[$setIndex]  = new DBRecord($id, $title, $source, $issn_l, $p_issn, $e_issn, '', $callnumber, $modified, $user);
			$setIndex++;
		}
		
		return $recordSet;
	}
	
    public function recordCount()
   	{
		$database = \Drupal::database();
		$result = $database->query("SELECT COUNT(*) AS numrows FROM issn");
		$numrows = '';
		foreach($result as $record)
		{
			$numrows = $record->numrows;
		}
		return $numrows;
    }
	
	public function deleteLCById($id)
	{
		$database = \Drupal::database();	
		$database->query("DELETE FROM {lc} WHERE id = :id", [':id' => $id]);
		
		return "$id deleted.";
	}
	
	/*
		For institution database table
	*/
	
	//Insert a new institution, contains the associated email extension and name
	public function insertInstitution($extension, $name)
	{
		$database = \Drupal::database();	
		$database->insert('institution');
			$fields = [
				'domain' => $extension,
				'name' => $name,
				];
			$institution_id = $database->insert('institution')
				->fields($fields)
				->execute();
				
			return $institution_id;
	}
	
	//Get the name of an institution from an email extension
	//Returns 0 on no result
	public function selectByExtension($extension) 
	{
		$database = \Drupal::database();	
		$result = $database->query("SELECT name FROM {institution} WHERE domain = :extension", [':extension' => $extension]);
		
		$name = 0;
		
		foreach($result as $record)
		{
			$name = $record->name;
		}
			
		return $name;
	}
	
	public function getInstitutionID($name) 
	{
		$database = \Drupal::database();	
		$result = $database->query("SELECT id FROM {institution} WHERE name = :name", [':name' => $name]);
		
		$id = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
		}
			
		return $id;
	}
	
	public function getInstitutionName($user_id) 
	{
		$database = \Drupal::database();	
		$result = $database->query("SELECT name FROM {institution} 
									LEFT OUTER JOIN user_institution
										ON institution_id = institution.id
										WHERE user_id = :id", [':id' => $user_id]);
		
		$name = 0;
		
		foreach($result as $record)
		{
			$name = $record->name;
		}
			
		return $name;
	}
	
	/*
		For user_institution table
	*/
	public function insertUser($user, $institution) {
		$database = \Drupal::database();	
		$database->insert('user_institution');
			$fields = [
				'user_id' => $user,
				'institution_id' => $institution,
				];
			$id = $database->insert('user_institution')
				->fields($fields)
				->execute();
				
			return $id;
	}
	
	//Takes in a user id and returns the corresponding institution name
	public function getUserInstitution($uid) {
		return \Drupal\user\Entity\User::load($uid)->get("field_institution")->value;
	}
	
	public function getInstitutions() {
		$database = \Drupal::database();
		
		$list = $database->query("SELECT DISTINCT field_institution_value FROM {user__field_institution}");

		$output = [];
		foreach($list as $record) {
			array_push($output, $record->field_institution_value);
		}
		
		return $output;
	}
	
	public function getInstitutionTable() {
        $database = \Drupal::database();
        
        $list = $database->query("SELECT * FROM {institution}");
        
		$i = 0;
        $output = [];
		foreach($list as $record) {
			$output[$i] = [$record->id, $record->name, $record->domain];
            $i++;
		}
		
		return $output;

    }
	
	/*
		For ISSN Table
	*/
	
	//Inserts an entry into the ISSN table
	//Checks that values are valid ISSNs and that there is no existing issn values that are the same
	//Outputs an array of two values
	//On success - the id of the new value, and an empty array
	//On failure - a 0, and an array of reasons for failure
	public function insertISSN($l_issn, $p_issn, $e_issn, $title){
		$database = \Drupal::database();
		
		//First trim leading and trailing whitespace that may exist
		$l_issn = trim($l_issn);
		$p_issn = trim($p_issn);
		$e_issn = trim($e_issn);
		$title = trim($title);
		
		//Add quotes around title if they're not present
		if(((substr($title, 0, 1) != '"') || (substr($title, -1, 1) != '"')) && $title != null)
			$title = '"' . $title . '"';
		
		$regISSN = '/^"?[0-9]{4}-?[0-9]{3}([0-9]|(X|x))"?$/'; //Accepts an ISSN with or without a hyphen, can be in quotes or not
		
		$errors = []; //Holds error messages
		
		//Make sure one of p or e issn is present
		if(($p_issn == null) && ($e_issn == null))
			array_push($errors, 'No P or E ISSN Present');
		//L-ISSN, match regex, or blank
		if((preg_match($regISSN, $l_issn) != 1) && $l_issn != null)
			array_push($errors, 'Invalid L-ISSN');
		else if((strpos($l_issn, '-') == false) && $l_issn != null) //Add hyphen if missing
			$l_issn = substr($l_issn, 0, 4) . '-' . substr($l_issn, -4, 4);
		//P-ISSN, match regex, or blank
		if((preg_match($regISSN, $p_issn) != 1) && $p_issn != null)
			array_push($errors, 'Invalid P-ISSN');
		else if((strpos($p_issn, '-') == false) && $p_issn != null) //Add hyphen if missing
			$p_issn = substr($p_issn, 0, 4) . '-' . substr($p_issn, -4, 4);
		//E-ISSN, match regex, or blank
		if((preg_match($regISSN, $e_issn) != 1) && $e_issn != null)
			array_push($errors, 'Invalid E-ISSN');
		else if((strpos($e_issn, '-') == false) && $e_issn != null) //Add hyphen if missing
			$e_issn = substr($e_issn, 0, 4) . '-' . substr($e_issn, -4, 4);
		
		//Make all characters in issns uppercase
		$l_issn = strtoupper($l_issn);
		$p_issn = strtoupper($p_issn);
		$e_issn = strtoupper($e_issn);
		
		//Check database for existing entries for inputted issns
		if($p_issn != null) {
			$existingISSN_p = $this->getISSNIdByP($p_issn);
			if($existingISSN_p != 0) {
				array_push($errors, 'P-ISSN already exists');
			}
		}
		if($e_issn != null) {
			$existingISSN_e = $this->getISSNIdByE($e_issn);
			if($existingISSN_p != 0) {
				array_push($errors, 'E-ISSN already exists');
			}
		}
		if($l_issn != null) {
			$existingISSN_l = $this->getISSNIdByL($l_issn);
			if($existingISSN_p != 0) {
				array_push($errors, 'L-ISSN already exists');
			}
		}
		
		//Insert only if there are no errors
		if(empty($errors)) {
			$database->insert('issn');
			$fields = [
				'title' => $title,
				'issn_l' => $l_issn,
				'p_issn' => $p_issn,
				'e_issn' => $e_issn,
			];
			$id = $database->insert('issn')->fields($fields)->execute();
		}
		else {
			$id = 0;
		}
		
		return [$id, $errors];
	}
	
	//Deletes all LC assignments tied to this ISSN, then deletes it
	public function deleteISSN($id){
		$database = \Drupal::database();	
		$database->query("DELETE FROM {lc} WHERE issn_id = :id", [':id' => $id]);
		$database->query("DELETE FROM {issn} WHERE id = :id", [':id' => $id]);
		
		return "$id deleted.";
	}
	
	//Edits an entry in the ISSN table to have the new supplied values
	//Checks that there are no existing values for each issn first, other than supplied id
	//Outputs an array of two values
	//On success - the id of the new value, and an empty array
	//On failure - a 0, and an array of reasons for failure
	public function editISSN($id, $l_issn, $p_issn, $e_issn, $title) {
		//First trim leading and trailing whitespace that may exist
		$l_issn = trim($l_issn);
		$p_issn = trim($p_issn);
		$e_issn = trim($e_issn);
		$title = trim($title);
		
		//Add quotes around title if they're not present
		if(((substr($title, 0, 1) != '"') || (substr($title, -1, 1) != '"')) && $title != null)
			$title = '"' . $title . '"';
		
		$regISSN = '/^"?[0-9]{4}-?[0-9]{3}([0-9]|(X|x))"?$/'; //Accepts an ISSN with or without a hyphen, can be in quotes or not
		
		$errors = []; //Holds error messages
		
		//Make sure one of p or e issn is present
		if(($p_issn == null) && ($e_issn == null))
			array_push($errors, 'No P or E ISSN Present');
		//L-ISSN, match regex, or blank
		if((preg_match($regISSN, $l_issn) != 1) && $l_issn != null)
			array_push($errors, 'Invalid L-ISSN');
		else if((strpos($l_issn, '-') == false) && $l_issn != null) //Add hyphen if missing
			$l_issn = substr($l_issn, 0, 4) . '-' . substr($l_issn, -4, 4);
		//P-ISSN, match regex, or blank
		if((preg_match($regISSN, $p_issn) != 1) && $p_issn != null)
			array_push($errors, 'Invalid P-ISSN');
		else if((strpos($p_issn, '-') == false) && $p_issn != null) //Add hyphen if missing
			$p_issn = substr($p_issn, 0, 4) . '-' . substr($p_issn, -4, 4);
		//E-ISSN, match regex, or blank
		if((preg_match($regISSN, $e_issn) != 1) && $e_issn != null)
			array_push($errors, 'Invalid E-ISSN');
		else if((strpos($e_issn, '-') == false) && $e_issn != null) //Add hyphen if missing
			$e_issn = substr($e_issn, 0, 4) . '-' . substr($e_issn, -4, 4);
		
		//Make all characters in issns uppercase
		$l_issn = strtoupper($l_issn);
		$p_issn = strtoupper($p_issn);
		$e_issn = strtoupper($e_issn);
		
		//Check database for existing entries for inputted issns, other than current one being edited
		if($p_issn != null) {
			$existingISSN_p = $this->getISSNIdByP($p_issn);
			if($existingISSN_p != 0 && $existingISSN_p !=  $id) {
				array_push($errors, 'P-ISSN exists in another entry');
			}
		}
		if($e_issn != null) {
			$existingISSN_e = $this->getISSNIdByE($e_issn);
			if($existingISSN_p != 0 && $existingISSN_e !=  $id) {
				array_push($errors, 'E-ISSN exists in another entry');
			}
		}
		if($l_issn != null) {
			$existingISSN_l = $this->getISSNIdByL($l_issn);
			if($existingISSN_p != 0 && $existingISSN_l !=  $id) {
				array_push($errors, 'L-ISSN exists in another entry');
			}
		}
		
		//Edit only if there are no errors
		if(empty($errors)) {
			$database->query("UPDATE {issn} SET issn_l = :l_issn, p_issn = :p_issn, e_issn = :e_issn, title = :title WHERE id = :id", [':id' => $id, ':l_issn' => $l_issn, ':p_issn' => $p_issn, ':e_issn' => $e_issn, ':title' => $title]); 
		}
		else {
			$id = 0;
		}
		
		return [$id, $errors];
	}
	
	//Gets ID of entry with entered p-issn
	public function getISSNIdByP($p_issn) {
		if($p_issn == '')
			$p_issn = 0;
		
		$database = \Drupal::database();
		$sql = "SELECT 
					issn.id as id					
				FROM issn
				WHERE issn.p_issn = '$p_issn'
				";
				
		$result = db_query($sql);
		
		$id = null;
		
		foreach($result as $record)
		{
			$id = $record->id;
		}
		
		return $id;
	}
	
	//Gets ID of entry with entered e-issn
	public function getISSNIdByE($e_issn) {
		if($e_issn == '')
			$e_issn = 0;
		
		$database = \Drupal::database();
		$sql = "SELECT 
					issn.id as id					
				FROM issn
				WHERE issn.e_issn = '$e_issn'
				";
				
		$result = db_query($sql);
		
		$id = null;
		
		foreach($result as $record)
		{
			$id = $record->id;
		}
		
		return $id;
	}
	
	//Gets ID of entry with entered l-issn
	public function getISSNIdByL($l_issn) {
		if($l_issn == '')
			$l_issn = 0;
		
		$database = \Drupal::database();
		$sql = "SELECT 
					issn.id as id					
				FROM issn
				WHERE issn.issn_l = '$l_issn'
				";
				
		$result = db_query($sql);
		
		$id = null;
		
		foreach($result as $record)
		{
			$id = $record->id;
		}
		
		return $id;
	}
	
	//Selects all entry from the issn table which has the entered id
	//Returns an array of arrays of entries containing id, title, l_issn, p_issn, e_issn
	public function selectISSNById($id) {
		$database = \Drupal::database();
		$sql = "SELECT 
					issn.id as id,
					issn.title as title,
					issn.issn_l as l_issn,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn,				
				FROM issn
				WHERE issn.id = $id;
				";
				
		$result = db_query($sql);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$l_issn = $record->l_issn;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			
			$recordSet[$setIndex]  = [$id, $l_issn, $p_issn, $e_issn, $title];
			$setIndex++;
		}
		
		return $recordSet;
	}
	
	//Selects all entries from the issn table that contain supplied issn
	//Returns an array of arrays of entries containing id, title, l_issn, p_issn, e_issn
	public function selectISSNbyISSN($issn) {
		$database = \Drupal::database();
		$sql = "SELECT 
					issn.id as id,
					issn.title as title,
					issn.issn_l as l_issn,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn,				
				FROM issn
				WHERE issn.issn_l = '$issn'
					OR issn.p_issn = '$issn'
					OR issn.e_issn = '$issn';
				";
				
		$result = db_query($sql);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$l_issn = $record->l_issn;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			
			$recordSet[$setIndex]  = [$id, $l_issn, $p_issn, $e_issn, $title];
			$setIndex++;
		}
		
		return $recordSet;
	}
	
	//Selects all entries from ISSN table
	//Returns an array of arrays of entries containing id, title, l_issn, p_issn, e_issn
	public function selectAllISSN() {
		$database = \Drupal::database();
		$sql = "SELECT 
					issn.id as id,
					issn.title as title,
					issn.issn_l as l_issn,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn,				
				FROM issn
				";
				
		$result = db_query($sql);
		
		$recordSet = array();
		$setIndex = 0;
		
		foreach($result as $record)
		{
			$id = $record->id;
			$title = $record->title;
			$l_issn = $record->l_issn;
			$p_issn = $record->p_issn;
			$e_issn = $record->e_issn;
			
			$recordSet[$setIndex]  = [$id, $l_issn, $p_issn, $e_issn, $title];
			$setIndex++;
		}
		
		return $recordSet;
	}
	
	
	
	//*Note* Possibly now unused
	//Returns id for issn if it exists
	public function getISSNId($issn)
	{
		/*
		returns a single int (ID)
		returns a 0 on no result
		*/
		if($issn == '')
			$issn = 0;
		
		$database = \Drupal::database();
		$sql = "SELECT 
					issn.id as id					
				FROM issn
				WHERE issn.issn_l = '$issn'
					OR issn.p_issn = '$issn'
					OR issn.e_issn = '$issn';
				";
				
		$result = db_query($sql);
		
		$id = null;
		
		foreach($result as $record)
		{
			$id = $record->id;
		}
		
		return $id;
	}
}
?>
