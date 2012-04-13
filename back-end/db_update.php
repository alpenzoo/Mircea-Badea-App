<?php
require_once 'config/connection.php';

/*
* 	Connect to the MongoDB
*/
$m = new Mongo("mongodb://${username}:${password}@${database_server}:${database_port}/${database}"); 
$db = $m->selectDB("${database}");
$collection = new MongoCollection($db, 'shows');


/* 
*	Get the HTML page with the show list and build array of shows
*/
$html = file_get_contents("http://www.radiozu.ro/emisiuni/mircea-badea-la-zu-1#inregistrari");
preg_match_all(
    '/<option value="(.*?)">Editia din(.*?)<\/option>/s',
    $html,
    $shows, // will contain theshows
    PREG_SET_ORDER // formats data into an array of shows
);


/* 
*	Loop through the array of shows and get the JSON file to store in MongoDB
*/
$counter=0;
$counter2=0;

foreach ($shows as $show) {
	$counter2++;
    $id = $show[1];
    $title = $show[2];
   
    $show_json=file_get_contents("http://www.radiozu.ro/ajax.php?action=getInregistrari&iid=" . $id);
 	$show_array=json_decode($show_json, true);
 	$show_array['_id']=$id;
 	$show_array['title']=$title;
 	$date = strtotime(str_replace(" ","-",$show_array['Data']) . " 00:00:00");

 	
 	$show_array['date']=new MongoDate($date);
 
 	
 	$ref=&$show_array;
 	
 	
 	echo "<hr>";
 	echo "id: " .$id ." [".$title."]...";
 	
 	//debug info:
 	//echo "<pre>";
	//print_r($show_array);
	//echo "</pre>";
 	
 	
 	try {
 			$collection->insert($ref,true);
 		 	echo " added<br>\n";
 		 	$counter++;
 	} catch(MongoCursorException $e) {
    		echo "Show already exists<br>\n";
	}
	
	
	//go through the first 10 shows. No point in getting > 500 if they are already in the DB
	if ($counter2==10)
		break;

}
echo "<hr>";
echo "Added " .$counter. " new show(s)";
?>
