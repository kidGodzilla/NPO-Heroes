<?php
require 'tropo.class.php';
include 'connect.inc.php';

//$location = $user_row[3];
$location = $_GET['location'];

$input = ' ';

if($location)
    {
        $tropo = new Tropo();
            @$result = new Result();
            $answer = $result->getValue();
            $input .= $answer;
    }

//$session = new Session();
//$from_info = $session->getFrom();
//$caller = $from_info['id'];

//$mat = mysql_query("SELECT * FROM `users` WHERE `msisdn` = '$caller' LIMIT 1");
//$user_row = mysql_fetch_row($mat);
//$user = $user_row[0];
$user = 1;    
    
    
$output = '';        

if(!$location)
    {
        $output .= "Welcome to the hitchhicker's guide to the galaxy, an interactive science fiction adventure game. ";
        $location = 1;
    }
if($location)
    {
        //Echo Location Text
        $q = mysql_query("SELECT * FROM `objects` WHERE `id` = '$location' LIMIT 1");
        $location_array = mysql_fetch_row($q);
        $location_name = $location_array[1];
        //$output .= $location_array[9]. ' ';    
        //Build list of possible commands
        $list = "look around, inventory, view inventory, examine $location_name, examine the $location_name";
        $w = mysql_query("SELECT * FROM `objects` WHERE `location` = '$location'");
        while($row = mysql_fetch_row($w))
            {
                $list .= ", examine $row[1], look at $row[1], examine the $row[1], look at the $row[1], look around $row[1], look around the $row[1],  pick up $row[1], take $row[1], pick up the $row[1], take the $row[1], use $row[1], use the $row[1]";
                if($row[8])
                    {
                        $list .= ", $row[8] $row[1], $row[8] the $row[1]";
                    }
            }
        //echo 'Possible Commands:<br>', $list, '<br><br>';
        //Evaluate Input
        if(strpos($input, 'look around') || strpos($input, "examine $location_name") || strpos($input, "examine the $location_name"))
            {
                $output .= 'You look around. ';
                $output .= $location_array[4].' ';
            }
        else if(strpos($input, 'inventory') || strpos($input, 'view inventory'))
            {
                $output .= 'Inventory: ';
                $le = mysql_query("SELECT * FROM `inventory` WHERE `user` = '$user' AND `location` = '0'");
                while($in_inventory = mysql_fetch_row($le))
                    {
                        $output .= $in_inventory[1]. ' ';
                    }
            }
        else
            {
                $w = mysql_query("SELECT * FROM `objects` WHERE `location` = '$location'");
                while($row = mysql_fetch_row($w))
                    {
                        $object_name = $row[1];
                        if(strpos($input, "examine $object_name") || strpos($input, "examine the $object_name") || strpos($input, "look at $object_name") || strpos($input, "look at the $object_name"))
                            {
                                $output .= $row[4].' ';
                            }
                        else if(strpos($input, "pick up $object_name") || strpos($input, "pick up the $object_name") || strpos($input, "take $object_name") || strpos($input, "take the $object_name"))
                            {
                                if($row[5] == 1)
                                    {
                                        $ba = mysql_query("SELECT * FROM `inventory` WHERE `name` = '$row[1]' AND `user` = '$user' LIMIT 1");
                                        if(!mysql_num_rows($ba))
                                            {
                                                //Put item in inventory
                                                $la = mysql_query("INSERT INTO `inventory` (`id`, `name`, `type`, `location`, `examine_text`, `can_pick_up`, `pick_up_text`, `can_use`, `use_verb`, `use_text`, `destroy_used`, `location_after_use`, `state_key`, `state_value`, `required_in_inventory`, `required_state_key`, `required_state_value`, `user`) VALUES ('', '$row[1]', '$row[2]', '0', '$row[4]', '$row[5]', '$row[6]', '$row[7]', '$row[8]', '$row[9]', '$row[10]', '$row[11]', '$row[12]', '$row[13]', '$row[14]', '$row[15]', '$row[16]', '$user')");
                                                $output .= $row[6].' ';
                                            }
                                        else
                                            {
                                                $output .= $row[1]. ' is already in your inventory. ';
                                            }
                                    }
                                else
                                    {
                                        $output .= $row[6].' '; //Cannot be picked up
                                    }
                            }
                        if($row[7] == 1)
                            {
                                $object_verb = $row[8];
                                //Check to see if item requires an item to be in inventory before it is usable
                                if(!$row[14])
                                      {
                                          $req_inventory = 1;
                                      }
                                else
                                      {
                                          $la = mysql_query("SELECT * FROM `inventory` WHERE `user` = '$user' AND `name` = '$row[14]' AND `location` = '0'");
                                          if(mysql_num_rows($la))
                                              {
                                                  $req_inventory = 1;
                                              }
                                      }                                
                                if(strpos($input, "use $object_name") || strpos($input, "use the $object_name") || strpos($input, "$object_verb $object_name") || strpos($input, "$object_verb the $object_name"))
                                    {
                                        if($req_inventory)
                                            {
                                                //Use Object Action
                                                $output .= $row[9].' ';
                                                if($row[11] > 0) 
                                                    {
                                                        $location = $row[11]; //Teleported!
                                                        //$output .= 'You are being teleported to location '.$location.' ';
                                                        $q = mysql_query("SELECT * FROM `objects` WHERE `id` = '$location' LIMIT 1");
                                                        $location_array = mysql_fetch_row($q);
                                                        $location_name = $location_array[1];
                                                        $output .= $location_array[9]. ' ';
                                                    }
                                                if($row[12])
                                                    {
                                                        $ma = mysql_query("DELETE FROM `states` WHERE `user` = '$user' AND `key` = $row[12]");
                                                        $za = mysql_query("INSERT INTO `states` (`id`, `user`, `location`, `key`, `value`) VALUES (NULL, '$user', '$location', '$row[12]', '$row[13]')");
                                                    }
                                            }
                                        else
                                            {
                                                $output .= "You can't do that because $row[14] is not in your inventory. ";
                                            }
                                    }
                            }
                    }
            }
        //Output
        
        //Request Input
        //echo '<form action="game2.php" method="post"><input type="text" name="input"> <input type="hidden" name="location" value="', $location, '"> <input type="hidden" name="user" value="', $user, '">  <input type="submit" name="submit" value="submit"></form>';
        $tropo = new Tropo();
        $tropo->say($output);
        $options = array("choices" => "$list", "name" => "color", "attempts" => 3);
        $tropo->ask("What would you like to do?", $options);
        $tropo->on(array("event" => "continue", "next" => "game.php?location=$location"));
        $tropo->RenderJson();

        //Update Location
        $moo = mysql_query("UPDATE `users` SET `location` = '$location' WHERE `id` = '$user' LIMIT 1");
    }
?>