/**
 * управление модальными окнами
 *
 * Все модалки работают по одному принципу:
 * нажимаемая кнопка и модалка связаны атрибутом data-modal.
 * При клике на кнопку забираем содержимое атрибута data-modal
 * и ищем модальное окно с таким же атрибутом.
 * После того как нашли нужное модальное окно, добавим классы
 * подложке и окну чтобы показать их.
 * При клике на крестик или на подложку, удаляем классы, чтобы модалка исчезла
 */
document.addEventListener("DOMContentLoaded", () => {
    const AUTH_USER = getCookie("login"); // смотрим, авторизован юзер или нет
    document.querySelector(".count_goods").innerText = AUTH_USER ? getCookie('cartForAuth') : checkCartCookie();  //счётчик кол-ва товаров около иконки корзины

    const modalButtonCart = document.querySelector(".js-open-modal-cart");                         //кнопка корзины
    const modalButtonExitFromSystem = document.querySelector(".js-open-modal-exit-from-system");   // кнопка "выйти из системы"
    const modalSubscribeButton = document.querySelector('.js-subscribe-button');                   // кнопка "Подписаться" в футере
    const overlay = document.querySelector(".js-overlay-modal");                                   // затемняющая подложка
    const closeButtons = document.querySelectorAll(".js-modal-close");                             // крестик закрытия на модалке
    const goodCards = document.querySelectorAll(".card");                                          //карточки товаров
    const buttonAddToCartFromProductPage = document.querySelector('.js_addToCartFromProductPage'); // кнопка "Добавить в корзину" на странице товара
    const buttonSaveChangeOfOrders = document.querySelector('.js-buttonSaveChange');            // кнопка сохранения изменений о статусах заказов у админа

    // когда на странице есть блок с карточками товаров
    // и когда добавляем товар в корзину из карточки товара
    if (goodCards.length) {
        goodCards.forEach((card) => {
            // кнопка "Добавить в корзину на карточке товара
            const buttonAddToCart = card.querySelector(".js_addToCart");

            buttonAddToCart.addEventListener("click", e => {
                e.preventDefault();
                const card = e.currentTarget.closest('.card');
                let id = card.dataset.id;
                const formInModalWindow = document.getElementById('js-modal_add_to_cart-form');
                formInModalWindow.setAttribute('data-modal',id);
                const modalButton = formInModalWindow.nextElementSibling;

                let colors = card.dataset.colors.split(',');
                let sizes = card.dataset.sizes.split(',');
                let category = card.dataset.category;
                let price = card.dataset.price;

                //пытался сделать чтобы эти операции совершались не здесь, а в twig, но не получилось
                let colorsHTML = `<option selected disabled class="option_item" value="">Цвет</option>`;
                colors.forEach(color => colorsHTML += `<option class="option_item" value="${color}">${color}</option>`);

                let sizesHTML = `<option selected disabled class="option_item" value="">Размер</option>`;
                sizes.forEach(size => sizesHTML += `<option class="option_item" value="${size}">${size}</option>`);

                formInModalWindow.querySelector('select[name="color"]').innerHTML = colorsHTML;
                formInModalWindow.querySelector('select[name="size"]').innerHTML = sizesHTML;

                let modalId = e.currentTarget.dataset.modal;
                document.getElementById('js-addToCartModalWindow').setAttribute('data-modal', modalId);
                let modalElem = document.querySelector('.modal[data-modal="' + modalId + '"]');
                modalElem.classList.add("active-modal");
                overlay.classList.add("active-modal");

                // addToCartForAuth находится в AJAX, addToCart в manageCart
                if (AUTH_USER) modalButton.addEventListener("click", addToCartForAuth.bind(modalButton, [id], 'modalWindow'), {once: true});
                else modalButton.addEventListener('click', addToCart.bind(modalButton, [id, category, price], 'modalWindow'), {once: true});
            });
        });
    }



    // когда добавляем товар в корзину со страницы товара
    if (buttonAddToCartFromProductPage) {
        buttonAddToCartFromProductPage.addEventListener('click', e => {
            e.preventDefault();
            const form = document.getElementById('js-add_to_cart_from_product_page-form');
            let id = form.dataset.id;
            let category = form.dataset.category;
            let price = form.dataset.price;
            let color = form.querySelector('select[name="color"]').value;
            let size = form.querySelector('select[name="size"]').value;
            let quantity = form.querySelector('select[name="quantity"]').value;

            // показываем сообщение под кнопкой, добавлен товар или нет
            if (AUTH_USER) e.currentTarget.nextElementSibling.innerText = addToCartForAuth([id, color, size, quantity], 'productPage', null);
            else e.currentTarget.nextElementSibling.innerText = addToCart([id, category, price, color, size, quantity], 'productPage', null);
        });
    }


    // кнопка "Выйти из системы"
    if (modalButtonExitFromSystem){
        modalButtonExitFromSystem.addEventListener("click", e => {
            e.preventDefault();
            let modalId = e.currentTarget.dataset.modal;
            let modalElem = document.querySelector('.modal[data-modal="' + modalId + '"]');
            modalElem.classList.add("active-modal");
            overlay.classList.add("active-modal");
        });
    }

    // кнопка "Подписаться" в подвале страницы
    if (modalSubscribeButton) {
        modalSubscribeButton.addEventListener('click', e => {
            let modalId = e.currentTarget.dataset.modal;
            let modalElem = document.querySelector('.modal[data-modal="' + modalId + '"]');
            modalElem.classList.add("active-modal");
            overlay.classList.add("active-modal");
            //функция в файле AJAX
            subscribeToNews();
        })
    }

    // кнопка корзины
    if (modalButtonCart) {
        modalButtonCart.addEventListener("click", e => {
            //если юзер авторизован, то передаем количество из куки 'cartForAuth', если нет, то из куки 'cart'
            const count = getCookie("login") ? +getCookie("cartForAuth") : checkCartCookie();

            if (!count) {
                e.preventDefault();
                let modalId = e.currentTarget.dataset.modal;
                let modalElem = document.querySelector('.modal[data-modal="' + modalId + '"]');
                modalElem.classList.add("active-modal");
                overlay.classList.add("active-modal");
            }
        });
    }

    // кнопка сохранения изменений о статусах заказов у админа
    if (buttonSaveChangeOfOrders){
        buttonSaveChangeOfOrders.addEventListener('click', e => {
            const modalId = e.currentTarget.dataset.modal;
            const modalElem = document.querySelector('.modal[data-modal="' + modalId + '"]');
            const modalButton = modalElem.querySelector('.js-saveChangeOfOrdersButtonModal');
            modalElem.classList.add("active-modal");
            overlay.classList.add("active-modal");

            modalButton.addEventListener('click', (e,modalElem) => {
                const overlay = document.querySelector(".js-overlay-modal");
                modalElem.classList.remove("active-modal");
                overlay.classList.remove("active-modal");
            }, {once: true});
        });
    }


    // крестик "Закрыть" на модалке
    closeButtons.forEach(button => {
        button.addEventListener("click", e => {
            let parentModal = e.target.closest(".modal");
            parentModal.classList.remove("active-modal");
            overlay.classList.remove("active-modal");
        });
    })

    // затемняющая подложка
    overlay.addEventListener("click", e => {
        document.querySelector(".modal.active-modal").classList.remove("active-modal");
        e.target.classList.remove("active-modal");
    });
});
//КОНЕЦ УПРАВЛЕНИЯ МОДАЛЬНЫМИ ОКНАМИ


// валидация полей ввода информации
const fieldsValidation = document.querySelectorAll('.js-field-validation');
fieldsValidation.forEach(field => {
    //функция в файле fieldValidation
    field.addEventListener('input', fieldValidation.bind(field));
})

// крестики на карточках товара в корзине
const deleteGoodFromCartButtons = document.querySelectorAll('.cart_product .fa-times');
if (deleteGoodFromCartButtons) {
    deleteGoodFromCartButtons.forEach(button => {
        //функция в файле AJAX
        button.addEventListener('click', deleteGoodFromCart.bind(button));
    });
}

// поля количества на карточке товара в корзине
const arrayInputChangeGoodCount = document.querySelectorAll('.js-input-change-count-good-cart');
if (arrayInputChangeGoodCount) {
    arrayInputChangeGoodCount.forEach(input => {
        input.addEventListener("focus", e => e.target.dataset.prevValue = e.target.value);
        //функция в файле AJAX.js
        input.addEventListener("change", changeValueGoodInCart.bind(input))
    });
}



//кнопка полной очистки корзины
const clearCartButton = document.querySelector('.js-clearCartButton');
if (clearCartButton) {
    if (getCookie("login")) { //если юзер авторизован
        // функция в файле AJAX
        clearCartButton.addEventListener('click', clearCart.bind(clearCartButton))
    }
    else {
        clearCartButton.addEventListener('click', (e) => {
            e.preventDefault();
            deleteCookie('cart');
            document.querySelector('.count_goods').innerText = checkCartCookie();
            document.querySelector('.js-clearCart').innerHTML = '<h2 class="message_for_user">Ваша корзина пуста</h2>'
        });
    }
}

// кнопка "Сохранить изменения" в личном кабинете
const saveChangeOfAccountButton = document.querySelector('.js-save-change-of-account');
if (saveChangeOfAccountButton) {
    saveChangeOfAccountButton.addEventListener('click', saveChangeInfoOfAccount.bind(saveChangeOfAccountButton));
}