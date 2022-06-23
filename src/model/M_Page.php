<?php
	class M_Page {

		/**
		 * Загрузка всех товаров каталога.
		 * Выдаёт 9 товаров.
		 * В дальнейшем планиру сделать либо "подгрузку" товаров, либо пагинацию
		 */
		public function getCatalog(): array {
			try {
				//думаю, что можно было бы сделать намного проще, но не удавалось получить нужный результат в запросе sql, поэтому пришлось использовать
				//GROUP_CONCAT() и далее совершать эти ужасные операции с массивами в convertAnArray($data)
				//статусы товара: 1 - В наличии, 2 - Нет на складе (не отображается в каталоге покупателям)
				$sql = "SELECT g.id_good, id_category,  name_good, price, description,  GROUP_CONCAT(size_title) AS sizes, GROUP_CONCAT(color_title) AS colors,
       						(SELECT path FROM photo_goods pg WHERE pg.id_good = g.id_good AND SUBSTR(pg.path, -6, 6) LIKE '-1.jpg' OR SUBSTR(pg.path, -7, 7) LIKE '-1.jpeg') AS path
						FROM goods_sizes_colors_brands gscb
						LEFT JOIN goods g USING (id_good)
						LEFT JOIN sizes_of_goods sog USING (id_size)
						LEFT JOIN colors_of_goods cog USING (id_color) 
						WHERE g.status = 1 
						GROUP BY g.id_good
						LIMIT 9 ";

				$data = DB::select($sql);
				//Преобразуем поля размеров из цветов из вида S,M,S,L,XL,M,M в вид S,M,L,XL
				$data = $this->convertAnArray($data);
			}
			catch (PDOException $e) {
				die('Ошибка при запросе каталога: ' . $e->getMessage());
			}

			return $data;
		}


		/**
		 * Загрузка "популярных" товаров на главной странице.
		 * Товары сортируются по просмотрам (views) и выдаются 6 самых популярных
		 */
		public function showPopularProducts(): array {
			try {
				$sql = "SELECT g.id_good, id_category,  name_good, price, description,  GROUP_CONCAT(size_title) AS sizes, GROUP_CONCAT(color_title) AS colors,
       						(SELECT path FROM photo_goods pg WHERE pg.id_good = g.id_good AND SUBSTR(pg.path, -6, 6) LIKE '-1.jpg' OR SUBSTR(pg.path, -7, 7) LIKE '-1.jpeg') AS path
						FROM goods_sizes_colors_brands gscb
						LEFT JOIN goods g USING (id_good)
						LEFT JOIN sizes_of_goods sog USING (id_size)
						LEFT JOIN colors_of_goods cog USING (id_color) 
						WHERE g.status = 1 
						GROUP BY g.id_good
						ORDER BY g.views DESC
						LIMIT 6 ";

				$data = DB::select($sql);
				$data = $this->convertAnArray($data);
			}
			catch (PDOException $e) {
				die('Ошибка при получении товаров раздела "Популярные": ' . $e->getMessage());
			}

			return $data;
		}


		/**
		 * Функция выдаёт все необходимые данные для страницы товара
		 * (загружает инфо о товаре, обновляет количество просмотров товара в БД, получает фото все фото товара для "карусели")
		 */
		public  function showGood(): array
		{
			try {
				$sql = "SELECT g.id_good, g.id_category, name_good, price, brand, description, category_title, title_major_category, id_major_category, views, GROUP_CONCAT(size_title) AS sizes, GROUP_CONCAT(color_title) AS colors
						FROM goods_sizes_colors_brands gscb
						LEFT JOIN goods g USING (id_good)
						LEFT JOIN categories c USING (id_category)
						LEFT JOIN major_categories mc ON c.id_major_category  = mc.id_category
						LEFT JOIN sizes_of_goods sog USING (id_size)
						LEFT JOIN colors_of_goods cog USING (id_color) 
						WHERE g.id_good = ?	AND g.status = 1 					
						GROUP BY g.id_good";

				$data = DB::select($sql, [$_GET['id']]);

				$good = $this->convertAnArray($data);
				// просто убрал излишнюю вложенность массивов из Array([0] =>Array([good_id] => ...,)) в Array([good_id] => ...,)
				$good = $good[0];
				$view = ++$good['views'];

				//обновляем в БД количество просмотров для данного товара
				$sql = "UPDATE goods SET views = '$view' WHERE id_good = ?";
				DB::update($sql, [$_GET['id']]);

				//подгружаем 3 товара из раздела "Похожие товары"
				$similarGoods = $this->showSimilarProducts($good['id_category']);

				//получаю все фотографии данного товара для "карусели"
				$sql = "SELECT path FROM photo_goods WHERE id_good = ?";
				$res = DB::select($sql, [$_GET['id']]);

				$photos = [];
				foreach ($res as $photo) {
					$photos[] = $photo['path'];
				}

			}
			catch (PDOException $e) {
				die('Ошибка при загрузке/обновлении товара: ' . $e->getMessage());
			}

			//требуется для "карусели"
			$good['countPhotos'] = count($photos);

			return ['good' => $good,'photos' => $photos, 'similarGoods' => $similarGoods ];
		}


		/**
		 * Загружает товары нужной категории (мужчинам, женщинам или рубашки, аксессуары)
		 */
		public function showCategoryProducts(): array {
			try {
				$args =[];

				if ($_GET['id_category']) { // когда нужно получить товары конкретной категории (футболки, кофты, рюкзаки...)

					$sql = "SELECT g.id_good, id_category, name_good, price, description,  GROUP_CONCAT(size_title) AS sizes, GROUP_CONCAT(color_title) AS colors,
       						(SELECT path FROM photo_goods pg WHERE pg.id_good = g.id_good AND SUBSTR(pg.path, -6, 6) LIKE '-1.jpg' OR SUBSTR(pg.path, -7, 7) LIKE '-1.jpeg') AS path
						FROM goods_sizes_colors_brands gscb
						LEFT JOIN goods g USING (id_good)
						LEFT JOIN sizes_of_goods sog USING (id_size)
						LEFT JOIN colors_of_goods cog USING (id_color) 
						WHERE g.id_category = ? AND g.status = 1 
						GROUP BY g.id_good
						LIMIT 9 ";

					$args[] = $_GET['id_category'];
				}
				else { // когда нужны товары главной категории (мужчинам, женщинам, детям...)
					if ($_GET['category'] === 'Аксессуары') {

						$sql = "SELECT views, g.id_good, id_category, name_good, price, description,  GROUP_CONCAT(size_title) AS sizes, GROUP_CONCAT(color_title) AS colors,
       						(SELECT path FROM photo_goods pg WHERE pg.id_good = g.id_good AND SUBSTR(pg.path, -6, 6) LIKE '-1.jpg' OR SUBSTR(pg.path, -7, 7) LIKE '-1.jpeg') AS path
						FROM goods_sizes_colors_brands gscb
						LEFT JOIN goods g USING (id_good)
						LEFT JOIN categories c USING (id_category)   
						LEFT JOIN sizes_of_goods sog USING (id_size)
						LEFT JOIN colors_of_goods cog USING (id_color) 
						WHERE c.category_title = ? AND g.status = 1 
						GROUP BY g.id_good 
						ORDER BY g.views DESC 
						LIMIT 9";

						$args[] = $_GET['category'];
					}
					elseif ($_GET['category'] === 'Женщинам' || $_GET['category'] === 'Мужчинам' || $_GET['category'] === 'Детям') {

						$sql = "SELECT g.id_good, id_category, name_good, price, description,  GROUP_CONCAT(size_title) AS sizes, GROUP_CONCAT(color_title) AS colors,
       						(SELECT path FROM photo_goods pg WHERE pg.id_good = g.id_good AND SUBSTR(pg.path, -6, 6) LIKE '-1.jpg' OR SUBSTR(pg.path, -7, 7) LIKE '-1.jpeg') AS path
						FROM goods_sizes_colors_brands gscb
						LEFT JOIN goods g USING (id_good)
						LEFT JOIN categories c USING (id_category)   
						LEFT JOIN sizes_of_goods sog USING (id_size)
						LEFT JOIN colors_of_goods cog USING (id_color) 
						WHERE c.id_major_category = ? AND g.status = 1 
						GROUP BY g.id_good 
						ORDER BY g.views DESC 
						LIMIT 9";

						$args[] = $_GET['majorCategory'];
					}
					else {
						die('Такой категории нет!');
					}
				}

				$data = DB::select($sql, $args);
				$data = $this->convertAnArray($data);
			}
			catch (PDOException $e) {
				die('Ошибка при запросе каталога: ' . $e->getMessage());
			}

			return $data;
		}


		/**
		 * Загружает товары категории "похожие" на странице товара
		 */
		public function showSimilarProducts( string $idCategory): array {
			try {
				$sql = "SELECT g.id_good, id_category,  name_good, price, description,  GROUP_CONCAT(size_title) AS sizes, GROUP_CONCAT(color_title) AS colors,
       						(SELECT path FROM photo_goods pg WHERE pg.id_good = g.id_good AND SUBSTR(pg.path, -6, 6) LIKE '-1.jpg' OR SUBSTR(pg.path, -7, 7) LIKE '-1.jpeg' OR SUBSTR(pg.path, -7, 7) LIKE '-1.jpeg') AS path
						FROM goods_sizes_colors_brands gscb
						LEFT JOIN goods g USING (id_good)
						LEFT JOIN sizes_of_goods sog USING (id_size)
						LEFT JOIN colors_of_goods cog USING (id_color) 
						WHERE g.id_category = ? AND g.id_good != ? AND g.status = 1 
						GROUP BY g.id_good
						ORDER BY g.views DESC
						LIMIT 3 ";


				$data = DB::select($sql, [$idCategory, $_GET['id']]);
				$data = $this->convertAnArray($data);
			}
			catch (PDOException $e) {
				die('Из БД не получены товары раздела "Похожие": ' . $e->getMessage());
			}

			return $data;
		}


		/**
		 * Когда выбираем цвет и размер товара, при выборе цвета
		 * срабатывает onchange и обновляет имеющиеся размеры товара
		 */
		public function updateSizesInSelect(): string {
			try {
				$sql = "SELECT  size_title AS sizes
						FROM goods_sizes_colors_brands gscb 
						INNER JOIN sizes_of_goods sog USING (id_size)
						INNER JOIN colors_of_goods cog USING (id_color)
						WHERE gscb.id_good = ? AND cog.color_title = ?";

				$result = DB::select($sql, [$_POST['idGood'], $_POST['color']]);
			}
			catch (PDOException $e) {
				die('Не получилось обновить select с размерами: ' . $e->getMessage());
			}
			return json_encode($result);
		}


		/**
		 * Преобразует поля colors и sizes из вида 'M,L,M,L,M' в отсортированные строки с уникальными значениями 'L,M'
		 */
		private function convertAnArray( array $data): array {

			foreach ($data as $key => $value) {
				$colors = explode(',', $value['colors']);  // [sizes] => M,L,M,L,M  | разбиваем на массив
				$sizes = explode(',', $value['sizes']);  // [titles] => Белый,Серебряный,Фиолетовый,Белый,Чёрный//

				sort($colors,SORT_STRING);  // M,L,M,L,M => L,L,M,M,M
				sort($sizes, SORT_STRING);

				$finalColors = array_unique($colors); // L,L,M,M,M => L,M
				$finalSizes = array_unique($sizes);

				$data[$key]['colors'] = implode(',', $finalColors); // 'L,M' - чтобы потом добавить атрибутом data-sizes='L,M' в карточку товара
				$data[$key]['sizes'] = implode(',', $finalSizes);
			}

			return $data;
		}
	}