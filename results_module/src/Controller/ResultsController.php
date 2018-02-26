<?php
/**
 * @file
 * Contains \Drupal\results_module\Controller\ResultsController.
 */
namespace Drupal\results_module\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\dbclasses\DBAdmin;
use Drupal\dbclasses\DBRecord;
class ResultsController extends ControllerBase{




public function content() {
$searchtype = \Drupal::request()->request->get('searchtype');
$searchterm = \Drupal::request()->request->get('searchterm');
$results_per_page = \Drupal::request()->request->get('resultsperpage');

$searchtype = 'all';
$searchterm = 'none';
$results_per_page = 100;
if (is_null($searchterm))
{
return array(
     '#type' => 'markup',
     '#markup' => 'No search term received!');
}
else if (is_null($searchtype))
{
return array(
     '#type' => 'markup',
     '#markup' => 'No search type received!');
}
else if (is_null($results_per_page))
{
return array(
     '#type' => 'markup',
     '#markup' => 'No results/page count received!');
}


else {
$dbAdmin = new DBAdmin();

if ($searchtype == 'all')
$recordSet = $dbAdmin->selectAll();
if ($searchtype == 'id')
$recordSet = $dbAdmin->selectById($searchterm);
if ($searchtype == 'title')
$recordSet = $dbAdmin->selectByTitle($searchterm);
if ($searchtype == 'issn')
$recordSet = $dbAdmin->selectByISSN($searchterm);
if ($searchtype == 'lccn')
$recordSet = $dbAdmin->selectByLCCN($searchterm); 



$tablebody = "<tr>";
// output data of each row
$resultcount = 0;
foreach($recordSet as $record)
{
if ($resultcount == $results_per_page) break;
else
{
$tablebody .= "<td>" . $record->id . "</td>";
$tablebody .= "<td>" . $record->title . "</td>";
$tablebody .= "<td>" . $record->source . "</td>";
$tablebody .= "<td>" . $record->issn_l . "</td>";
$tablebody .= "<td>" . $record->p_issn . "</td>";
$tablebody .= "<td>" . $record->e_issn . "</td>";
$tablebody .= "<td>" . $record->lcclass . "</td>";
$tablebody .= "<td>" . $record->callnumber . "</td>";
$tablebody .= "<td><button type=\"button\">Edit</button></td>";
$tablebody .= "</tr>";
$resultcount++;
}
}
for ($i=0; $i<30; $i++)
{
//$dbAdmin->insert('Publishers Weekly', '1', '0000-0019', '0000-0019', '2150-4008', 'A91-2', 'A');
}

return array(
     '#type' => 'markup',
     '#markup' => '<table><th>ID#</th><th>Title</th><th>Source</th><th>L ISSN</th><th>P ISSN</th><th>E ISSN</th><th>LC Class</th><th>LCCN</th><th>Edit</th>' . $tablebody . '</table>');
}
}
}