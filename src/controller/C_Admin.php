<?php
	class C_Admin extends C_Base {

		private M_Admin $admin;

		public function __construct()
		{
			$this->admin = new M_Admin;
		}


		public function action_getOrders() {
			if ($_SESSION['role'] == '2') {
				$this->title .= ' | Заказы';

				if ($this->IsPost()) $this->message = $this->admin->setChangeOfOrders();
				$this->content = $this->admin->getOrders();

				$this->template = 'admin/orders.twig';
			}
		}

		public function action_changeCatalog() {
			if ($_SESSION['role'] == '2') {
				$this->title .= ' | Изменить каталог';

				if ($this->IsPost()) $this->message = $this->admin->addNewGoodInCatalog();
				$this->content = $this->admin->getCatalog();

				$this->template = 'admin/changeCatalog.twig';
			}
		}
	}