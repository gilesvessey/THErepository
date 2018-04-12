<?php
namespace Drupal\dbclasses;
class DBAdmin
{
	/*
	* Method Header 	: Errors[] insert(string title, string l_issn, string p_issn, string e_issn, string lc);
	*	Arguments	: int $id = the id of some ISSN record
	*	Returns		: Returns 0 if errors and an array of length X where X = number of errors.
	*/
	public function insert($title, $l_issn, $p_issn, $e_issn, $lc) {
	//Checks input data and inserts an entry into the database
	//Returns issn id and an empty array on successful upload
	//Returns 0 and an array of strings containing error messages on unsuccessful upload
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

			//If all ISSNs are not in database, insert a new value
			if($existingISSN_l == null && $existingISSN_p == null && $existingISSN_e == null) {
				$temp = $this->insertISSN($l_issn, $p_issn, $e_issn, $title);
				$issn_id = $temp[0]; //Get the issn id of the new entry
			}
			//If not, let's check that all of the existing ones we found are equal - compare l, p, e, if they're not null
			else if((((($existingISSN_l == $existingISSN_p) || ($existingISSN_p == null)) && (($existingISSN_l == $existingISSN_e) || $existingISSN_e == null)) || ($existingISSN_l == null)) && ((($existingISSN_p == $existingISSN_e) || ($existingISSN_e == null)) || ($existingISSN_p == null))) {
				//Let's find the existing one
				if($existingISSN_p != null)
					$issn_id = $existingISSN_p;
				else if($existingISSN_e != null)
					$issn_id = $existingISSN_e;
				else if($existingISSN_l != null)
					$issn_id = $existingISSN_l;

				//Get the entry on this id
				$entry = $this->selectISSNById($issn_id);

				//Get any existing values, so we don't replace them with nulls
				if($entry[1] != null) {
					$l_issn = $entry[1];
				}
				if($entry[2] != null) {
					$p_issn = $entry[2];
				}
				if($entry[3] != null) {
					$e_issn = $entry[3];
				}

				//Edit the entry on the id that we found
				$this->editISSN($issn_id, $l_issn, $p_issn, $e_issn, $title, 0);
			}
			//Some ISSNs do exist, but must be from different entries
			//If the types are not conflicting, we can combine them
			else {
				$entryL = $this->selectISSNById($existingISSN_l);
				$entryP = $this->selectISSNById($existingISSN_p);
				$entryE = $this->selectISSNById($existingISSN_e);

				$issnConflict = 0;

				//If l and p have different entries, lets compare values
				if(($existingISSN_l != null) && ($existingISSN_p != null) && (existingISSN_l != existingISSN_p)) {
					for($i = 1; $i <= 3; $i++) {
						if(($entryL[$i] != null) && ($entryL[$i] != $entryP[$i]) && ($entryP[$i] != null)) {
							$issnConflict = 1;
						}
					}
				}
				//If l an e have different entries, lets compare values
				if(($existingISSN_l != null) && ($existingISSN_e != null) && (existingISSN_l != existingISSN_e)) {
					for($i = 1; $i <= 3; $i++) {
						if(($entryL[$i] != null) && ($entryL[$i] != $entryE[$i]) && ($entryE[$i] != null)) {
							$issnConflict = 1;
						}
					}
				}
				//If p an e have different entries, lets compare values
				if(($existingISSN_p != null) && ($existingISSN_e != null) && ($existingISSN_p != $existingISSN_e)) {
					for($i = 1; $i <= 3; $i++) {
						if(($entryP[$i] != null) && ($entryP[$i] != $entryE[$i]) && ($entryE[$i] != null)) {
							$issnConflict = 1;
						}
					}
				}

				//If there were no conflicts found we can combine the entries
				if($issnConflict == 0) {
					//Let's get an existing entry id
					if($existingISSN_p != null)
						$issn_id = $existingISSN_p;
					else if($existingISSN_e != null)
						$issn_id = $existingISSN_e;
					else if($existingISSN_l != null)
						$issn_id = $existingISSN_l;

					//Now we'll edit this entry
					$this->editISSN($issn_id, $l_issn, $p_issn, $e_issn, $title, 0);

					//Now we need to assign this new ID to all LC entries that were using the old ones
					//And then delete the other issn entries
					if(($existingISSN_l != null) && ($existingISSN_l != $issn_id)) {
						$recordsL = $this->selectLCByISSNId($existingISSN_l);
						foreach($recordsL as $record){
							$this->editLC($record->id, $record->callnumber, $issn_id);
						}

						$this->deleteISSN($existingISSN_l);
					}
					if(($existingISSN_p != null) && ($existingISSN_p != $issn_id)) {
						$recordsP = $this->selectLCByISSNId($existingISSN_p);
						foreach($recordsP as $record){
							$this->editLC($record->id, $record->callnumber, $issn_id);
						}

						$this->deleteISSN($existingISSN_p);
					}
					if(($existingISSN_e != null) && ($existingISSN_e != $issn_id)) {
						$recordsE = $this->selectLCByISSNId($existingISSN_e);
						foreach($recordsE as $record){
							$this->editLC($record->id, $record->callnumber, $issn_id);
						}

						$this->deleteISSN($existingISSN_e);
					}
				}
				//If there were conflicts found, we end and return the error
				else {
					array_push($errors, 'Conflicting ISSNs found');
					return [0, $errors];
				}
			}

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

	/*
	* Method Header 	: int editLC(int id, string lc, int issn_id);
	*	Arguments	: id = the ID of the LC to be edited
	*			  lc = the new LC data that will replace the current LC data found at the provided ID
	*			  issn_id = the ISSN record to which this LC record refers to
	*	Returns		: the ID of the recently edited LC.
	*/
	public function editLC($id, $lc, $issn_id) {
		//Edits an LC entry
		//Used in the insert method when combining ISSN entries
		//Doesn't contain any cleaning/validation since that is already done in the insert method!
		//Be careful not to use this in other places if you don't verify the information
		//Accepts an id for lc entry, an lc number, and an issn id
		//Returns the id for the entry
		$database = \Drupal::database();
		$database->query("UPDATE {lc} SET lc = :lc, issn_id = :issn_id WHERE id = :id", [':id' => $id, ':lc' => $lc, ':issn_id' => $issn_id]);
		return $id;
	}

	/*
	* Method Header 	: DBRecord[] selectByID(int id);
	*	Arguments	: id = the id of some ISSN record
	*	Returns		: an array of DBRecords, generally of length 1 if a record is found, of length 0 if not found.
	*/
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
					user__field_institution.field_institution_value as name,
					lc.lc as lc,
					lc.user_id as user_id
				FROM lc
				LEFT OUTER JOIN issn
					ON issn.id = lc.issn_id
					LEFT OUTER JOIN user__field_institution
						ON user__field_institution.entity_id = lc.user_id
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

	/*
	* Method Header 	: DBRecord[] selectAll();
	*	Arguments	: void
	*	Returns		: an array of DBRecords of length X, where X = # of ISSN records in database
	*/
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

	/*
	* Method Header 	: DBRecord[] selectByTitle(string title);
	*	Arguments	: title = the public title of some work
	*	Returns		: an array of DBRecords. Its possible that multiple works/records have the same title
	*/
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
					user__field_institution.field_institution_value as name,
					lc.lc as lc,
					lc.user_id as user_id
				FROM lc
				LEFT OUTER JOIN issn
					ON issn.id = lc.issn_id
					LEFT OUTER JOIN user__field_institution
						ON user__field_institution.entity_id = lc.user_id
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

	/*
	* Method Header 	: DBRecord[] selectByISSN(string issn);
	*	Arguments	: issn = the issn of some work/record. This ISSN will be matched against l,p, and e ISSNs.
	*	Returns		: an array of DBRecords, while not common, its technically possible that multiple works share an ISSN.
	*/
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
					user__field_institution.field_institution_value as name,
					lc.lc as lc,
					lc.user_id as user_id
				FROM lc
				LEFT OUTER JOIN issn
					ON issn.id = lc.issn_id
					LEFT OUTER JOIN user__field_institution
						ON user__field_institution.entity_id = lc.user_id
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

		return $recordSet;
	}

	/*
	* Method Header 	: DBRecord[] selectLCByISSNId(string issn_id);
	*	Arguments	: issn_id = the id of some ISSN record
	*	Returns		: an array of DBRecords.
	*/
	public function selectLCByISSNId($issn_id) {
		$database = \Drupal::database();
		$sql = "SELECT
					lc.id as id,
					issn.title as title,
					issn.issn_l as issn_l,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn,
					issn.modified as modified,
					user__field_institution.field_institution_value as name,
					lc.lc as lc,
					lc.user_id as user_id
				FROM lc
				LEFT OUTER JOIN issn
					ON issn.id = lc.issn_id
					LEFT OUTER JOIN user__field_institution
						ON user__field_institution.entity_id = lc.user_id
				WHERE lc.issn_id = $issn_id
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


	/*
	* Method Header 	: DBRecord[] selectByLC(string lc);
	*	Arguments	: lc = the public (not a database ID) LC
	*	Returns		: an array of DBRecords. Returns all records that match the provided LC
	*/
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
					user__field_institution.field_institution_value as name,
					lc.lc as lc,
					lc.user_id as user_id
				FROM lc
				LEFT OUTER JOIN issn
					ON issn.id = lc.issn_id
					LEFT OUTER JOIN user__field_institution
						ON user__field_institution.entity_id = lc.user_id
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

	/*
	* Method Header 	: int recordCount();
	*	Arguments	:
	*	Returns		: an integer whose value is equal to the number of rows in the ISSN table.
	*/
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

	/*
	* Method Header 	: string delectLCById(int id);
	*	Arguments	: id = the id of the LC to be removed from the database
	*	Returns		: a string that confirms the LC record has been removed from the database.
	*/
	public function deleteLCById($id)
	{
		$database = \Drupal::database();
		$database->query("DELETE FROM {lc} WHERE id = :id", [':id' => $id]);

		return "$id deleted.";
	}

	/*
		For institution database table
	*/

	/*
	* Method Header 	: int insertInstitution(string extension, string name);
	*	Arguments	: extension = the "domain" of an institution, ex: UPEI.ca is the domain for University of PEI
	*			  name = the public name of the institution, ex: "University of Prince Edward Island"
	*	Returns		: an int that is = to the the new institution's ID # in the database.
	*/
	public function insertInstitution($extension, $name)
	{
		//Insert a new institution, contains the associated email extension and name
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

	public function deleteInstitutionById($id)
	{
		$database = \Drupal::database();
		$result = $database->query("DELETE FROM {institution} WHERE id = :id", [':id' => $id]);

		return "$id deleted.";
	}

	/*
	* Method Header 	: string selectByExtension(string extension);
	*	Arguments	: extension = the "domain" of an institution, ex: UPEI.ca is the domain for University of PEI
	*	Returns		: returns a string that is an institution's name. Returns a 0 if no record is found.
	*/
	public function selectByExtension($extension)
	{
		//Get the name of an institution from an email extension
		$database = \Drupal::database();
		$result = $database->query("SELECT name FROM {institution} WHERE domain = :extension", [':extension' => $extension]);

		$name = 0;

		foreach($result as $record)
		{
			$name = $record->name;
		}

		return $name;
	}

	/*
	* Method Header 	: int getInstitutionID(string name);
	*	Arguments	: name = the "domain" of an institution, ex: UPEI.ca is the domain for University of PEI
	*	Returns		: returns the ID of the institution who's name matches the supplied argument.
	*/
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

	/*
	* Method Header 	: string getInstitutionName(int user_id);
	*	Arguments	: user_id = the id of a user
	*	Returns		: returns the name of the institution with which the user is affiliated
	*/
	public function getInstitutionName($user_id)
	{
		$database = \Drupal::database();
		$result = $database->query("SELECT field_institution_value as name FROM {user__field_institution}
									WHERE entity_id = :id", [':id' => $user_id]);

		$name = 0;

		foreach($result as $record)
		{
			$name = $record->name;
		}

		return $name;
	}

	/*
	* Method Header 	: int insertUser(int user, int institution);
	*	Arguments	: user = the ID of the user
	*			  institution = the ID of the institution, creates a connection between a user and an institution
	*	Returns		: returns the ID of the newly inserted record.
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
	//If you want duplicate ISSNs to not be allowed, set $check to 1, otherwise set to 0
	public function editISSN($id, $l_issn, $p_issn, $e_issn, $title, $check) {
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

		if($check == 1) {
			//Check database for existing entries for inputted issns, other than current one being edited
			if($p_issn != null) {
				$existingISSN_p = $this->getISSNIdByP($p_issn);
				if($existingISSN_p != 0 && $existingISSN_p !=  $id) {
					array_push($errors, 'P-ISSN exists in another entry');
				}
			}
			if($e_issn != null) {
				$existingISSN_e = $this->getISSNIdByE($e_issn);
				if($existingISSN_e != 0 && $existingISSN_e !=  $id) {
					array_push($errors, 'E-ISSN exists in another entry');
				}
			}
			if($l_issn != null) {
				$existingISSN_l = $this->getISSNIdByL($l_issn);
				if($existingISSN_l != 0 && $existingISSN_l !=  $id) {
					array_push($errors, 'L-ISSN exists in another entry');
				}
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

	//Selects the entry from the issn table which has the entered id
	//Returns an array containing id, l_issn, p_issn, e_issn, title
	public function selectISSNById($id) {
		$database = \Drupal::database();
		$sql = "SELECT
					issn.id as id,
					issn.title as title,
					issn.issn_l as l_issn,
					issn.p_issn as p_issn,
					issn.e_issn as e_issn
				FROM issn
				WHERE issn.id = '$id';
				";

		$result = db_query($sql);

		$output = null;

		foreach($result as $record)
		{
			$output = [$record->id, $record->l_issn, $record->p_issn, $record->e_issn, $record->title];
		}

		return $output;
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
					issn.e_issn as e_issn
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
					issn.e_issn as e_issn
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
