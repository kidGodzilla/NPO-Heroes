<?php
include 'connect.inc.php';
$input = ' ';
$output = '';
$debug = $_GET['debug'];
if(!$debug)
    {
        $debug = $_POST['debug'];
    }
if(!$debug)
    {
        require 'tropo.class.php';
        $location = $_GET['location'];
        $user = $_GET['user'];
        if($location)
            {
                $tropo = new Tropo();
                    @$result = new Result();
                    $answer = $result->getValue();
                    $input .= $answer;
            }
        else
            {
                $user = rand(111, 99999);
                $output .= "Welcome to the lonely galaxy's guide to hitch hiking, an interactive science fiction adventure game.<br><br>You wake up. The room is spinning very gently round your head. Or at least it would be if you could see it, which you can't. It is pitch black. There seems to be a lamp within arms reach.<br><br>";
                $location = 1;
            }
    }
else
    {
        echo '<html><body>';
        $location = $_POST['location'];
        echo 'location ', $location, '<br>';
        $input .= $_POST['input'];
        $user = $_GET['user'];
        if(!$user)
            {
                $user = $_POST['user'];
            }
    }


//Main Loop
if($location)
    {
        //Get Location Text
        $q = mysql_query("SELECT * FROM `objects` WHERE `id` = '$location' LIMIT 1");
        $location_array = mysql_fetch_row($q);
        $location_name = $location_array[1]; 

        
        //Evaluate Input
        
        
        //Look Around
        if(strpos($input, 'look around') || strpos($input, "examine $location_name") || strpos($input, "examine the $location_name"))
            {
                $output .= 'You look around.<br>';
                $output .= $location_array[4].' ';
                //list of items not in inventory
                $w = mysql_query("SELECT * FROM `objects` WHERE `location` = '$location' AND `can_pick_up` = '1'");
                while($row = mysql_fetch_row($w))
                    {
                        $ba = mysql_query("SELECT * FROM `inventory` WHERE `name` = '$row[1]' AND `user` = '$user'");
                        if(!mysql_num_rows($ba))
                            {
                                $output .= "You see a $row[1]. ";
                            }
                    }
                //list of items previously dropped
                $w = mysql_query("SELECT * FROM `inventory` WHERE `location` = '$location' AND `can_pick_up` = '1' AND `user` = '$user'");
                while($row = mysql_fetch_row($w))
                    {
                        $output .= "You see a $row[1]. ";
                    }                    
            }
        
        
        //View Inventory
        if(strpos($input, 'inventory') || strpos($input, 'view inventory') || strpos($input, 'view the inventory'))
            {
                $output .= 'Inventory:<br>';
                $le = mysql_query("SELECT * FROM `inventory` WHERE `user` = '$user' AND `location` = '0'");
                while($in_inventory = mysql_fetch_row($le))
                    {
                        $output .= $in_inventory[1]. '<br>';
                    }
            }
        else
            {
                //Specific Actions for Objects within a Location-State
                $w = mysql_query("SELECT * FROM `objects` WHERE `location` = '$location'");
                while($row = mysql_fetch_row($w))
                    {
                        $verbed = 0;
                        $req_inventory = 0;
                        $not_destroyed = 0;
                        $object_name = $row[1];
                        //Examine an Object within a Location-State
                        if(strpos($input, "examine $object_name") || strpos($input, "examine the $object_name") || strpos($input, "look at $object_name") || strpos($input, "look at the $object_name"))
                            {
                                $output .= $row[4].' ';
                            }
                        //Pick up an Object within a Location-State
                        if(strpos($input, "pick up $object_name") || strpos($input, "pick up the $object_name") || strpos($input, "take $object_name") || strpos($input, "take the $object_name"))
                            {
                                if($row[5] == 1)
                                    {
                                        $ba = mysql_query("SELECT * FROM `inventory` WHERE `name` = '$row[1]' AND `user` = '$user' LIMIT 1"); //If picked up and dropped somewhere else it still has to be 'gone'
                                        if(!mysql_num_rows($ba))
                                            {
                                                //Put item in inventory
                                                $la = mysql_query("INSERT INTO `inventory` (`id`, `name`, `type`, `location`, `examine_text`, `can_pick_up`, `pick_up_text`, `can_use`, `use_verb`, `use_text`, `destroy_used`, `location_after_use`, `state_key`, `state_value`, `required_in_inventory`, `required_state_key`, `required_state_value`, `user`) VALUES ('', '$row[1]', '$row[2]', '0', '$row[4]', '$row[5]', '$row[6]', '$row[7]', '$row[8]', '$row[9]', '$row[10]', '$row[11]', '$row[12]', '$row[13]', '$row[14]', '$row[15]', '$row[16]', '$user')");
                                                $output .= $row[6].' ';
                                            }
                                        else
                                            {
                                                $output .= 'There is no '.$row[1]. ' here.<br>';
                                            }
                                    }
                                else
                                    {
                                        $output .= $row[6].' '; //Cannot be picked up ever
                                    }
                            }
                        
                        //Execute an Object's main Use Verb    
                        if($row[7] == 1)
                            {
                                $arr = explode(',', $row[8]);
                                foreach($arr as $object_verb)
                                    {
                                        if(strpos($input, "$object_verb $object_name") || strpos($input, "$object_verb the $object_name"))
                                            {
                                                $verbed = 1;
                                                //echo "$row[1] was verbed";
                                            }
                                    }
                                //Check to see if item requires an item to be in inventory before it is usable
                                if(!$row[14])
                                      {
                                          //echo "$row[1] got this far! ";
                                          $req_inventory = 1; //Not required in inventory
                                      }
                                else
                                      {
                                          //echo "$row[1] didn't go to the right place! ";
                                          $la = mysql_query("SELECT * FROM `inventory` WHERE `user` = '$user' AND `name` = '$row[14]' AND `location` = '0'");
                                          if(mysql_num_rows($la))
                                              {
                                                  //echo "Things worked out in the end for $row[1]. ";
                                                  $req_inventory = 1; //Required in inventory AND is actually in inventory
                                              }
                                      }
                                //Check to see if item has been destroyed
                                if($row[10])
                                    {
                                        $mu = mysql_query("SELECT * FROM `states` WHERE `location` = '$location' AND `user` = '$user' AND `key` = '$row[1]' AND `value` = 'destroyed' LIMIT 1");
                                        if(!mysql_num_rows($mu))
                                            {
                                                $not_destroyed = 1;
                                            }
                                    }
                                else
                                    {
                                        $not_destroyed = 1;
                                    }
                                //Execute an Object's main Use Verb if user says 'USE OBJECT' OR 'MAIN-USE-VERB OBJECT'
                                if(strpos($input, "use $object_name") || strpos($input, "use the $object_name") || $verbed)
                                    {
                                        if($req_inventory)
                                            {
                                                //echo "Yes 1 ";
                                                if($not_destroyed)
                                                    {
                                                       //echo "Yes 2";
                                                       //Use Object Action
                                                        $output .= $row[9].' ';
                                                        if($row[11] > 0) 
                                                            {
                                                                $location = $row[11]; //Teleported!
                                                                //$output .= 'You are being teleported to location '. $location.' ';
                                                                $q = mysql_query("SELECT * FROM `objects` WHERE `id` = '$location' LIMIT 1");
                                                                $location_array = mysql_fetch_row($q);
                                                                $output .= $location_array[9]. '<br><br>';
                                                            }
                                                        if($row[12])
                                                            {
                                                                $ma = mysql_query("DELETE FROM `states` WHERE `user` = '$user' AND `key` = $row[12]");
                                                                $za = mysql_query("INSERT INTO `states` (`id`, `user`, `location`, `key`, `value`) VALUES (NULL, '$user', '$location', '$row[12]', '$row[13]')");
                                                            }
                                                        if($row[10])
                                                            {
                                                                $za = mysql_query("INSERT INTO `states` (`id`, `user`, `location`, `key`, `value`) VALUES (NULL, '$user', '$location', '$row[1]', 'destroyed')");
                                                            }
                                                    }
                                                else
                                                    {
                                                        $output .= "You can't do that! ";
                                                    }
                                            }
                                        else
                                            {
                                                $output .= "You can't do that because $row[14] is not in your inventory. ";
                                            }
                                    }
                            }
                    }
                    
                    
                            //Lookup additional verbs in the VERB table
                            $zoo = mysql_query("SELECT * FROM `verbs` WHERE `location` = '0' OR `location` = '$location'");
                            while($ram = mysql_fetch_row($zoo))
                                {
                                    $goat = explode(',', $ram[3]);
                                    foreach($goat as $ram_verb)
                                        {
                                            $zoo_req_inventory = 0;
                                            if(strpos($input, "$ram_verb $ram[1]") || strpos($input, "$ram_verb the $ram[1]"))
                                                {
                                                    //Check to see if item requires an item to be in inventory before it is usable
                                                    if(!$ram[9])
                                                          {
                                                              $zoo_req_inventory = 1; //Not required in inventory
                                                          }
                                                    else
                                                          {
                                                              $la = mysql_query("SELECT * FROM `inventory` WHERE `user` = '$user' AND `name` = '$ram[9]' AND `location` = '0'");
                                                              if(mysql_num_rows($la))
                                                                  {
                                                                      $zoo_req_inventory = 1; //Required in inventory AND is actually in inventory
                                                                  }
                                                          }
                                                    if($zoo_req_inventory)
                                                        {
                                                            //Use Object Action
                                                            $output .= $ram[4].' ';
                                                            if($ram[6] > 0) 
                                                                {
                                                                    $location = $ram[6]; //Teleported!
                                                                    //$output .= 'You are being teleported to location '. $location.' ';
                                                                    $q = mysql_query("SELECT * FROM `objects` WHERE `id` = '$location' LIMIT 1");
                                                                    $location_array = mysql_fetch_row($q);
                                                                    $output .= $location_array[9]. '<br><br>';
                                                                }
                                                            if($ram[7])
                                                                {
                                                                    $ma = mysql_query("DELETE FROM `states` WHERE `user` = '$user' AND `key` = $ram[7]");
                                                                    $za = mysql_query("INSERT INTO `states` (`id`, `user`, `location`, `key`, `value`) VALUES (NULL, '$user', '$location', '$ram[7]', '$ram[8]')");
                                                                }
                                                            if($ram[5])
                                                                {
                                                                    $za = mysql_query("INSERT INTO `states` (`id`, `user`, `location`, `key`, `value`) VALUES (NULL, '$user', '$location', '$ram[1]', 'destroyed')");
                                                                }
                                                            if($ram[12])
                                                                {
                                                                    //Drop item
                                                                    $ba = mysql_query("SELECT * FROM `inventory` WHERE `name` = '$ram[12]' AND `user` = '$user' AND `location` = '0' LIMIT 1");
                                                                    if(mysql_num_rows($ba))
                                                                        {
                                                                            //Discard item from inventory into current location if this is not where the object was spawned
                                                                            $nupe = mysql_query("SELECT * FROM `inventory` WHERE `name` = '$ram[12]' AND `location` = '$location'");
                                                                            if(mysql_num_rows($nupe))
                                                                                {
                                                                                    $la = mysql_query("UPDATE `inventory` SET `location` = '$location' WHERE `name` = '$ram[12]' AND `user` = '$user'");
                                                                                }
                                                                            else
                                                                                {
                                                                                    $la = mysql_query("DELETE FROM `inventory` WHERE `name` = '$ram[12]' AND `user` = '$user'");
                                                                                }
                                                                            //$output .= "You dropped $row[1].<br>";
                                                                            //Quiet on drop because the user does not perceive this as an item drop
                                                                        }
                                                                    else
                                                                        {
                                                                            //Quiet on error because the user may not know they are dropping an item
                                                                            //$output .= $row[1]. ' is not in your inventory.<br>';
                                                                        }
                                                                }
                                                        }
                                                    else
                                                        {
                                                            //Quiet output
                                                            //$output .= "You can't do that because $ram[9] is not in your inventory. ";
                                                        }
                                                }
                                        }
                                }
                            //Lookup additional commands from inventory
                            //Specific Actions for Objects within a Location-State
                            $w = mysql_query("SELECT * FROM `inventory` WHERE `user` = '$user'");
                            while($row = mysql_fetch_row($w))
                                {
                                    if($row[3] == 0 || $row[3] == $location)
                                        {
                                            $verbed = 0;
                                            $req_inventory = 0;
                                            $not_destroyed = 0;
                                            $dupe_in_inventory = 0;
                                            $object_name = $row[1];
                                            //Examine an Object in Inventory
                                            if(strpos($input, "examine $object_name") || strpos($input, "examine the $object_name") || strpos($input, "look at $object_name") || strpos($input, "look at the $object_name"))
                                                {
                                                    $output .= $row[4].' ';
                                                }
                                            //Pick up an Object which was previously dropped
                                            if(strpos($input, "pick up $object_name") || strpos($input, "pick up the $object_name") || strpos($input, "take $object_name") || strpos($input, "take the $object_name"))
                                                {
                                                    if($row[5] == 1)
                                                        {
                                                            //$ba = mysql_query("SELECT * FROM `inventory` WHERE `name` = '$row[1]' AND `user` = '$user' AND `location` = '$location'"); //It must be in this location
                                                            if($location == $row[3])
                                                                {
                                                                    //Put item in inventory
                                                                    //$la = mysql_query("INSERT INTO `inventory` (`id`, `name`, `type`, `location`, `examine_text`, `can_pick_up`, `pick_up_text`, `can_use`, `use_verb`, `use_text`, `destroy_used`, `location_after_use`, `state_key`, `state_value`, `required_in_inventory`, `required_state_key`, `required_state_value`, `user`) VALUES ('', '$row[1]', '$row[2]', '0', '$row[4]', '$row[5]', '$row[6]', '$row[7]', '$row[8]', '$row[9]', '$row[10]', '$row[11]', '$row[12]', '$row[13]', '$row[14]', '$row[15]', '$row[16]', '$user')");
                                                                    $boo = mysql_query("UPDATE `inventory` SET `location` = '0' WHERE `id` = '$row[0]'");
                                                                    $output .= $row[6] .' ';
                                                                }
                                                            else
                                                                {
                                                                    //Quiet
                                                                    //$output .= 'There is no '.$row[1]. ' here.<br>';
                                                                }
                                                        }
                                                    else
                                                        {
                                                            $output .= $row[6].' '; //Cannot be picked up ever
                                                        }
                                                }
                                                
                                            //Drop an object from Inventory
                                            if(strpos($input, "drop $object_name") || strpos($input, "drop the $object_name") || strpos($input, "discard $object_name") || strpos($input, "discard the $object_name"))
                                                {
                                                    if($row[5] == 1)
                                                        {
                                                            //$ba = mysql_query("SELECT * FROM `inventory` WHERE `name` = '$row[1]' AND `user` = '$user' AND `location` = '0'");
                                                            if($row[3] == 0)
                                                                {
                                                                    //Discard item from inventory into current location if this is not where the object was spawned
                                                                    $nupe = mysql_query("SELECT * FROM `objects` WHERE `name` = '$row[1]' AND `location` = '$location'");
                                                                    if(!mysql_num_rows($nupe))
                                                                        {
                                                                            $la = mysql_query("UPDATE `inventory` SET `location` = '$location' WHERE `name` = '$row[1]' AND `user` = '$user'");
                                                                        }
                                                                    else
                                                                        {
                                                                            $la = mysql_query("DELETE FROM `inventory` WHERE `name` = '$row[1]' AND `user` = '$user'");
                                                                        }
                                                                    $output .= "You dropped $row[1].<br>";
                                                                }
                                                            else
                                                                {
                                                                    $output .= $row[1]. ' is not in your inventory.<br>';
                                                                }
                                                        }
                                                    else
                                                        {
                                                            $output .= "$row[1] Cannot be dropped.<br>"; //Cannot be picked up or dropped ever
                                                        }
                                                }


                                            //Execute an Object's main Use Verb    
                                            if($row[7] == 1)
                                                {
                                                    $arr = explode(',', $row[8]);
                                                    foreach($arr as $object_verb)
                                                        {
                                                            if(strpos($input, "$object_verb $object_name") || strpos($input, "$object_verb the $object_name"))
                                                                {
                                                                    $verbed = 1;
                                                                    //echo "$row[1] was verbed";
                                                                }
                                                        }
                                                    //Check to see if item requires an item to be in inventory before it is usable
                                                    if(!$row[14])
                                                          {
                                                              //echo "$row[1] got this far! ";
                                                              $req_inventory = 1; //Not required in inventory
                                                          }
                                                    else
                                                          {
                                                              //echo "$row[1] didn't go to the right place! ";
                                                              $la = mysql_query("SELECT * FROM `inventory` WHERE `user` = '$user' AND `name` = '$row[14]' AND `location` = '0'");
                                                              if(mysql_num_rows($la))
                                                                  {
                                                                      //echo "Things worked out in the end for $row[1]. ";
                                                                      $req_inventory = 1; //Required in inventory AND is actually in inventory
                                                                  }
                                                          }
                                                    //Check to see if item has been destroyed
                                                    if($row[10])
                                                        {
                                                            $mu = mysql_query("SELECT * FROM `states` WHERE `location` = '$location' AND `user` = '$user' AND `key` = '$row[1]' AND `value` = 'destroyed' LIMIT 1");
                                                            if(!mysql_num_rows($mu))
                                                                {
                                                                    $not_destroyed = 1;
                                                                }
                                                        }
                                                    else
                                                        {
                                                            $not_destroyed = 1;
                                                        }
                                                    //Check to see if a copy of this item is in the user's inventory
                                                    $la = mysql_query("SELECT * FROM `inventory` WHERE `user` = '$user' AND `name` = '$row[1]' AND `location` = '0'");
                                                    if(mysql_num_rows($la))
                                                        {
                                                            $dupe_in_inventory = 1; //Required in inventory AND is actually in inventory
                                                        }
                                                    //Execute an Object's main Use Verb if user says 'USE OBJECT' OR 'MAIN-USE-VERB OBJECT'
                                                    if(strpos($input, "use $object_name") || strpos($input, "use the $object_name") || $verbed)
                                                        {
                                                            if($req_inventory)
                                                                {
                                                                    //echo "Yes 1 ";
                                                                    if($not_destroyed)
                                                                        {
                                                                           //echo "Yes 2";
                                                                           //Use Object Action
                                                                            if(!$dupe_in_inventory)
                                                                                {
                                                                                    $output .= $row[9].' ';
                                                                                    if($row[11] > 0) 
                                                                                        {
                                                                                            $location = $row[11]; //Teleported!
                                                                                            //$output .= 'You are being teleported to location '. $location.' ';
                                                                                            $q = mysql_query("SELECT * FROM `objects` WHERE `id` = '$location' LIMIT 1");
                                                                                            $location_array = mysql_fetch_row($q);
                                                                                            $output .= $location_array[9]. '<br><br>';
                                                                                        }
                                                                                    if($row[12])
                                                                                        {
                                                                                            $ma = mysql_query("DELETE FROM `states` WHERE `user` = '$user' AND `key` = $row[12]");
                                                                                            $za = mysql_query("INSERT INTO `states` (`id`, `user`, `location`, `key`, `value`) VALUES (NULL, '$user', '$location', '$row[12]', '$row[13]')");
                                                                                        }
                                                                                    if($row[10])
                                                                                        {
                                                                                            $za = mysql_query("INSERT INTO `states` (`id`, `user`, `location`, `key`, `value`) VALUES (NULL, '$user', '$location', '$row[1]', 'destroyed')");
                                                                                        }
                                                                                }
                                                                        }
                                                                    else
                                                                        {
                                                                            $output .= "You can't do that! ";
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
            }


        //Build list of possible commands for VOX RECOGNITION
        $list = "look around, inventory, view inventory, view the inventory, examine $location_name, examine the $location_name, look around $location_name, look around the $location_name";
        $w = mysql_query("SELECT * FROM `objects` WHERE `location` = '$location'");
        while($row = mysql_fetch_row($w))
            {
                $list .= ", examine $row[1], look at $row[1], examine the $row[1], look at the $row[1],  pick up $row[1], take $row[1], pick up the $row[1], take the $row[1], use $row[1], use the $row[1]";
                if($row[8])
                    {
                        $arr = explode(',', $row[8]);
                        foreach($arr as $verb)
                            {
                                $list .= ", $verb $row[1], $verb the $row[1]";
                            }
                    }
            }
        $w = mysql_query("SELECT * FROM `inventory` WHERE `user` = '$user'");
        while($row = mysql_fetch_row($w))
            {
                if($row[3] == 0 || $row[3] == $location)
                    {
                        $list .= ", examine $row[1], look at $row[1], examine the $row[1], look at the $row[1], discard $row[1], discard the $row[1], drop $row[1], drop the $row[1], use $row[1], use the $row[1]";
                        if($row[8])
                            {
                                $arr = explode(',', $row[8]);
                                foreach($arr as $verb)
                                    {
                                        $list .= ", $verb $row[1], $verb the $row[1]";
                                    }
                            }
                    }
            }            
        $zoo = mysql_query("SELECT * FROM `verbs` WHERE `location` = '0' OR `location` = '$location'");
        while($ram = mysql_fetch_row($zoo))
            {
                //Check to see if item requires an item to be in inventory before it is usable
                if(!$ram[9])
                    {
                        $zoo_req_inventory = 1; //Not required in inventory
                    }
                else
                    {
                        $la = mysql_query("SELECT * FROM `inventory` WHERE `user` = '$user' AND `name` = '$ram[9]' AND `location` = '0'");
                        if(mysql_num_rows($la))
                            {
                                $zoo_req_inventory = 1; //Required in inventory AND is actually in inventory
                            }
                    }
                if($zoo_req_inventory)
                    {
                        $goat = explode(',', $ram[3]);
                        foreach($goat as $ram_verb)
                            {
                                $list .= ", $ram_verb $ram[1], $ram_verb the $ram[1]";
                            }
                    }
            }
        if($debug) { $output .= '<br><br>Possible Commands:<br>'. $list. '<br><br>'; }
        
        
        //Output
        //Request Input
        if($debug)
            {
            echo $output;
            echo '<br><br><form action="game2.php" method="post"><input type="text" name="input"> <input type="hidden" name="debug" value="1"> <input type="hidden" name="location" value="', $location, '"> <input type="hidden" name="user" value="', $user, '">  <input type="submit" name="submit" value="submit"></form>';
            }
        else
            {
                $output_a = explode('<br>', $output);
                $output = implode(' ', $output_a);
                $tropo = new Tropo();
                $tropo->say($output, array("voice" => "Simon","allowSignals" => "","required" => "true"));
                $options = array("choices" => "$list", "name" => "color", "attempts" => 5, "voice" => "Simon");
                $tropo->ask("What would you like to do?", $options);
                $tropo->on(array("event" => "continue", "next" => "game2.php?location=$location&user=$user"));
                $tropo->RenderJson();
            }
    }
?>