<?php
	require_once("Helpers.php");
    class LoginModel {
		private $usernameLocation = "username";				// Nyckel. Används i $_SESSION för att lagra en inloggad användares användarnamn.
		private $useragentLocation = "useragent";			// Nyckel. Används i $_SESSION för att lagra en inloggad användares useragent.
		private $clientAddressLocation = "clientAddress";	// Nyckel. Används i $_SESSION för att lagra en inloggad användares ip-adress.
		
		private $usersFilePath = "Users.txt";						// I denna fil finns alla användare och deras (krypterade) lösenord sparade.
		private $savedCredentialsFilePath = "SavedCredentials.txt";	// I denna fil lagras användare och temporära lösenord när användaren vill fortsätta vara inloggad.
		private $helpers;	// Hjälp-funktioner.
		
		public function __construct() {
			$this->helpers = new Helpers();
		}
		
		// Om inloggningen lyckas så returneras ett lyckat state, annars kastas ett undantag.
		public function login($username, $password, $saveCredentials = FALSE, $loginWithSavedCredentials = FALSE) {
			
			// Om det finns sparade kakor med korrekt inloggningsuppgifter så används dem i första hand. 
			if($loginWithSavedCredentials) {
				if($this->Authenticate($username, $password, $this->savedCredentialsFilePath)) {
					// Vi lagrar lite uppgifter om användaren.
					$_SESSION[$this->usernameLocation] = $username;
					$_SESSION[$this->useragentLocation] = $_SERVER['HTTP_USER_AGENT'];
					$_SESSION[$this->clientAddressLocation] = $_SERVER['REMOTE_ADDR'];
					
					return "CookieLoginSuccess";	// En status som skickas vidare till view för att berätta för användaren att vi loggade in med sparad info.
				}
				throw new Exception("BadCookieCredentials");
			}
			
			// Validering, om något går fel så kastar vi ett undantag som fångas i controllern och presenteras i view.
			if(!isset($username) || $username == "") {
				throw new Exception("EmptyUsername");
			}
			
			if(!isset($password) || $password == "") {
				throw new Exception("EmptyPassword");
			}
			
			$password = md5($password);
			
			// Om inloggningsuppgifterna stämmer så är användaren autentiserad. Sessions-arrayet lagrar användarnamnet.
			if($this->Authenticate($username, $password, $this->usersFilePath)){
				$_SESSION[$this->usernameLocation] = $username;
				$_SESSION[$this->useragentLocation] = $_SERVER['HTTP_USER_AGENT'];
				$_SESSION[$this->clientAddressLocation] = $_SERVER['REMOTE_ADDR'];
				
				if($saveCredentials) {
					return "SaveCredentialsLoginSuccess";	//En status som skickas vidare till användaren för att visa att vi sparade inloggningsuppgifterna.
				}
				return "LoginSuccess";	// En status som skickas vidare till användaren för att visa att inloggningen lyckades.
			} else {
				throw new Exception("InvalidCredentials");
			}
			
			throw new Exception("Unexpected");
		}
		
		// Returnerar true om ett användarnamn är lagrat i sessions-arrayet. 
		public function userIsLoggedIn() {
			if(isset($_SESSION[$this->usernameLocation]) && isset($_SESSION[$this->useragentLocation]) && isset($_SESSION[$this->clientAddressLocation]) 
			&& $_SESSION[$this->useragentLocation] == $_SERVER['HTTP_USER_AGENT'] && $_SESSION[$this->clientAddressLocation] == $_SERVER['REMOTE_ADDR']) {
				return TRUE;
			}
		}
		
		// Returnerar användarnamn på inloggad användare.
		public function currentUser() {
			return $_SESSION[$this->usernameLocation];
		}
		
		// Körs om användaren vill logga ut.
		public function doLogout() {
			session_unset();	// Tömmer sessions-arrayet.
		}
		
		// Autentiseringsfunktion. $source är den fil där valida inloggningsuppgifter ligger lagrade.
		private function Authenticate($username, $password, $source) {
			$users = file($source);
			foreach($users as $user) {
				$credentials = explode(";", $user);
				if($username == $credentials[0] && $password == $credentials[1]) {
					// Om det inte finns en lagrad tid i lösenordsfilen (för att t.ex. markera en cookies utgångsdatum) eller om den lagrade tiden
					// är senare den nuvarande tiden så är allt okej och användaren loggas in.
					if(!isset($credentials[2]) || time() < $credentials[2]){
						return TRUE;
					}
					
				}
			}
			return FALSE;
		}
		
		// Den här funktionen lagrar de sparade inloggningsuppgifterna på servern. Detta ger oss flera fördelar:
		// 1. Istället för att lagra användarens krypterade lösenord i en kaka hos klienten så kan vi lagra ett tillfälligt lösenord istället.
		// Detta bidrar starkt till säkerheten och tack vare att uppgifterna finns lagrade på servern så fungerar inloggning felfritt.
		// 2. Vi kan sätta en tidsgräns på klientens cookies och använda det tillfälliga lösenordet för att bekräfta kakans identitet.
		// Eftersom varje tillfälligt lösenord är unikt så kan samma användare logga in från flera olika maskiner utan att det påverkar säkerheten. 
		public function saveCredentialsOnServer($username, $password, $expirationTime) {
			$savedCredentials = $username . ";" . $password . ";" . $expirationTime;
			$this->helpers->WriteLineToFile($this->savedCredentialsFilePath, $savedCredentials);
		}
    }
?>