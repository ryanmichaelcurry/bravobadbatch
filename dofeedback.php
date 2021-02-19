<?php
require_once("vendor/apollophp/header.php");
var_dump($_POST);
if(isset($_POST["text"]))
{
    $apollo->insert("feedback", ["cadet"=>$_POST["cadet"], "text"=>$_POST["text"]]);
    $id = $apollo->result->insert_id;
    var_dump($id);
    $cadet = $apollo->select("cadet", ["id"=>$_POST["cadet"]])[0];
    var_dump($cadet);
    var_dump($cadet["feedback"]);
    $cadet["feedback"] = json_decode($cadet["feedback"]);
    var_dump($cadet["feedback"]);
    var_dump(array($id));
    $cadet["feedback"] = array_merge($cadet["feedback"], array($id));
    $cadet["feedback"] = json_encode($cadet["feedback"]);
    $apollo->update("cadet", ["feedback"=>$cadet["feedback"]], ["id"=>$_POST["cadet"]]);
}

header("Location: /");

?>