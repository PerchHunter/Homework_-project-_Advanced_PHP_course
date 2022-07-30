<?php

class C_Page extends C_Base
{
	private M_Page $page;

	public function __construct()
	{
		$this->page = new M_Page;
	}

	/**
	 * Главная страница
	 */
	public function action_index(): void
    {
        $this->title .= ' | Главная';
        $this->content = $this->page->showPopularProducts();
        $this->template = 'index/index.twig';
	}

	/**
	 * Страница каталог
	 */
	public function action_getCatalog(): void
	{
		$data = $this->page->getCatalog();

		$this->title .= ' | Каталог';
		$this->breadcrumb = ['title' => 'Каталог'];
		$this->pagination = $data['pagination'];
		$this->content = $data['products'];
		$this->template = 'catalog/catalog.twig';
	}

	/**
	 * Страница каталог, но когда мы смотрим товары какой-то категории
	 */
	public function action_showCategoryProducts(): void
	{
		$data = $this->page->showCategoryProducts();

		$this->title .= ' | Каталог';
		$this->breadcrumb = ['title' => 'Каталог'];
		$this->pagination = $data['pagination'];
		$this->content = $data['products'];
		$this->message = count($this->content) ? '' : 'Товары этой категории скоро будут добавлены :)';
		$this->template = 'catalog/catalog.twig';
	}

	/**
	 * Страница товара
	 */
	public function action_showGood(): void
	{
		//если существует id товара
		if ((int)strip_tags($_GET['id'])) {
			$this->content = $this->page->showGood();
		}

		// Интернет - магазин | название товара
		$this->title .= ' | ' . $this->content['good']['name_good'];
		$this->breadcrumb = ['title' => $this->content['good']['title_major_category']];
		$this->template = 'good/good.twig';
	}
}
