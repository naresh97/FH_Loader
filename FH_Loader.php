<?PHP
	define("FH_LOADER_LOGIN","https://www.campusoffice.fh-aachen.de/views/campus/search.asp");
	define("FH_LOADER_ILIAS_LOGIN","https://www.ili.fh-aachen.de/ilias.php?lang=de&client_id=elearning&cmd=post&cmdClass=ilstartupgui&cmdNode=ta&baseClass=ilStartUpGUI&rtoken=");
	define("FH_LOADER_ICAL",'https://www.campusoffice.fh-aachen.de/views/calendar/iCalExport.asp?startdt=01.09.2016&enddt=28.02.2017');
	define("FH_LOADER_ILI_MAIL","https://www.ili.fh-aachen.de/ilias.php?mobj_id=0&cmdClass=ilmailfoldergui&cmdNode=d9:d5&baseClass=ilMailGUI");
	
	class IliasMessage{
		public $subject;
		public $sender;
		public $dat;
		
		function __construct($s,$sen,$d)
		{
			$this->subject=$s;
			$this->sender=$sen;
			$this->dat=$d;
		}
	}
	
	class FH_Loader{
		private $user;
		private $pass;
		private $campusKey;
		private $ckFile;
		private $ckey;
		
		public function __construct($username,$password){
			$this->user=$username;
			$this->password=$password;
			$this->ckey = $this->getCampusKey($username,$password);
			$this->getIliasKey($username,$password);
		}
		private function getCampusKey($user,$pass)
		{
			$url = FH_LOADER_LOGIN;
			$data = array('u'=>$user, 'p'=>$pass);
			$options = array(
				'http' => array(
					'header' => 'Content-type: application/x-www-form-urlencoded\r\n',
					'method' => 'POST',
					'content' => http_build_query($data)
				)
			);
			$context = stream_context_create($options);
			$result = file_get_contents($url, false, $context);
			if(!$result){
				return false;
			}
			if(strpos($result,"Anmeldung fehlgeschlagen")!=FALSE){
				throw new Exception("Login failed");
			}
			$sC = $http_response_header[5];
			$sC = substr($sC, 12, strpos($sC, ";")-12);
			return $sC;
		}
		private function getIliasKey($user,$pass)
		{
			$url = FH_LOADER_ILIAS_LOGIN;
			$this->ckfile = tempnam("/tmp","CURLCOOKIE");
			$ch=curl_init(FH_LOADER_ILIAS_LOGIN);
			curl_setopt($ch,CURLOPT_COOKIEJAR,$this->ckfile);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch,CURLOPT_POST,1);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch,CURLOPT_POSTFIELDS,"username=$user&password=$pass");
			$output=curl_exec($ch);
		}
		public function loadIliasHTML($url)
		{
			$ch = curl_init ($url);
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $this->ckfile); 
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
			$output = curl_exec ($ch);
			return $output;
		}
		public function loadCampusHTML($url){
			$key=$this->ckey;
			$options = array(
				'http' => array(
					'header' => "Cookie: $key\r\n Content-type: application/x-www-form-urlencoded\r\n",
					'method' => 'GET'
				)
			);
			$context = stream_context_create($options);
			$result = file_get_contents($url, false, $context);
			return $result;
		}
		public function loadCal(){
			return $this->loadCampusHTML(FH_LOADER_ICAL);
		}
		public function loadMails(){
			$dom=new DOMDocument();
			@$dom->loadHTML($this->loadIliasHTML(FH_LOADER_ILI_MAIL));
			$table=$dom->getElementById('mail_folder_tbl_357260');
			
			$message=array();
			$items = $table->getElementsByTagName('tr');
			$i=0;
			foreach ($items as $node) {
				if($i==0){
					$i+=1;
					continue;
				}
				$c=0;
				$inf=array();
				foreach ($node->childNodes as $block){
					$newdoc = new DOMDocument();
					$clone = $block->cloneNode(TRUE);
					if($block->hasChildNodes()){
						$clone = $block->firstChild->cloneNode(TRUE);
					}
					$newdoc->appendChild($newdoc->importNode($clone,TRUE));
					
					if($c==4){
						
						array_push($inf,$newdoc->saveHTML());
					}
					if($c==6){
						array_push($inf,$block->nodeValue);
					}
					if($c==8){
						array_push($inf,$newdoc->saveHTML());
					}
					$c+=1;
				}
				array_push($message, new IliasMessage($inf[1],$inf[0],$inf[2]));
			}
			return $message;
		}
	
		function loadMailsJson(){
			return json_encode($this->loadMails());
		}
	}
	
	
	
?>