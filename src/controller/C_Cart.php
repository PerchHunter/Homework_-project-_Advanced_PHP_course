<?php
class C_Cart extends C_Base {

    private M_Cart $cart;

    public function __construct()
    {
        $this->cart = new M_Cart;
    }


    public function action_index(): void
    {
        $this->title .= ' | Корзина';
	    $this->breadcrumb = ['title' => 'Корзина покупок'];

        if ($this->IsPost()){ //когда нажали на кнопку "Оформить заказ"
	        $this->message= $this->cart->processOrder();
        } else {
	        $this->content = $this->cart->getCart();
	        $this->message = count($this->content) ? '' : 'Ваша корзина пуста';
        }

        $this->template = 'cart/cart.twig';
    }

}