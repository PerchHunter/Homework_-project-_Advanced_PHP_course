<?php
	use Twig\Environment;
	use Twig\Loader\FilesystemLoader;

// Базовый контроллер сайта.

	class C_Base extends C_Controller
	{
		protected string $title;		   // текст для тега title раздела head
		protected array $breadcrumb;       // для хлебных крошек
		protected string $message;         // сообщения пользователю (например, что аккаунт с такими логином и почтой уже существует )
		protected string $error;           // сообщения ошибок
		protected $cartForAuth;            // кол-во товаров в корзине для зарегистрированного пользователя (int|null)
		protected array $content;		   // содержание страницы
		protected string $template;        //шаблон, который загружаем


		protected function before(): void
		{
			$this->title = Config::get('siteName');
			$this->breadcrumb = [];
			$this->message = '';
			$this->error = '';
			$this->cartForAuth = $_COOKIE['cartForAuth'];
			$this->content = [];
			$this->template = '';
		}

		/**
		 * Вывод шаблона Twig
		 * @return void
		 * @throws \Twig\Error\LoaderError
		 * @throws \Twig\Error\RuntimeError
		 * @throws \Twig\Error\SyntaxError
		 */
		public function render(): void
		{
			$vars = array('title' => $this->title, 'breadcrumb' => $this->breadcrumb, 'message' => $this->message, 'content' => $this->content, 'error' => $this->error, 'cartForAuth' => $this->cartForAuth);
			$loader = new FilesystemLoader(Config::get('path_views'));
			$twig = new Environment($loader);
			$twig->addGlobal('cookie', $_COOKIE); //установил глобальные переменные. Теперь куки, гет-параметры и пар-ры сессии доступны в любом месте шаблона
			$twig->addGlobal('get', $_GET);
			$twig->addGlobal('session', $_SESSION);
			// print_r($_SESSION);
//			print_r($this->content); // посмотреть что у нас выдаёт сервер
			echo  $twig->render($this->template, $vars);
		}
	}
