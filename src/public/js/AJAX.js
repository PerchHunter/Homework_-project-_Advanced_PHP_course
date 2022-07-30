//  добавление товара в корзину для зарегистрированного пользователя
function addToCartForAuth(args, callPoint, e) {
    let [id, color, size, quantity, string] = args;

    if (callPoint === 'modalWindow') {
        e.preventDefault();
        const modalWindow = e.target.closest('#js-addToCartModalWindow');
        const modalTitle = modalWindow.querySelector('.modal__title');
        let color = modalWindow.querySelector('select[name="color"]').value;
        let size = modalWindow.querySelector('select[name="size"]').value;
        let quantity = modalWindow.querySelector('select[name="quantity"]').value;

        if (!color || !size || !+quantity) return modalTitle.innerText = 'Пожалуйста, выберите все поля';

        string = `c=Cart&action=addToCartForAuth&idGood=${id}&color=${color}&size=${size}&quantity=${quantity}`;
    }
    else if (callPoint === 'productPage') {
        if (!color || !size || !+quantity) return 'Пожалуйста, выберите все поля';
        string = `c=Cart&action=addToCartForAuth&idGood=${id}&color=${color}&size=${size}&quantity=${quantity}`;
    }

    const headers = {
        "content-type": "application/x-www-form-urlencoded"
    }

    fetch('../lib/server.php', {method: "POST", body: string, headers: headers})
        .then(response => {
            return response.status !== 200 ? Promise.reject() : response.json();
        })
        .then(result => {
            if (result) {
                if (callPoint === 'modalWindow') {
                    e.target.closest(".modal").classList.remove("active-modal");
                    document.querySelector(".js-overlay-modal").classList.remove("active-modal");
                    document.querySelector('.count_goods').innerText = getCookie('cartForAuth');
                }
                else if (callPoint === 'productPage') {
                    document.querySelector('.count_goods').innerText = getCookie('cartForAuth');
                    let messageNode = document.querySelector('.warning_message');
                    messageNode.innerText = 'Товар добавлен в корзину!';
                    setTimeout(() => messageNode.innerText = '', 2000);
                }
            }
        })
}

// удаление товара из корзины
function deleteGoodFromCart(e) {
    const id = e.target.dataset.id;
    const color = e.target.dataset.color;
    const size = e.target.dataset.size;

    const string = `c=Cart&action=deleteGood&idGood=${id}&color=${color}&size=${size}`;
    const headers = {
        "content-type": "application/x-www-form-urlencoded"
    }

    fetch('../lib/server.php', {method: "POST", body: string, headers: headers})
        .then(response => {
            return response.status !== 200 ? Promise.reject() : response.json();
        })
        .then(result => {
            if (+result[0]) { //если в кол-во товаров в корзине > 0
                e.target.parentElement.remove();
                document.querySelector('.js-cart-total-price').innerHTML = `&#8381;${result[1]}`;
            } else {
                document.querySelector('.js-clearCart').innerHTML = '<h2 class="message_for_user">Ваша корзина пуста</h2>'
            }

            document.querySelector('.count_goods').innerText = result[0];
        })
}

//изменение значения количества в карточке товара в корзине
function changeValueGoodInCart(e) {
    const info = e.target.name.split('-');
    const id = info[0];
    const color = info[1];
    const size = info[2];
    const prevValue = e.target.dataset.prevValue;
    const curValue = e.target.value;

    const string = `c=Cart&action=changeValueGoodInCart&idGood=${id}&color=${color}&size=${size}&prevValue=${prevValue}&curValue=${curValue}`;
    const headers = {
        "content-type": "application/x-www-form-urlencoded"
    }

    fetch("../lib/server.php", {method: "POST", body: string, headers: headers})
        .then(response => {
            return response.status !== 200 ? Promise.reject() : response.json();
        })
        .then(result => {
            document.querySelector('.count_goods').innerText = result[0];
            document.querySelector('.js-cart-total-price').innerHTML = `&#8381;${result[1]}`;
        })
}

// полная очистка корзины
function clearCart(e) {
    e.preventDefault();
    const string = `c=Cart&action=clearCart`;
    const headers = {
        "content-type": "application/x-www-form-urlencoded"
    }

    fetch('../lib/server.php', {method: "POST", body: string, headers: headers})
        .then(response => {
            return response.status !== 200 ? Promise.reject() : response.json();
        })
        .then(result => {
            if (result) {
                document.querySelector('.count_goods').innerText = getCookie('cartForAuth');
                document.querySelector('.js-clearCart').innerHTML = '<h2 class="message_for_user">Ваша корзина пуста</h2>';
            }
        })
}

// выход из системы
function exitFromSystem(e) {
    const overlay = document.querySelector(".js-overlay-modal");
    const parentModal = e.target.closest(".modal");
    const cart = document.querySelector('.js-exitFromSystem'); // блок - контейнер корзины
    const menu = document.querySelector('.js-auth-menu-box-item'); // выпадающий список у иконки профиля

    const string = `c=User&action=exitFromSystem`;
    const headers = {
        "content-type": "application/x-www-form-urlencoded"
    }

    fetch('../lib/server.php', {method: 'POST', body: string, headers: headers})
        .then(response => {
            return response.status !== 200 ? Promise.reject() : response.text();
        })
        .then(result => {
            // скрываем модальное окно
            parentModal.classList.remove("active-modal");
            overlay.classList.remove("active-modal");
            document.querySelector('.count_goods').innerText = 0;
            // если на момент выхода из профиля мы находились на странице корзины...
            if (cart) cart.innerHTML = '<h2 class="message_for_user">Ваша корзина пуста</h2>';
            // заменяем пункты в менюшке
            menu.innerHTML = '<a class="item_link" href="?c=user&act=registration">Зарегистрироваться</a>' +
                '<a class="item_link" href="?c=user&act=auth">Войти</a>';
        })
}

//изменение информации о пользователе в личном кабинете
function saveChangeInfoOfAccount() {
    const message = document.querySelector('.warning_message');
    const parent = document.getElementById('changePersonalAccount_form');

    const name = parent.querySelector('input[name="name"]').value;
    const surname = parent.querySelector('input[name="surname"]').value;
    const postalAddress = parent.querySelector('input[name="postalAddress"]').value;
    const postalCode = parent.querySelector('input[name="postalCode"]').value;
    const telephone = parent.querySelector('input[name="telephone"]').value;
    const gender = parent.querySelector('input[name="gender"]:checked').value;
    const email = parent.querySelector('input[name="email"]').value;
    const currentPassword = parent.querySelector('input[name="currentPassword"]').value;
    const newPassword = parent.querySelector('input[name="newPassword"]').value;

    const string = `c=User&action=saveChangeInfoOfAccount&name=${name}&surname=${surname}&postalAddress=${postalAddress}&postalCode=${postalCode}&telephone=${telephone}&gender=${gender}&email=${email}&currentPassword=${currentPassword}&newPassword=${newPassword}`;
    const headers = {
        "content-type": "application/x-www-form-urlencoded"
    }

    fetch('../lib/server.php', {method: "POST", body: string, headers: headers})
        .then(response => {
            return response.status !== 200 ? Promise.reject() : response.text();
        })
        .then(text => message.innerText = text)
}

//подписка на новости в подвале сайта
function subscribeToNews() {
    const email = document.querySelector('.js-subscribe-input').value;

    const string = `c=User&action=subscribeToNews&email=${email}`;
    const headers = {
        "content-type": "application/x-www-form-urlencoded"
    }

    fetch('../lib/server.php', {method: "POST", body: string, headers: headers})
        .then(response => {
            return response.status !== 200 ? Promise.reject() : response.text();
        })
        .then(text => document.querySelector('.js-subscribe-message-text').innerText = text)
}

// изменение размеров в зависимости от выбранного цвета при добавлении товара в корзину
function updateSizesInSelect(e, whereIsCall) {
    const color = e.target.value;
    let parentElem, idGood;

    if (whereIsCall === 'modalWindow') {
        parentElem = e.target.closest('#js-addToCartModalWindow');
        idGood = parentElem.dataset.modal;
    }
    else if (whereIsCall === 'productPage') {
        parentElem = e.target.closest('form');
        idGood = parentElem.dataset.id;
    }

    const string = `c=Page&action=updateSizesInSelect&idGood=${idGood}&color=${color}`;
    const headers = {
        "content-type": "application/x-www-form-urlencoded"
    }

    fetch('../lib/server.php', {method: "POST", body: string, headers: headers})
        .then(response => {
            return response.status !== 200 ? Promise.reject() : response.json();
        })
        .then(sizes => {
            let sizesHTML = `<option class="option_item" value="">Размер</option>`;
            for (let size of sizes) {
                sizesHTML += `<option class="option_item" value="${size.sizes}">${size.sizes}</option>`;
            }

            parentElem.querySelector('select[name="size"]').innerHTML = sizesHTML;
        })
}

// функция сохранения изменённой админом информации о товаре
function saveChangeOfGood(e) {
    const idGood = e.target.value;
    const rowInTable = document.getElementById(`js-row-${idGood}`);
    const nameGood = rowInTable.querySelector('textarea[name="name_good"]').value;
    const priceGood = rowInTable.querySelector('input[name="price"]').value;
    const brandGood = rowInTable.querySelector('textarea[name="brand"]').value;
    const descriptionGood = rowInTable.querySelector('textarea[name="description"]').value;
    const id_categoryGood = rowInTable.querySelector('input[name="id_category"]').value;
    const statusGood = rowInTable.querySelector('input[name="status"]').value;
    const viewsGood = rowInTable.querySelector('input[name="views"]').value;

    const string = `c=Admin&action=saveChangeOfGood&id=${idGood}&name=${nameGood}&price=${priceGood}&brand=${brandGood}&description=${descriptionGood}&idCategory=${id_categoryGood}&status=${statusGood}&views=${viewsGood}`;
    const headers = {
        "content-type": "application/x-www-form-urlencoded"
    }

    fetch('../lib/server.php', {method: "POST", body: string, headers: headers})
        .then(response => {
            return response.status !== 200 ? Promise.reject() : response.text();
        })
        .then(text => e.target.innerText = text)
}

// При добавлении нового товара в каталог, когда админ выбирает главную категорию
// в соседний select подтягиваются подкатегории для этой категории.
// Например, выбираем "Мужчинам" - подтягиваются подкатегории для мужчин
function updateCategoryWhenAddProduct(e) {
    const idMajCat = e.target.value;

    const string = `c=Admin&action=updateCategoryWhenAddProduct&idMajCat=${idMajCat}`;
    const headers = {
        "content-type": "application/x-www-form-urlencoded"
    }

    fetch('../lib/server.php', {method: "POST", body: string, headers: headers})
        .then(response => {
            return response.status !== 200 ? Promise.reject() : response.json();
        })
        .then(categories => {
            let categoriesHTML = `<option selected disabled class="option_item" value="">Категория</option>`;
            for (let category of categories) {
                categoriesHTML += `<option class="option_item" value="${category.id_category}">${category.id_category}-${category.category_title}</option>`;
            }

            document.querySelector('table.addGoodInCatalog select[name="category"]').innerHTML = categoriesHTML;
        })
}


// Добавляем товару цвет и размер товару в админке
function addColorSizeForGood(e) {
    const table = document.querySelector('table.addColorSize');
    const idGood = table.querySelector('select[name="idGoodAdd"]').value;
    const color = table.querySelector('select[name="colorAdd"]').value;
    const size = table.querySelector('select[name="sizeAdd"]').value;

    const string = `c=Admin&action=addColorSizeForGood&idGood=${idGood}&color=${color}&size=${size}`;
    const headers = {
        "content-type": "application/x-www-form-urlencoded"
    }

    fetch("../lib/server.php", {method: "POST", body: string, headers: headers})
        .then(response => {
            return response.status !== 200 ? Promise.reject() : response.text();
        })
        .then(text => {
            e.target.innerText = text;
            setTimeout(() => e.target.innerText = 'Добавить', 2000);
        })
}