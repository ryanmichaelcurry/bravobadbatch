<?php
class Apollo
{
    private $conn, $host, $username, $password, $database;

    function __construct($host, $username, $password, $database)
    {
        session_start();

        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;

        // Connect to Database
        $this->conn = new mysqli($host, $username, $password, $database);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    
    public function ip()
    {
        if (isset($_SESSION["ip"]))
        {
            $session_id = session_id();
            // prepare and bind
            $stmt = $this->conn->prepare("UPDATE `views` SET `timestamp`= CURRENT_TIMESTAMP WHERE `session_id` = ?");
            $stmt->bind_param("s", $session_id);
            $stmt->execute();
        }
        if (!isset($_SESSION["ip"]))
        {
            $user_ip = getenv('REMOTE_ADDR');
            $geo = unserialize(file_get_contents("http://www.geoplugin.net/php.gp?ip=$user_ip"));
            $country = $geo["geoplugin_countryName"];
            $city = $geo["geoplugin_city"];
            $latitude = $geo["geoplugin_latitude"];
            $longitude = $geo["geoplugin_longitude"];

            $_SESSION["ip"]=$_SERVER["REMOTE_ADDR"];
            $_SESSION["country"]=$country;
            $_SESSION["city"]=$city;
            $_SESSION["latitude"]=$latitude;
            $_SESSION["longitude"]=$longitude;
            $session_id = session_id();

            // prepare and bind
            $stmt = $this->conn->prepare("INSERT INTO `views` (`ip`, `session_id`, `country`, `city`, `latitude`, `longitude`,`timestamp`) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->bind_param("ssssss", $_SESSION["ip"], $session_id, $_SESSION["country"], $_SESSION["city"], $_SESSION["latitude"], $_SESSION["longitude"]);
            $stmt->execute();

        }
    }


    public function sanitize(array $array, array $keep, $exclude)	//exclude for htmlspecialchars
    {
        foreach ($array as $key => $value)
        {
			if(gettype($exclude)=="array")
            {
                if(!in_array($key, $exclude))
                {
                	$array[$key]=htmlspecialchars($array[$key]);
                }
			}
			
            if(!in_array($key, $keep))
            {
                unset($array[$key]);
            }

        }
        return($array);
    }

    public function insert($table, $array)
	{
		if(sizeof($array) > 0)
		{
            $conn = $this->conn;
        
			//continuation of $sql varaible construction
			$sql = "INSERT INTO `$table` (";
			$sqlValues = "VALUES (";
			$COMMAcount = sizeof($array);
			$datatypes = "";
			foreach ($array as $key => $value)
			{
				if($COMMAcount > 1)
				{
					$sql .= "`$key`, ";
					$sqlValues .= "?, ";
				}
				//i.e. last in array (place `timestamp`)
				else
				{
					$sql .= "`$key`, `timestamp`) ";
					$sqlValues .= "?, CURRENT_TIMESTAMP)";
				}
				$COMMAcount -= 1;

				//code below is for the bind_param function used later
				switch (gettype($value)) {
					case 'integer':
						$datatypes .= "i";
						break;
					
					case 'double':
						$datatypes .= "d";
						break;

					case 'string':
						$datatypes .= "s";
						break;

					default:
						$datatypes .= "b";
				}
			}
			$sql .= $sqlValues;

			$bindParamArray = array();
			foreach ($array as $key => $value)
			{
				$bindParamArray = array_merge($bindParamArray, array($value));
            }
            

			// prepare and bind
            $stmt = $conn->prepare($sql);
			$stmt->bind_param($datatypes, ...$bindParamArray);

			$stmt->execute();

			$stmt->close();

			$this->result = $conn;
			return(true);
        }
    }

    public function select(string $table, array $array, array $order=[])
	{
		$conn = $this->conn;

		//used to get the datatypes needed for bind_param
		$datatypes = "";

		//continuation of $sql varaible construction
		$sql = "SELECT * FROM `$table` WHERE ";
		$ANDcount = sizeof($array);
		foreach ($array as $key => $value)
		{
			if($ANDcount > 1)
			{
				$sql .= " `$key` = ? AND";
			}
			else
			{
				$sql .= " `$key` = ?";
			}
			$ANDcount -= 1;

			//code below is for the bind_param function used later
			switch (gettype($value)) {
				case 'integer':
					$datatypes .= "i";
					break;
				
				case 'double':
					$datatypes .= "d";
					break;

				case 'string':
					$datatypes .= "s";
					break;

				default:
					$datatypes .= "b";

			}
        }
        
        if(!empty($order))
        {
            $sql.=" ORDER BY `$order[column]` $order[order] LIMIT $order[offset], $order[limit]";
        }

        //make one dimentional array
		$bindParamArray = array();
		foreach ($array as $key => $value)
		{
			$bindParamArray = array_merge($bindParamArray, array($value));
		}

		// prepare and bind
		$stmt = $conn->prepare($sql);
		$stmt->bind_param($datatypes, ...$bindParamArray);
        $stmt->execute();
        
        //stack overflow save (idk what it does)
		$meta = $stmt->result_metadata();
	    while ($field = $meta->fetch_field())
	    {
	        $params[] = &$row[$field->name];
	    }

        //dynamically bind results
	    call_user_func_array(array($stmt, 'bind_result'), $params);

        //return result as 3d array
	    $result = [];
	    $i=0;
	    while ($stmt->fetch())
	    {
	        foreach($row as $key => $val)
	        {
	            $result[$i][$key] = $val;
	        }
	        $i++;
	    }
	    return($result);
	}
	
	public function update(string $table, array $array, array $where)
	{
		$conn = $this->conn;

		//used to get the datatypes needed for bind_param
		$datatypes = "";

		//continuation of $sql varaible construction
		$sql = "UPDATE `$table` SET";
		$ANDcount = sizeof($array);
		foreach ($array as $key => $value)
		{
			if($ANDcount > 1)
			{
				$sql .= " `$key` = ?,";
			}
			else
			{
				$sql .= " `$key` = ?";
			}
			$ANDcount -= 1;

			//code below is for the bind_param function used later
			switch (gettype($value)) {
				case 'integer':
					$datatypes .= "i";
					break;
				
				case 'double':
					$datatypes .= "d";
					break;

				case 'string':
					$datatypes .= "s";
					break;

				default:
					$datatypes .= "b";

			}
        }
		
		$sql .= " WHERE";
        $ANDcount = sizeof($where);
		foreach ($where as $key => $value)
		{
			if($ANDcount > 1)
			{
				$sql .= " `$key` = ? AND";
			}
			else
			{
				$sql .= " `$key` = ?";
			}
			$ANDcount -= 1;

			//code below is for the bind_param function used later
			switch (gettype($value)) {
				case 'integer':
					$datatypes .= "i";
					break;
				
				case 'double':
					$datatypes .= "d";
					break;

				case 'string':
					$datatypes .= "s";
					break;

				default:
					$datatypes .= "b";

			}
        }

        //make one dimentional array
		$bindParamArray = array();
		foreach ($array as $key => $value)
		{
			$bindParamArray = array_merge($bindParamArray, array($value));
		}
		foreach ($where as $key => $value)
		{
			$bindParamArray = array_merge($bindParamArray, array($value));
		}

		// prepare and bind
		$stmt = $conn->prepare($sql);
		var_dump($sql);
		var_dump($array);
		var_dump($datatypes);
		var_dump($bindParamArray);
		$stmt->bind_param($datatypes, ...$bindParamArray);
        $stmt->execute();

		return($stmt);
	}
	
	public function delete(string $table, array $array)
	{
		$conn = $this->conn;

		//used to get the datatypes needed for bind_param
		$datatypes = "";

		//continuation of $sql varaible construction
		$sql = "DELETE `$table` WHERE ";
		$ANDcount = sizeof($array);
		foreach ($array as $key => $value)
		{
			if($ANDcount > 1)
			{
				$sql .= " `$key` = ? AND";
			}
			else
			{
				$sql .= " `$key` = ?";
			}
			$ANDcount -= 1;

			//code below is for the bind_param function used later
			switch (gettype($value)) {
				case 'integer':
					$datatypes .= "i";
					break;
				
				case 'double':
					$datatypes .= "d";
					break;

				case 'string':
					$datatypes .= "s";
					break;

				default:
					$datatypes .= "b";

			}
        }

        //make one dimentional array
		$bindParamArray = array();
		foreach ($array as $key => $value)
		{
			$bindParamArray = array_merge($bindParamArray, array($value));
		}

		// prepare and bind
		$stmt = $conn->prepare($sql);
		$stmt->bind_param($datatypes, ...$bindParamArray);
        $stmt->execute();
		
		$apollo->result = $stmt;
	    return(true);
	}
	
	public function whitelist(string $table, array $array)
	{
		$conn = $this->conn;

		$describe = $this->describe($table);
		$columns = array();
		$whitelist = array();

		foreach($describe as $key => $value)
		{
			array_merge($columns, array($value["Field"]));
		}

		foreach ($array as $key => $value) 
		{
			if(array_key_exists($key, $columns))
			{
				array_merge($whitelist, array($key=>$value));
			}
		}

		return($whitelist);
	}
	
	public function describe(string $table)
	{
		$conn = $this->conn;

		// prepare and bind
		$stmt = $conn->prepare("DESCRIBE `$table`");
		//$stmt->bind_param("s", $table);
		$stmt->execute();
		
		//stack overflow save (idk what it does)
		$meta = $stmt->result_metadata();
	    while ($field = $meta->fetch_field())
	    {
	        $params[] = &$row[$field->name];
	    }

        //dynamically bind results
	    call_user_func_array(array($stmt, 'bind_result'), $params);

        //return result as 3d array
	    $result = [];
	    $i=0;
	    while ($stmt->fetch())
	    {
	        foreach($row as $key => $val)
	        {
	            $result[$i][$key] = $val;
	        }
	        $i++;
	    }
	    return($result);
    }
    

    //Methods Pertaining to User Creation, Deletion, Logining in, etc.

	public function signup($array)
	{
		if(sizeof($array) > 0)
		{

			$conn = $this->conn;


			// prepare and bind
			$stmt = $conn->prepare("SELECT `email` FROM `user` WHERE `email` = ?");
			$stmt->bind_param("s", $array["email"]);
			$stmt->execute();
			$stmt->store_result();

			$sql_username = TRUE;
			if($stmt->num_rows <= 0)
			{
				$sql_username = FALSE;
			}
			$stmt->close();

        	// If user does not exist
        	if (!$sql_username)
        	{
        		$array["password"] = password_hash($array["password"], PASSWORD_DEFAULT);
        		$array["profile"] = "/img/default.png";

        		//used to get the datatypes needed for bind_param
				$datatypes = "";

				//continuation of $sql varaible construction
				$sql = "INSERT INTO `user` (";
				$sqlValues = "VALUES (";
				$COMMAcount = sizeof($array);
				foreach ($array as $key => $value)
				{
					if($COMMAcount > 1)
					{
						$sql .= "`$key`, ";
						$sqlValues .= "?, ";
					}
					//i.e. last in array (place `timestamp`)
					else
					{
						$sql .= "`$key`, `timestamp`) ";
						$sqlValues .= "?, CURRENT_TIMESTAMP)";
					}
					$COMMAcount -= 1;

					//code below is for the bind_param function used later
					switch (gettype($value)) {
						case 'integer':
							$datatypes .= "i";
							break;
						
						case 'double':
							$datatypes .= "d";
							break;

						case 'string':
							$datatypes .= "s";
							break;

						default:
							$datatypes .= "b";

					}
				}
				$sql .= $sqlValues;

				$bindParamArray = array();
				foreach ($array as $key => $value)
				{
					$bindParamArray = array_merge($bindParamArray, array($value));
				}

				// prepare and bind
				$stmt = $conn->prepare($sql);
				$stmt->bind_param($datatypes, ...$bindParamArray);

				$stmt->execute();

				$stmt->close();
				$conn->close();

				return(true);
        	}

			//If user already exists
			if ($sql_username == $this->username)
			{
				$this->error = "User Already Exists";
				return(false);
			}
		}
	}

	public function updateUsers($idarray, $array)
	{
		if(sizeof($array) > 0)
		{
			foreach ($array as $key => $value)
			{
				if(!in_array($key, array('username', 'email', 'name', 'picture', 'description', 'coverimg')))
				{
					$this->error = "Added values are not allowed";
					return(false);
				}
			}

			//used to get the datatypes needed for bind_param
			$datatypes = "";

			//continuation of $sql varaible construction
			$sql = "UPDATE `users` SET ";
			$COMMAcount = sizeof($array);

			foreach ($array as $key => $value)
			{
				if($COMMAcount > 1)
				{
					$sql .= "`$key` = ?, ";
				}
				//i.e. last in array (place `timestamp`)
				else
				{
					$sql .= "`$key` = ? ";
				}
				$COMMAcount -= 1;

				//code below is for the bind_param function used later
				switch (gettype($value)) {
					case 'integer':
						$datatypes .= "i";
						break;
					
					case 'double':
						$datatypes .= "d";
						break;

					case 'string':
						$datatypes .= "s";
						break;

					default:
						$datatypes .= "b";

				}
			}

			$whereValues = "WHERE ";
			$COMMAcount = sizeof($idarray);
			foreach ($idarray as $key => $value)
			{
				if($COMMAcount > 1)
				{
					$whereValues .= "`$key` = ?, ";
				}
				//i.e. last in array (place `timestamp`)
				else
				{
					$whereValues .= "`$key` = ?";
				}
				$COMMAcount -= 1;

				//code below is for the bind_param function used later
				switch (gettype($value)) {
					case 'integer':
						$datatypes .= "i";
						break;
					
					case 'double':
						$datatypes .= "d";
						break;

					case 'string':
						$datatypes .= "s";
						break;

					default:
						$datatypes .= "b";

				}
			}


			$sql .= $whereValues;	//merge beginning and end of sql statement

			$bindParamArray = array();
			foreach ($array as $key => $value)
			{
				$bindParamArray = array_merge($bindParamArray, array($value));
			}
			foreach ($idarray as $key => $value)
			{
				$bindParamArray = array_merge($bindParamArray, array($value));
			}

			$conn = $this->conn;
			// prepare and bind
			$stmt = $conn->prepare($sql);
			$stmt->bind_param($datatypes, ...$bindParamArray);

			$stmt->execute();

			$stmt->close();
			$conn->close();

			return(true);
		}
	}

	public function login($array)
	{
		if(sizeof($array) > 0)
		{
			$conn = $this->conn;

			//Get password used to verify the legitimacy of the user later
			$password = $array["password"];
			unset($array["password"]);

			//used to get the datatypes needed for bind_param
			$datatypes = "";

			//continuation of $sql varaible construction
			$sql = "SELECT * FROM `users` WHERE";
			$ANDcount = sizeof($array);
			foreach ($array as $key => $value)
			{
				if($ANDcount > 1)
				{
					$sql .= " `$key` = ? AND";
				}
				else
				{
					$sql .= " `$key` = ?";
				}
				$ANDcount -= 1;

				//code below is for the bind_param function used later
				switch (gettype($value)) {
					case 'integer':
						$datatypes .= "i";
						break;
					
					case 'double':
						$datatypes .= "d";
						break;

					case 'string':
						$datatypes .= "s";
						break;

					default:
						$datatypes .= "b";

				}
			}

			$bindParamArray = array();
			foreach ($array as $key => $value)
			{
				$bindParamArray = array_merge($bindParamArray, array($value));
			}


			// prepare and bind
			$stmt = $conn->prepare($sql);


			$stmt->bind_param($datatypes, ...$bindParamArray);

			$stmt->execute();

			$meta = $stmt->result_metadata();
		    while ($field = $meta->fetch_field())
		    {
		        $params[] = &$row[$field->name];
		    }

		    call_user_func_array(array($stmt, 'bind_result'), $params);

		    $result = [];
		    while ($stmt->fetch()) {
		        foreach($row as $key => $val)
		        {
		            $result[$key] = $val;
		        }
		    }


			// Verify Password
			if(isset($result["password"]))
			{
				if(password_verify($password, $result["password"]))
				{
					foreach ($result as $key => $value)
					{
						if(in_array($key, array('id', 'type', 'name', 'username', 'email', 'picture', 'coverimg', 'timestamp')))
						{
							$_SESSION[$key] = $value;
						}
					}
				}
				else
				{
					$this->error = "Incorrect";
					return(false);
				}
			}
			else
			{
				$this->error = "User does not exist";
				return(false);
			}

			$stmt->close();
			$conn->close();

			return(true);
		}
		else
		{
			$this->error="Array is empty";
			return(false);
		}

	}

	public function changePassword($userid, $password)
	{

		$password = password_hash($password, PASSWORD_DEFAULT);

		$conn = $this->conn;
		$stmt = $conn->prepare("UPDATE `users` SET `password` = ? WHERE `id` = ?");
		$stmt->bind_param("si", $password, $userid);
		$stmt->execute();

		$stmt->close();
		$conn->close();

		return(true);
	}

	public function loginReset($array)
	{
		if(sizeof($array) > 0)
		{

			$conn = $this->conn;

			//used to get the datatypes needed for bind_param
			$datatypes = "";

			//continuation of $sql varaible construction
			$sql = "SELECT * FROM `users` WHERE";
			$ANDcount = sizeof($array);
			foreach ($array as $key => $value)
			{
				if($ANDcount > 1)
				{
					$sql .= " `$key` = ? AND";
				}
				else
				{
					$sql .= " `$key` = ?";
				}
				$ANDcount -= 1;

				//code below is for the bind_param function used later
				switch (gettype($value)) {
					case 'integer':
						$datatypes .= "i";
						break;
					
					case 'double':
						$datatypes .= "d";
						break;

					case 'string':
						$datatypes .= "s";
						break;

					default:
						$datatypes .= "b";

				}
			}

			$bindParamArray = array();
			foreach ($array as $key => $value)
			{
				$bindParamArray = array_merge($bindParamArray, array($value));
			}


			// prepare and bind
			$stmt = $conn->prepare($sql);

			$stmt->bind_param($datatypes, ...$bindParamArray);

			$stmt->execute();

			$meta = $stmt->result_metadata();
		    while ($field = $meta->fetch_field())
		    {
		        $params[] = &$row[$field->name];
		    }

		    call_user_func_array(array($stmt, 'bind_result'), $params);

		    $result = [];
		    while ($stmt->fetch()) {
		        foreach($row as $key => $val)
		        {
		            $result[$key] = $val;
		        }
		    }


			// Verify User's Existence
			if(isset($result["id"]))
			{
				foreach ($result as $key => $value)
				{
					if(in_array($key, array('id', 'type', 'name', 'username', 'email', 'picture', 'coverimg', 'timestamp')))
					{
						$_SESSION[$key] = $value;
					}
				}
			}
			else
			{
				$this->error = "User does not exist";
				return(false);
			}

			$stmt->close();
			$conn->close();

			return(true);
		}
		else
		{
			$this->error="Array is empty";
			return(false);
		}

	}

	public function logout()
	{
		if(isset($_SESSION))
		{
			foreach ($_SESSION as $key => $value)
			{
				if(in_array($key, array('id', 'type', 'username', 'email', 'picture', 'timestamp')))
				{
					unset($_SESSION[$key]);
				}
			}
			return(true);
		}
		else
		{
			$this->error = "Session not started";
			return(false);
		}
	}

	public function joinChannel($user, $channel)
	{
		$conn = $this->conn;

		$stmt = $conn->prepare("SELECT `id`, `users`, `users_int` FROM `channels` WHERE `url` = ?");	//this is the one being followed
		$stmt->bind_param("s", $channel);
		$stmt->execute();
		$stmt->bind_result($channel_id, $users, $users_int);
		$stmt->fetch();

		$channelExists = TRUE;
		if($stmt->num_rows <= 0)
		{
			$channelExists = FALSE;
		}
		if(!$channelExists)
		{
			$this->error = "channel does not exists";
		}
		$stmt->close();

		$stmt = $conn->prepare("SELECT `channels` FROM `users` WHERE `id` = ?");	//this is the user
		$stmt->bind_param("s", $user);
		$stmt->execute();
		$stmt->bind_result($mychannels);
		$stmt->fetch();
		$stmt->close();

		if(in_array($channel_id, explode(",", $mychannels)))	//if already following that user, we need to unfollow i.e. remove from list
		{
			$users = str_replace($user.",", "", $users);
			$mychannels = str_replace($channel_id.",", "", $mychannels);

			$users_int = sizeof(explode(",", $users))-1;

			$stmt = $conn->prepare("UPDATE `channels` SET `users` = ?, `users_int` = ? WHERE `url` = ?");
			$stmt->bind_param("sis", $users, $users_int, $channel);
			$stmt->execute();
			$stmt->close();

			$stmt = $conn->prepare("UPDATE `users` SET `channels` = ? WHERE `id` = ?");
			$stmt->bind_param("ss", $mychannels, $user);
			$stmt->execute();
			$stmt->close();

			return("left");

		}

		elseif (!in_array($user, explode(",", $users)))
		{
			$users = $users.$user.",";
			$mychannels = $mychannels.$channel_id.",";

			$users_int = sizeof(explode(",", $users))-1;

			$stmt = $conn->prepare("UPDATE `channels` SET `users` = ?, `users_int` = ? WHERE `url` = ?");
			$stmt->bind_param("sis", $users, $users_int, $channel);
			$stmt->execute();
			$stmt->close();

			$stmt = $conn->prepare("UPDATE `users` SET `channels` = ? WHERE `id` = ?");
			$stmt->bind_param("ss", $mychannels, $user);
			$stmt->execute();
			$stmt->close();

			return("joined");
		}

		else
		{
			$this->error="Issue resolving following array";
			return(false);
		}
	}






}

?>
