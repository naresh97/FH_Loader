<?PHP
	define("FH_LOADER_LOGIN","https://www.campusoffice.fh-aachen.de/views/campus/search.asp");
	define("FH_LOADER_ILIAS_LOGIN","https://www.ili.fh-aachen.de/ilias.php?lang=de&client_id=elearning&cmd=post&cmdClass=ilstartupgui&cmdNode=xk&baseClass=ilStartUpGUI&rtoken=");
	define("FH_LOADER_ICAL",'https://www.campusoffice.fh-aachen.de/views/calendar/iCalExport.asp?startdt=01.09.2018&enddt=28.02.2019%2023:59:59');
	define("FH_LOADER_ILI_MAIL","https://www.ili.fh-aachen.de/ilias.php?mobj_id=0&cmdClass=ilmailfoldergui&cmdNode=gd:g9&baseClass=ilMailGUI");
	define("FH_LOADER_ILI_GRUPPEN", "https://www.ili.fh-aachen.de/ilias.php?baseClass=ilPersonalDesktopGUI&cmd=jumpToMemberships");
	define("FH_LOADER_URI","https://www.ili.fh-aachen.de/");
	define("FH_LOADER_PERSONAL","https://www.ili.fh-aachen.de/ilias.php?cmdClass=ilpersonalprofilegui&cmdNode=ss:st&baseClass=ilPersonalDesktopGUI");
	class IliasMessage{
		public $subject;
		public $sender;
		public $dat;
		public $msg;
		
		function __construct($a,$b,$c,$d)
		{
			$this->subject=$b;
			$this->sender=$a;
			$this->dat=$d;
			$this->msg=$c;
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
			curl_setopt($ch,CURLOPT_POSTFIELDS,"username=$user&password=$pass&cmd[doStandardAuthentication]=Anmelden");
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
			
			$finder = new DomXPath($dom);
			$spaner = $finder->query("//*[./text()='Verschieben nach: Posteingang']");

			$specialCode = iterator_to_array($spaner)[0]->getAttribute("value");
			
			$table=$dom->getElementById("mail_folder_tbl_$specialCode");
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
						
						array_push($inf, $this->loadSpecificMail(FH_LOADER_URI . $clone->getAttribute("href")));
						
						
						
					}
					if($c==8){
						array_push($inf,$newdoc->saveHTML());
					}
					$c+=1;
				}
				array_push($message, new IliasMessage($inf[0],$inf[1],$inf[2],$inf[3]));
			}
			return $message;
		}
	
		function loadMailsJson(){
			return json_encode($this->loadMails());
		}
		
		function loadSpecificMail($url){
			$dom = new DOMDocument();
			@$dom->loadHTML($this->loadIliasHTML($url));
			$form = $dom->getElementById("form_");
			$div1 = iterator_to_array($form->getElementsByTagName("div"))[0];
			$msgStr = $div1->nodeValue;
			return $msgStr;
		}
		
		function loadRegisteredGroups(){
			$dom = new DOMDocument();
			@$dom->loadHTML($this->loadIliasHTML(FH_LOADER_ILI_GRUPPEN));
			$classname="il_ContainerItemTitle";
			$finder = new DomXPath($dom);
			$spaner = $finder->query("//*[contains(@class, '$classname')]");
			
			$groups=array();
			
			foreach($spaner as $node){
				if($node->tagName == "a"){
					$gcont=array();
					
					$gcont["name"]=$node->textContent;
					$gcont["url"]=$node->getAttribute("href");
					
					array_push($groups,$gcont);
				}
			}
			
			return $groups;
		}
		function loadRegisteredGroupsJson(){
			return json_encode($this->loadRegisteredGroups());
		}
		function loadPersonal(){
			$dom = new DOMDocument();
			@$dom->loadHTML($this->loadIliasHTML(FH_LOADER_PERSONAL));
			
			$profile=array();
			
			$profile["fname"]=$dom->getElementById("usr_firstname")->getAttribute("value");
			$profile["lname"]=$dom->getElementById("usr_lastname")->getAttribute("value");
			$profile["email"]=$dom->getElementById("usr_email")->getAttribute("value");
			$profile["matrik"]=$dom->getElementById("usr_matriculation")->getAttribute("value");
			
			return $profile;
		}
		function loadPersonalJson(){
			return json_encode($this->loadPersonal());
		}
	}
	
	
	
?>