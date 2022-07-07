<?php

class App {

    public static function init(): void {
        date_default_timezone_set('Europe/Moscow');

        if (php_sapi_name() !== 'cli' && isset($_SERVER)) {
            self::web();
        }
    }

    //site.ru/index.php?c=user&act=auth

    protected static function web(): void
    {
	    // если юзер первый раз зашёл на сайт и у него ещё не было роли, то присваиваем 0 - неавторизованный пользователь
	    if (is_null($_SESSION['role'])) $_SESSION['role'] = '0';

	    $action = 'action_';
        $action .= $_GET['act'] ?? 'index';


//        $controller = match ($_GET['c']) {     //для PHP версии 8.0 и выше
//            'page' => new C_Page,
//            'user' => new C_User,
//            'cart' => new C_Cart,
//            default => new C_Page,
//        };

        switch ($_GET['c'])
        {
            case 'page':
                $controller = new C_Page;
                break;
            case 'user':
                $controller = new C_User;
                break;
            case 'cart':
                $controller = new C_Cart;
                break;
	        case 'admin':
		        $controller = new C_Admin;
		        break;
            default:
                $controller = new C_Page;
        }

        $controller->Request($action);
    }
}