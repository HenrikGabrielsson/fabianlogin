<?php
	require_once("loginModel.php");
	require_once("loginView.php");
	class LoginController {
		private $model;
		private $view;
		
		public function __construct() {
			$this->model = new LoginModel();
			$this->view = new LoginView($this->model);
			
			// Om användaren försöker logga in och inte redan är inloggad så kör vi doLogin().
			if(($this->view->loginWithSavedCredentials() || $this->view->loginAttempted()) && !$this->model->userIsLoggedIn()) {
				$this->doLogin();
			}
			
			// Om användaren vill logga ut och är inloggad så kör vi doLogout().
			if($this->view->logoutRequest() && $this->model->userIsLoggedIn()) {
				$this->model->doLogout();	// Hanterar utloggningen i systemet.
				$this->view->doLogout();	// Genererar eventuella ut-meddelanden till användaren.
			}
			
			//Om användaren vill registrera sig och har skickat formuläret.
			if($this->view->registerAttempted())
			{
				$this->doRegister();			
			}
			
			$this->doStuff();
		}
		
		// Sämsta namnet på en metod nånsin. Förlåt.
		public function doStuff() {
			$this->view->showHTML();	// Säger till view att trycka ut färdig html till användaren.
		}
		
		public function doLogin() {
			// $username och $password sätts per default till det som användaren har angett. Om det finns sparade kakor så används uppgifterna i dem istället.
			$username = $this->view->suppliedUsername();
			$password = $this->view->suppliedPassword();
			
			if($this->view->loginWithSavedCredentials()) {
				$username = $this->view->savedUsername();
				$password = $this->view->savedPassword();
			}
			
			// LoginModel->login() kastar undantag om autentiseringen misslyckas, därav try - catch.
			try {
				// Om autentisering lyckas så säger vi till vyn att visa ett glatt meddelande!
				$loginResult = $this->model->login($username, $password, $this->view->saveCredentials(), $this->view->loginWithSavedCredentials());
				$this->view->loginSuccess($loginResult);
			}
			// Om något går fel i autentiseringen så kastas ett undantag. Detta presenteras sedan i view.
			catch(Exception $e) {
				$this->view->loginError($e->getMessage());
			}
		}
		
		//försök registrera ny användare
		public function doRegister()
		{
			$username = $this->view->regFormName();
			$password1 = $this->view->regFormPassword1();
			$password2 = $this->view->regFormPassword2();
			
			//försöker att registrera användare.
			$registerSuccess = $this->model->attemptRegister($username, $password1, $password2);
			
			//om registrering fungerande.
			if($registerSuccess)
			{
				$this->view->registerSuccess();
			}
			//om det gick fel.
			else 
			{
				$this->view->registerError($this->model->getRegErrors());
			}
			
		}
	
	}
?>