<?php
	class C_User extends C_Base
	{
		private M_User $user;

		public function __construct()
		{
			$this->user = new M_User;
		}

		/**
		 * Страница регистрации
		 */
		public function action_registration(){
			$this->title .= ' | Регистрация';
			$this->breadcrumb = ['title' => 'Регистрация'];

			if ($this->isPost()) {
				$this->message = $this->user->registration();
			}

			$this->template = 'user/registration.twig';
		}

		/**
		 * Страница входа в систему
		 */
		public function action_auth() {
			$this->title .= ' | Авторизация';
			$this->breadcrumb = ['title' => 'Авторизация'];

			if ($this->isPost()) {
				$this->message = $this->user->auth();
			}

			$this->template = 'user/auth.twig';
		}

		/**
		 * Личный кабинет
		 */
		public function action_personalAccount() {
			$this->title = ' | Личный кабинет';
			$this->breadcrumb = ['title' => 'Личный кабинет'];
			$this->content = $this->user->personalAccount();
			$this->template = 'user/personalAccount.twig';
		}


	}
