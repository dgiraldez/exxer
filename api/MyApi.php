<?php

class API
{
		protected $req = array();
    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    protected $method = '';
    /**
     * Property: endpoint
     * The Model requested in the URI. eg: /files
     */
    protected $endpoint = '';
    /**
     * Property: verb
     * An optional additional descriptor about the endpoint, used for things that can
     * not be handled by the basic methods. eg: /files/process
     */
    protected $verb = '';
    /**
     * Property: args
     * Any additional URI components after the endpoint and verb have been removed, in our
     * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
     * or /<endpoint>/<arg0>
     */
    protected $args = Array();
    /**
     * Property: file
     * Stores the input of the PUT request
     */
     protected $file = Null;

    /**
     * Constructor: __construct
     * Allow for CORS, assemble and pre-process the data
     */
    public function __construct($request) {
    		
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json; charset=utf8");
	
        $this->args = explode('/', rtrim($request, '/'));
            //echo $this->args; //  ?arg1=1&arg2=2...
            //echo $this->args[0];

        $this->endpoint = array_shift($this->args);
            //echo $this->endpoint; //equivalente a la tabla

        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $this->verb = array_shift($this->args);
                //echo $this->verb;

        }

        $this->method = $_SERVER['REQUEST_METHOD'];
            //echo $this->method; // GET, POST, PUT, DELETE

        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new Exception("Unexpected Header");
            }
        }

        switch($this->method) {
        case 'DELETE':
        case 'POST':
            $this->request = $this->_cleanInputs($_POST);
            break;
        case 'GET':
            $this->request = $this->_cleanInputs($_GET);
			//print_r($this->request);
            break;
        case 'PUT':
            $this->request = $this->_cleanInputs($_GET);
            $this->file = file_get_contents("php://input");
            break;
        default:
            $this->_response('Invalid Method', 405);
            break;
        }
    }
    
    public function processAPI() {
			$username = "monitor";
			$password = "monitor";
			$hostname = "localhost";

			$response=array();

			//connection to the database
			$dbhandle = mysql_connect($hostname, $username, $password) or die("Unable to connect to MySQL");
			mysql_set_charset('utf8');

			//select a database to work with
			$selected = mysql_select_db("capture_db",$dbhandle) or die("Could not select test");

      switch($this->method) {
        case 'DELETE':
			//return $this->_response($response,204,print_r($this->args[0]));
        	$query = "DELETE FROM ".$this->endpoint." WHERE id=".$this->args[0];
			$result = mysql_query($query) or die("Invalid query: [".$query."] ". mysql_error());
			mysql_close($dbhandle);
			return $this->_response($response,204);
			break;
        case 'POST': //insert
        	/*
        	 * INSERT INTO 
        	 * `input`(`id`, `description`, `min`, `max`, `unit`, `notify`, `enabled`, `alerted`, `created`)
        	 *  VALUES ([value-1],[value-2],[value-3],[value-4],[value-5],[value-6],[value-7],[value-8],[value-9])
        	 */
			$fields = json_decode($this->file,true);
			$and = 0;
			foreach ($fields as $key => $arg) {
				$keys = $keys.($and==0 ? $key : ",".$key);
				$values = $values.($and==0 ? $arg : ",".$arg);
				$and++;
			}
			$query = "INSERT INTO ".$this->endpoint." (".$keys.") VALUES (".$values.")";
			//return $this->_response(print_r($query),202);
			$result = mysql_query($query) or die("Invalid query: [".$query."] ". mysql_error());
			mysql_close($dbhandle);
			return $this->_response($response,201);
        	break;
        case 'GET':	//select
        	$n = count($this->request);
			$query = "SELECT * FROM ".$this->endpoint;
			if ($n == 1) {
				if (count($this->args)>0) {//viene con id
					$query = $query." WHERE id=".$this->args[0];
					$result = mysql_query($query) or die("Invalid query: " . mysql_error());
					$rows = array();
					while($row = mysql_fetch_assoc($result)) {
							$rows[] = $row;
					}
					if (count($rows) == 0) {
						$response = $rows; //return null object, pero da error, devuelvo array vacio
					} elseif (count($rows)>1) {
						$response = $rows; //return array, nunca deberia entrar aca
					} else {
						$response = $rows[0]; //return object
					}
				}
			} else {
				$query = $query." WHERE ";
				$and = 0;
	            foreach ($this->request as $key => $arg) {
					if ($key<>"request") {
						if ($and>0) {//mas de un parametro
							$query = $query." AND ";
						}
						if ($key=="filter"){//concateno directamente
							$query = $query." ".$arg;
						} else {								
							$pos = strpos($arg,'*');
							if ($pos === false) {//no hay wildcard
								$query = $query."`".$key."` = ".$arg;
							} else {//wildcard, reemplazar por %
								$query = $query."`".$key."` like '".str_replace("*","%",$arg)."'";
							}
						}
						$and = $and+1;
					}
				}
			}
			//echo $query."\n";
			//return $this->_response(print_r($query),202);
			
			$result = mysql_query($query) or die("Invalid query: " . mysql_error());
			$rows = array();
			while($row = mysql_fetch_assoc($result)) {
					$rows[] = $row;
			}
			if (count($rows) == 0) {
				$response = $rows; //return null object, pero da error, devuelvo array vacio
			} elseif (count($rows)>1) {
				$response = $rows; //return array
			} else {
				$response = $rows; //return object
			}
          	break;
        case 'PUT':	//update  
        	/*
        	 * UPDATE `input` SET `id`=[value-1],`description`=[value-2],`min`=[value-3],`max`=[value-4],
        	 * `unit`=[value-5],`notify`=[value-6],`enabled`=[value-7],`alerted`=[value-8],`created`=[value-9] WHERE 1
        	 */ 
			$query = "UPDATE ".$this->endpoint;
			$fields = json_decode($this->file,true);
			$and = 0;
			foreach ($fields as $key => $arg) {
				if ($and==0) {
					$query = $query." SET ";
					$and++;
				} else {
					$query = $query.", ";
				}
				$query = $query."`".$key."` = '".$arg."'";
			}
			$query = $query." WHERE id=".$this->args[0];
			//return $this->_response(print_r($query),202);
			$result = mysql_query($query) or die("Invalid query: [".$query."] ". mysql_error());
			$query = "SELECT * FROM ".$this->endpoint." WHERE id=".$this->args[0];
			$result = mysql_query($query) or die("Invalid query: " . mysql_error());
			$rows = array();
			while($row = mysql_fetch_assoc($result)) {
					$rows[] = $row;
			}
			$response = $rows[0]; //return object
			mysql_close($dbhandle);
			return $this->_response($response,202);
          	break;
        default:
          	break;
        }

		//close the connection
		mysql_close($dbhandle);

		return $this->_response($response);
    }

    private function _response($data, $status = 200, $info = "") {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status)." ".$info);
        
        if(function_exists('json_encode')) {
              if (strnatcmp(phpversion(), '5.4.0') >= 0) {
                      return json_encode($data, JSON_UNESCAPED_UNICODE);
              } else {
                      return json_encode($data);
              }
       }

    }

    private function _cleanInputs($data) {
        $clean_input = Array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->_cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }
        return $clean_input;
    }

    private function _requestStatus($code) {
        $status = array( 
            100 => 'Continue',   
            101 => 'Switching Protocols',   
            200 => 'OK', 
            201 => 'Created',   
            202 => 'Accepted',   
            203 => 'Non-Authoritative Information',   
            204 => 'No Content',   
            205 => 'Reset Content',   
            206 => 'Partial Content',   
            300 => 'Multiple Choices',   
            301 => 'Moved Permanently',   
            302 => 'Found',   
            303 => 'See Other',   
            304 => 'Not Modified',   
            305 => 'Use Proxy',   
            306 => '(Unused)',   
            307 => 'Temporary Redirect',   
            400 => 'Bad Request',   
            401 => 'Unauthorized',   
            402 => 'Payment Required',   
            403 => 'Forbidden',   
            404 => 'Not Found',   
            405 => 'Method Not Allowed',   
            406 => 'Not Acceptable',   
            407 => 'Proxy Authentication Required',   
            408 => 'Request Timeout',   
            409 => 'Conflict',   
            410 => 'Gone',   
            411 => 'Length Required',   
            412 => 'Precondition Failed',   
            413 => 'Request Entity Too Large',   
            414 => 'Request-URI Too Long',   
            415 => 'Unsupported Media Type',   
            416 => 'Requested Range Not Satisfiable',   
            417 => 'Expectation Failed',   
            500 => 'Internal Server Error',   
            501 => 'Not Implemented',   
            502 => 'Bad Gateway',   
            503 => 'Service Unavailable',   
            504 => 'Gateway Timeout',   
            505 => 'HTTP Version Not Supported'); 
        return ($status[$code])?$status[$code]:$status[500]; 
    }    
    
}


?>
