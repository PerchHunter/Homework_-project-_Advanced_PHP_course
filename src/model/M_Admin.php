<?php
	class M_Admin {

		// получение списка заказов для зарегистрированных и незарегистрированных покупателей
		public function getOrders(): array {
			try {
				$sql = '';
				//для таблицы с заказами зарег-х покупателей
				if ($_GET['users'] === 'authUsers') $sql = "SELECT * FROM orders_auth_users ORDER BY id_order DESC LIMIT 10";
				//для таблицы с заказами незарег-х покупателей
				elseif ($_GET['users'] === 'unauthUsers') $sql = "SELECT * FROM orders_unauth_users ORDER BY id_order DESC LIMIT 10";

				$orders = DB::select($sql);
			}
			catch (PDOException $e) {
				die('Не удалось получить заказы авторизованных пользователей из таблицы. Ошибка:  ' .  $e->getMessage());
			}

			return $orders;
		}


		//изменение статусов заказов для покупателей
		public function setChangeOfOrders(): string {
			$queries = [];
			$sql ='';

			//благодаря обработчикам onchange у инпутов в $_POST попадают только те заказы, статусы которых меняли
			foreach ($_POST as $key => $value) {
				//для таблицы с заказами зарег-х покупателей
				if ($_GET['users'] === 'authUsers') $sql = "UPDATE orders_auth_users SET id_order_status = ? WHERE id_order = ?";
				//для таблицы с заказами незарег-х покупателей
				elseif ($_GET['users'] === 'unauthUsers') $sql = "UPDATE orders_unauth_users SET id_order_status = ? WHERE id_order = ?";

				$queries[] = [$sql, [$value, $key]];
			}

			$result = DB::transaction($queries);

			return $result ? 'Статусы заказов успешно обновлены' : 'Данные не обновлены. Ошибка.';
		}


		// Генерирует таблицу товаров для редактирования их в админке
		public function getCatalog(): array {
			try {
				$sqlGoods = "SELECT g.id_good, name_good, price, brand, description, id_category, status, views, GROUP_CONCAT(path) AS photos, GROUP_CONCAT(size_title) AS sizes, GROUP_CONCAT(color_title) AS colors
						FROM goods_sizes_colors_brands gscb
						LEFT JOIN goods g USING (id_good)
						LEFT JOIN photo_goods pg USING (id_good)
						LEFT JOIN sizes_of_goods sog USING (id_size)
						LEFT JOIN colors_of_goods cog USING (id_color)
						GROUP BY g.id_good
						ORDER BY g.id_good DESC
						LIMIT 20";

				$sqlMajCat = "SELECT * FROM major_categories ORDER BY id_category";
				$sqlCategories = "SELECT * FROM categories ORDER BY id_category";
				$sqlAllId = "SELECT id_good FROM goods ORDER BY id_good DESC";
				$sqlColors = "SELECT * FROM colors_of_goods ORDER BY id_color";
				$sqlSizes = "SELECT * FROM sizes_of_goods ORDER BY id_size";

				$goods = DB::select($sqlGoods);
				$majorCategories = DB::select($sqlMajCat);
				$categories = DB::select($sqlCategories);
				$allIdGoods = DB::select($sqlAllId);
				$allColors = DB::select($sqlColors);
				$allSizes = DB::select($sqlSizes);


				//не получилось сделать запрос чтобы получить что-то подобное и избежать этих операций
				foreach ($goods as $key => $good) {
					$colors = explode(',', $good['colors']);  // [colors] => Белый,Серебряный,Фиолетовый,Белый,Белый
					$sizes = explode(',', $good['sizes']);  // [sizes] => M,L,M,L,M  | разбиваем на массив
					$photos = explode(',', $good['photos']);

					sort($colors,SORT_STRING);
					sort($sizes, SORT_STRING);// M,L,M,L,M => L,L,M,M,M
					sort($photos, SORT_STRING);

					$finalColors = array_unique($colors);
					$finalSizes = array_unique($sizes);// L,L,M,M,M => L,M
					$finalPhotos = array_unique($photos);

					$goods[$key]['colors'] = implode(',', $finalColors);
					$goods[$key]['sizes'] = implode(',', $finalSizes);// 'L,M' - чтобы потом добавить атрибутом data-sizes='L,M' в карточку товара
					$goods[$key]['photos'] = implode(',', $finalPhotos);
				}
			}
			catch (PDOException $e) {
				die('Не удалось получить заказы авторизованных пользователей из таблицы. Ошибка:  ' .  $e->getMessage());
			}

			return ['goods' => $goods, 'majorCategories' => $majorCategories, 'categories' => $categories, 'allIdGoods' => $allIdGoods, 'allColors' => $allColors, 'allSizes' => $allSizes];
		}


		// Изменяем в админке информацию об уже имеющихся товарах
		public function saveChangeOfGood(): string {
			try {
				$sql = "UPDATE goods SET name_good = ?, price = ?, brand = ?, description = ?, id_category = ?, status = ?, views = ? WHERE id_good = ?";
				$args = [$_POST['name'], $_POST['price'], $_POST['brand'], $_POST['description'], $_POST['idCategory'], $_POST['status'], $_POST['views'], $_POST['id']];

				DB::update($sql, $args);

				return 'Готово!';
			}
			catch (PDOException $e) {
				die('Информация о товаре не обновлена. Ошибка: ' . $e->getMessage());
			}
		}


		// добавляем новый товар в каталог
		public function addNewGoodInCatalog(): string {
			try {
				$sqlAddGoods = "INSERT INTO goods(name_good, price, brand, description, id_category, status, views) VALUES (?,?,?,?,?,?,?)";
				$argsAddGoods = [$_POST['name_good'], $_POST['price'], $_POST['brand'], $_POST['description'], $_POST['category'], $_POST['status'], $_POST['views']];

				$IDnewGood = DB::insert($sqlAddGoods, $argsAddGoods);

				// если новый товар успешно добавлен в БД, загружаем фотки
				if ($IDnewGood) {

					$checkSmPh = true;
					$checkBigPh = true;
					$sqlAddPhotos = "INSERT INTO photo_goods(path, id_good) VALUES ";

					// Задумывал что для товара будет одинаковое кол-во больших и мал. фото с одинаковыми названиями, но лежать будут в разных папках
					// Загружаем маленькие фото
					for ($i = 0, $j = 1; $i < count($_FILES['smallPhoto']['name']); $i++, $j++) {
						// сформировал имя файлу в формат 'id товара-№ фото.расширение'
						$extension = explode('.', $_FILES['smallPhoto']['name'][$i]);
						$extension = $extension[count($extension) - 1];
						$newNamePhoto = $IDnewGood . '-' . $j . '.' . $extension;

						// путь от index.php до папки с файлами
						$pathSmall = "./images/photoGoods/small/$newNamePhoto";

						if (!move_uploaded_file($_FILES['smallPhoto']['tmp_name'][$i], $pathSmall)) {
							$checkSmPh = false;
							break;
						}
						else {
							// Загружаем большую фото с таким же названием, если маленькая загружена
							$pathBig = "./images/photoGoods/big/$newNamePhoto";

							if (!move_uploaded_file($_FILES['bigPhoto']['tmp_name'][$i], $pathBig)) {
								$checkBigPh = false;
								break;
							}

							//если обе фотки успешно загружены, значение в SQL- запрос
							$sqlAddPhotos .= "('$newNamePhoto', '$IDnewGood'),";
						}
					}

					// если и большие и маленькие фото успешно загрузились, добавляем записи в таблицу с фотками photo_goods
					if ($checkSmPh && $checkBigPh) {
						$sqlAddPhotos = rtrim($sqlAddPhotos, ',');

						$resultAddPhotos = DB::insert($sqlAddPhotos);

						if ($resultAddPhotos) return 'Товар и фотографии успешно добавлены!';
					}
				}
			}
			catch (PDOException $e) {
				return 'Ошибка: ' . $e->getMessage();
			}
		}

		// При добавлении нового товара в каталог, когда админ выбирает главную категорию
		// в соседний select подтягиваются подкатегории для этой категории.
		public function updateCategoryWhenAddProduct(): string {
			try {
				$sql = "SELECT id_category, category_title, id_major_category FROM categories WHERE id_major_category = {$_POST['idMajCat']}";
				$data = DB::select($sql);

				return $data ? json_encode($data) : '';
			}
			catch (PDOException $e) {
				die('Категории товаров не обновлены. Ошибка: ' . $e->getMessage());
			}
		}


		// Добавляем товару цвет и размер  товару в админке
		public function addColorSizeForGood(): string {
			try {
				$sql = "INSERT INTO goods_sizes_colors_brands(id_good, id_size, id_color) VALUES (?, ?, ?)";
				$args = [$_POST['idGood'], $_POST['size'], $_POST['color']];

				if (DB::insert($sql, $args)) return 'Готово!';
			}
			catch (PDOException $e) {
				die('Не добавлено. Ошибка: ' . $e->getMessage());
			}
		}
	}