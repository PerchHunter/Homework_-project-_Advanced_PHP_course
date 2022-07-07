<?php
	class M_User {

		/**
		 * Регистрация пользователя
		 */
		public function registration(): string {

			foreach ($_POST as $key => $value)
			{
				$$key =(string)htmlspecialchars(strip_tags($value));
			}

			$password = strrev(md5($password)); //шифруем и переворачиваем пароль

			try {
				$sql = "INSERT INTO users(user_name, user_surname, gender, user_login, user_email, user_password, role) VALUES (?,?,?,?,?,?,?)";
				$args = [$name, $surname, $gender, $login, $email, $password, 1];

				if (DB::insert($sql, $args)) header('Location: index.php?c=user&act=auth');
			}
			catch (PDOException $e) {
				if(preg_match("/users\.user_email'$/", $e->getMessage())) {
					return "Пользователь с такой почтой уже существует";
				}
				elseif (preg_match("/users\.user_login'$/", $e->getMessage())) {
					return "Пользователь с таким логином уже существует";
				}
				else {
					die('Ошибка при регистрации: ' . $e->getMessage());
				}

			}
		}

		/**
		 * Авторизация пользователя
		 */
		public function auth(): string {
			$login = (string)htmlspecialchars(strip_tags($_POST['login']));
			$password = (string)htmlspecialchars(strip_tags($_POST['password']));
			$password = strrev(md5($password)); //шифруем, чтобы сравнить с хешем пароля в БД

			try {
				$sql = "SELECT id_user, role, SUM(quantity) AS alreadyInCart
						FROM users 
						LEFT JOIN baskets USING (id_user)
						WHERE user_login = ? AND user_password = ?";

				$result = DB::select($sql, [$login, $password]);

				$userID = $result[0]['id_user'];
				$userRole = $result[0]['role'];
				//товары в корзине на этом аккаунте
				$alreadyInCart = $result[0]['alreadyInCart'] ?? 0;

				if ($userID) {
					// админа не запоминаем. Админ работает только в рамках текущей сессии и каждый раз авторизуется
					if ($_POST['remember'] && $userRole != 2) {
						setcookie('ID',$userID,time()+3600*24*7,'/');
						setcookie('login',$login,time()+3600*24*7,'/');
						setcookie('cartForAuth',$alreadyInCart,time()+3600*24*7,'/');

						$_SESSION['role'] = '1';
					}
					else {
						setcookie('ID',$userID,0,'/');
						setcookie("login",$login,0,'/');
						setcookie('cartForAuth',$alreadyInCart,0,'/');

						$_SESSION['role'] = $userRole;
					}


					//если мы неавторизованные добавили что-то в корзину, а потом авторизовались, то эти товары переносим в БД
					if ($_COOKIE['cart']) {
						$arrGoods = explode(' ', $_COOKIE['cart']);
						$sql = "INSERT INTO baskets(id_user, id_good, size, color, price, quantity) VALUES ";
						$countInCart = 0;

						foreach($arrGoods as $good) {
							//кука 'cart' выглядит так: 'id/категория/размер/цвет/цена/количество' '...' '...'
							$arrGood = explode('/', $good);
							$sql .= "('$userID', '$arrGood[0]', '$arrGood[1]', '$arrGood[2]', '$arrGood[3]', '$arrGood[4]', '$arrGood[5]'),";
							$countInCart += $arrGood[5];
						}

						$sql = rtrim($sql, ',');
						$addInTableBasket = DB::insert($sql);

						if ($addInTableBasket){
							$countInCart += $alreadyInCart;

							($_POST['remember']) ? setcookie('cartForAuth',$countInCart,time()+3600*24*7,'/') : setcookie('cartForAuth',$countInCart,0,'/');
							setcookie('cart','',time()-1,'/');
						}
					}

					//обновляем последнюю активность пользователя
					$sql = "UPDATE users SET user_last_action = NOW() WHERE user_login = ? AND user_password = ?";

					if (DB::update($sql, [$login, $password])) header('Location: index.php');
				}

				return 'Учётная запись с таким логином и паролем не найдена';
			}
			catch (PDOException $e) {
				die('Авторизация не прошла.' . $e->getMessage());
			}
		}

		/**
		 * Подгружаем данные о пользователе, когда он заходит в личный кабинет
		 */
		public function personalAccount(): array {
			try {
				$sql = "SELECT user_name, user_surname, gender, user_login, user_email, postal_address, postal_code, telephone FROM users WHERE id_user = ?";
				return DB::select($sql, [$_COOKIE['ID']]);
			}
			catch (PDOException $e) {
				die('Ошибка при обращении к базе данных: ' . $e->getMessage());
			}

		}

		/**
		 * Когда пользователь меняет свои данные в личном кабинете
		 */
		public function saveChangeInfoOfAccount(): string {
			foreach ($_POST as $key => $value)
			{
				$$key =(string)htmlspecialchars(strip_tags($value));
			}

			try {
				$sql ='';
				$args = [];

				if (!$currentPassword || !$newPassword) { // если юзер пароль не изменял
					$sql = "UPDATE users SET user_name = ?, user_surname = ?, gender = ?, user_email = ?, postal_address = ?, postal_code =?, telephone =?, user_last_action = NOW() WHERE id_user = ?";
					$args = [$name, $surname, $gender, $email, $postalAddress, $postalCode, $telephone, $_COOKIE['ID']];
				}
				elseif ($currentPassword && $newPassword) {// если он решил изменить пароль
					$currentPassword = strrev(md5($currentPassword));
					$newPassword = strrev(md5($newPassword));
					$sql = "SELECT user_email FROM users WHERE user_login = ? AND user_password = ?"; // проверяем текущий пароль на корректность
					$user = DB::select($sql, [$_COOKIE['login'], $currentPassword]);

					if (!$user[0]['user_email']) return "Вы ввели неправильный текущий пароль";

					if ($user[0]['user_email'] != $email && $email) { // если пароль был корректен, получаем почту и, если почта из формы отличается от текущей, то проверяем не исп-ся ли она кем-то ещё
						$sql = "SELECT id_user FROM users WHERE user_email = ?";
						$user = DB::select($sql, [$email]);

						if ($user[0]['id_user'] && $user[0]['id_user'] != $_COOKIE['ID'])  return "Эта почта уже используется другим пользователем";
					}

					$sql = "UPDATE users SET user_name = ?, user_surname = ?, gender = ?, user_email = ?, user_password = ?, postal_address = ?, postal_code =?, telephone =?, user_last_action = NOW() WHERE  id_user = ?";
					$args = [$name, $surname, $gender, $email, $newPassword, $postalAddress, $postalCode, $telephone, $_COOKIE['ID']];
				}

				if (DB::update($sql, $args)) return "Информация вашего профиля успешно обновлена";
			}
			catch (PDOException $e) {
				die("Ошибка при изменении информации о пользователе: " . $e->getMessage());
			}
		}

		/**
		 * Выход из системы
		 */
		public function exitFromSystem(): void {
			$_SESSION['role'] = '0';

			setcookie('ID',' ',time()-1,'/');
			setcookie('login',' ',time()-1,'/');
			setcookie('cartForAuth',' ',time()-1,'/');
		}

		/**
		 * Подписка на новости в подвале сайта
		 */
		public  function subscribeToNews(): string {
			$email = (string)htmlspecialchars(strip_tags($_POST['email']));
			try {
				$sql = "INSERT INTO subscribers(email_subscriber) VALUES (?)";
				$result = DB::insert($sql, [$email]);

				if ($result) return 'Спасибо за подписку! Теперь вы будете узнавать новости первыми';
			}
			catch (PDOException $e) {
				return 'Вы уже подписаны на наши новости';
			}
		}

	}