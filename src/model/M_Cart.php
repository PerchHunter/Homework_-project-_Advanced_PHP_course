<?php
	class M_Cart {

		/**
		 * Выдаёт на странице корзины инфо о товарах, которые польз-ль добавил в корзину,
		 * как для зарег-й (если есть $_COOKIE['login']), так и незарег-й
		 */
		public function getCart(): array {
			$arrGoods = [];

			if ($_COOKIE['login']) {
				$sql = "SELECT id_good, name_good, b.price, category_title, color, size, quantity, path
						FROM goods g
						LEFT JOIN baskets b USING (id_good)
    					LEFT JOIN categories c USING (id_category)
    					LEFT JOIN photo_goods pg USING (id_good)
						WHERE g.id_good = b.id_good AND SUBSTR(pg.path, -6, 6) LIKE '-1.jpg' OR SUBSTR(pg.path, -7, 7) LIKE '-1.jpeg'";

			} else {
				//  кука 'cart' выглядит так: 'id/категория/размер/цвет/цена/количество' '...' '...'
				$goods = explode(' ', $_COOKIE['cart']);

				$strID = '';
				foreach($goods as $good) {
					$arrGood = explode('/', $good);
					array_push($arrGoods, ['id_good' => $arrGood[0], 'size' => $arrGood[2], 'color' => $arrGood[3], 'price' => $arrGood[4], 'quantity' => $arrGood[5]]);
					$strID .= $arrGood[0] . ', ';
				}

				$strID = rtrim($strID, ', '); // 13, 14, 17  айдишники товаров

				$sql = "SELECT id_good, name_good, category_title, path FROM goods g
    					INNER JOIN categories c USING (id_category)
    					INNER JOIN photo_goods pg USING (id_good)
						WHERE g.id_good IN ($strID) AND SUBSTR(pg.path, -6, 6) LIKE '-1.jpg' OR SUBSTR(pg.path, -7, 7) LIKE '-1.jpeg'";
			}

			try {
				$data = DB::select($sql);

				if (!$_COOKIE['login']) {
					// использую такую конструкцию потому что в запросе WHERE g.id_good IN (34,35,34,34) выдавал строки с id  34,35
					// не смог сделать правильный запрос к БД
					for ($i = 0; $i < count($arrGoods); $i++) {
						foreach ($data as $good) {
							if ($arrGoods[$i]['id_good'] == $good['id_good']) {
								$arrGoods[$i] = array_merge($arrGoods[$i],$good);
								break;
							}
						}
					}
					return $arrGoods;
				}

				return $data;
			}
			catch (PDOException $e) {
				die('Данные о корзине не получены. Ошибка: ' . $e->getMessage());
			}
		}

		/**
		 * Добавление товара в корзину зарег-ным пользователем
		 * Сначала проверяем, есть ли этот товар в корзине
		 * Если да, то к имеющемуся кол-ву добавляем еще кол-во
		 * Если нет, то добавляем товар в корзину
		 */
		public function addToCartForAuth(): bool {
			try {
				$sql = "SELECT size, color, quantity FROM baskets WHERE id_user = ? AND id_good = ?";
				$goods = DB::select($sql, [$_COOKIE['ID'], $_POST['idGood']]);
				$good = [];

				$match = false;
				// Проверяю, есть ли товар с таким цветом и размером.
				// Актуально если добавлено несколько одинаковых товаров, но разных цветов или размеров
				foreach($goods as $value) {
					if ($value['size'] ===  $_POST['size'] && $value['color'] === $_POST['color']) {
						$match = true;
						$good = $value;
					}
				}

				if ($match) { // если уже есть такой товар с таким же цветом и размером...
					$good['quantity'] += $_POST['quantity'];
					$sql = "UPDATE baskets SET quantity = ? WHERE id_user = ? AND id_good = ? AND color = ? AND size = ?";
					$result = DB::update($sql, [$good['quantity'], $_COOKIE['ID'], $_POST['idGood'], $good['color'], $good['size']]);
				}
				else {
					$sql = "SELECT * FROM goods WHERE id_good = ?";
					$good = DB::select($sql, [$_POST['idGood']])[0];

					$sql = "INSERT INTO baskets(id_good, id_user, size, color, price, quantity) VALUES (?, ?, ?, ?, ?, ?)";
					$result = DB::insert($sql, [$good['id_good'], $_COOKIE['ID'], $_POST['size'], $_POST['color'], $good['price'], $_POST['quantity']]);
				}

				if ($result) {
					$count = $_COOKIE['cartForAuth'] ?  $_COOKIE['cartForAuth'] + $_POST['quantity'] : $_POST['quantity'];
					setcookie('cartForAuth',$count,time()+3600*2*7,'/');

				}

				return (bool)$result;
			}
			catch (PDOException $e) {
				die('Ошибка при добавлении товара в корзину. Ошибка: ' . $e->getMessage());
			}
		}

		/**
		 * Когда поль-ль нажал кнопку "Оформить заказ"
		 */
		public function processOrder(): string {
			$name = (string)htmlspecialchars(strip_tags($_POST['name']));
			$surname = (string)htmlspecialchars(strip_tags($_POST['surname']));
			$email = (string)htmlspecialchars(strip_tags($_POST['email']));
			$info = (string)htmlspecialchars(strip_tags($_POST['AddInfo']));

			$order = $this->getCart();
			$args = [];

			try {
				if ($_COOKIE['login']) { // зарегистрированный пользователь
					$sql = "SELECT * FROM users WHERE user_login = ? AND id_user = ?";
					//Смотрим, заполнены ли поля адрес доставки, индекс, телефон в ЛК пользователя
					$user = DB::select($sql, [$_COOKIE['login'], $_COOKIE['ID']]);

					if (empty($user[0]['postal_address']) || empty($user[0]['postal_code']) || empty($user[0]['telephone'])) {
						return 'Перед совершением заказа заполните необходимые поля в личном кабинете';
					}

					$sql = "INSERT INTO orders_auth_users(id_user, additional_info, product_id, product_name, product_price, quantity, total_price, category, size, color) VALUES ";

					foreach($order as $good) {
						$fieldQuantity = $good['quantity']; // значение поля 'количество ' для данного товара
						$totalPrice = $good['price'] *  $fieldQuantity;
						$sql .= "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?),";
						array_push($args, $_COOKIE['ID'], $info, $good['id_good'], $good['name_good'], $good['price'], $fieldQuantity, $totalPrice, $good['category_title'], $good['size'], $good['color']);
					}


				} else { //незарегистрированный пользователь
					$sql = "INSERT INTO orders_unauth_users(user_name, user_surname, user_email, additional_info, product_id, product_name, product_price, quantity, total_price, category, size, color) VALUES ";

					foreach($order as $good) {
						$fieldQuantity = $good['quantity']; // значение поля 'количество ' для данного товара
						$totalPrice = $good['price'] *  $fieldQuantity;
						$sql .= "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?),";
						array_push($args, $name, $surname, $email, $info, $good['id_good'], $good['name_good'], $good['price'], $fieldQuantity, $totalPrice, $good['category_title'], $good['size'], $good['color']);
					}
				}

				$rtrimSql = rtrim($sql, ',');

				if (DB::insert($rtrimSql, $args)) {
					if ($_COOKIE['login']) {
						$sql = "DELETE FROM baskets WHERE id_user = ?";
						DB::delete($sql, [$_COOKIE['ID']]);

						setcookie('cartForAuth','0',time()+3600*24*7,'/');
						return "Ваш заказ принят. Спасибо за покупку";
					}
					else {
						setcookie('cart','',time()-1,'/');
						return "Спасибо за заказ. Мы свяжемся с вами в ближайшее время";
					}
				}
			}
			catch (PDOException $e) {
				die('Заказ не выполнен. Ошибка при обращении к базе данных: ' . $e->getMessage());
			}
		}

		/**
		 * Когда меняем значение поля "количество" в карточке товара в корзине
		 */
		function changeValueGoodInCart(): string {
			$idGood = $_POST['idGood'];
			$goodColor = $_POST['color'];
			$goodSize = $_POST['size'];
			$prevValue = $_POST['prevValue'];
			$curValue = (int)htmlspecialchars(strip_tags($_POST['curValue']));
			$totalCount = 0;
			$totalPrice = 0;


			if ($_COOKIE['login']) {
				try {
					$sql = "UPDATE baskets SET quantity = ? WHERE id_good = ? AND color = ? AND size = ?";

					if (DB::update($sql, [$curValue, $idGood, $goodColor, $goodSize])) {
						$sql = "SELECT SUM(quantity) AS 'totalCount', SUM(quantity * price) AS 'totalPrice' FROM baskets WHERE id_user = ?";
						$res = DB::select($sql, [$_COOKIE['ID']]);
						if ($res) {
							$totalCount = $res[0]['totalCount'];
							$totalPrice = $res[0]['totalPrice'];
							setcookie('cartForAuth',$totalCount,time()+3600*24*7,'/');
						}
					}
				}
				catch (PDOException $e) {
					die("Не получилось изменить количество товара в корзине. Ошибка: " . $e->getMessage());
				}

			} else {
				$arrGoods = explode(' ', $_COOKIE['cart']);
				$filterArr = [];

				foreach($arrGoods as $good) {
					$arrGood = explode('/', $good);

					if ($arrGood[0] == $idGood && $arrGood[3] == $goodColor && $arrGood[2] == $goodSize) {
						//заменили старое кол-во товара на новое при условии что id, цвет, размер совпадают в строке
						array_splice($arrGood,  - 1, 1, [$curValue]);
					}

					//находим общее кол-во товаров в корзине и цену за все товары
					$totalCount += $arrGood[5];
					$totalPrice += $arrGood[5] * $arrGood[4];
					$filterArr[] = implode('/', $arrGood);
				}

				$arrGoods = implode(' ', $filterArr);
				setcookie('cart',$arrGoods,time()+3600*24*7,'/');
			}
			return json_encode([$totalCount, $totalPrice]);
		}

		/**
		 * Удаление товара из корзины путём нажатия крестика на карточке товара в корзине
		 */
		public function deleteGood(): string {
			$id = $_POST['idGood'];
			$color = $_POST['color'];
			$size = $_POST['size'];

			$totalPrice = 0;
			$totalCount = 0;

			if ($_COOKIE['login']) {
				try {
					$sql = "DELETE FROM baskets WHERE id_good = ? AND color = ? AND size = ?";

					if (DB::delete($sql, [$id, $color, $size])) {
						$sql = "SELECT price, quantity FROM baskets WHERE id_user = ?";
						$goods = DB::select($sql, [$_COOKIE['ID']]);

						if ($goods) {
							foreach($goods as $good) {
								$totalPrice += $good['price'] * $good['quantity'];
								$totalCount += $good['quantity'];
							}
						}

						setcookie('cartForAuth',$totalCount,time()+3600*24*7,'/');
					}
				}
				catch (PDOException $e) {
					die('Не получилось удалить товар из корзины. Ошибка: ' . $e->getMessage());
				}
			}
			else {
				$arrGoods = explode(' ', $_COOKIE['cart']);
				$filterArrGoods = [];

				foreach($arrGoods as $good) {
					if (!preg_match("/^$id\/\d+\/$size\/$color\/\S+$/", $good)) {
						$arrGood = explode('/', $good);
						//находим цену за все товары
						$totalCount += $arrGood[5];
						$totalPrice += $arrGood[5] * $arrGood[4];
						$filterArrGoods[] = $good;
					}
				}

				$str = implode(' ', $filterArrGoods);
				setcookie('cart',$str,time()+3600*24*7,'/');
			}

			return json_encode([$totalCount, $totalPrice]);
		}

		/**
		 * Полная очистка корзины при нажатии на кнопку "Очистить корзину" (для зарег-го пользователя)
		 */
		public function clearCart(): bool {
			try {
				$sql = "DELETE FROM baskets WHERE id_user = {$_COOKIE['ID']}";
				if (DB::delete($sql)) {
					setcookie('cartForAuth',0,time()+3600*24*7,'/');
					return true;
				}
				else {
					return false;
				}
			}
			catch (PDOException $e) {
				die('Корзина не очищена. Ошибка при обращении к базе данных: ' . $e->getMessage());
			}
		}

	}