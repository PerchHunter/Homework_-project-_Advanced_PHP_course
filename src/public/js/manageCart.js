/**
 * берет куки корзины, которые хранятся в виде 'id/категория/размер/цвет/цена/количество' '...' '...'
 * когда юзер не авторизован,
 * вычисляет количество товаров и возвращает его
 * @param count
 * @returns {number}
 */
function checkCartCookie(count = 0) {
  let userCartCookie = getCookie("cart");

  if (userCartCookie) {
    let goods = userCartCookie.split(" ");

    for (let good of goods) {
      let countGood = good.split("/");
      count += +countGood[countGood.length - 1];
    }
  }

  return count;
}


/**
 * КОГДА ЮЗЕР НЕ АВТОРИЗОВАН
 * Принимает информацию о товаре, добавляемом в корзину и место откуда вызвали - callPoint.
 * callPoint может быть либо "modalWindow" - когда выбираем кол-во и цвет в модальном окне,
 * либо "productPage" - когда выбираем на странице товара
 * обрабатывает в вид 'id/категория/размер/цвет/цена/количество' '...' '...',
 * меняет число у иконки корзины
 * @param args
 * @param callPoint
 * @param e
 */
function addToCart(args, callPoint, e) {
  let [id, category, price, color, size, quantity] = args;

  if (callPoint === 'modalWindow') {
    e.preventDefault();
    const modalWindow = e.target.closest('#js-addToCartModalWindow');
    const modalTitle = modalWindow.querySelector('.modal__title');
    color = modalWindow.querySelector('select[name="color"]').value;
    size = modalWindow.querySelector('select[name="size"]').value;
    quantity = modalWindow.querySelector('select[name="quantity"]').value;

   console.log(e.target);
    if (!color || !size || !+quantity) {
      modalTitle.innerText = 'Пожалуйста, выберите все поля';
      e.target.addEventListener('click', addToCart.bind( e.target, args, 'modalWindow'),{once: true});
      return;
    }
  }
  else if (callPoint === 'productPage') {
    if (!color || !size || !+quantity) return 'Пожалуйста, выберите все поля';
  }

  let options = {
    'max-age': new Date(Date.now() + 86400e3 * 7), //куки на неделю для неавторизованного польз-ля
  };
  let nameCookie = "cart";
  let value = `${id}/${category}/${size}/${color}/${price}/${quantity}`;
  let currentCookie = getCookie(nameCookie);

  if (currentCookie) { // куки выглядят так 'id/категория/размер/цвет/цена/количество' '...' '...'
    const regexpID = new RegExp(`\s?${id}\/`);
    const regexpSize = new RegExp(`\/${size}\/`)
    const regexpColor = new RegExp(`\/${color}\/`)

    if (regexpID.test(currentCookie) && regexpSize.test(currentCookie) && regexpColor.test(currentCookie)) {
      let arr = currentCookie.split(' ');
      let subArr = arr.findIndex(item => regexpID.test(item) && regexpSize.test(item) && regexpColor.test(item));

      if (subArr === -1) {
        value = currentCookie + " " + value;
      }
      else {
        // если в куки нашли уже добавленный товар с таким же id, размером и цветом, то обновляем ему количество
        let elem = arr[subArr].split('/');
        value = `${id}/${category}/${size}/${color}/${price}/${quantity + +elem[elem.length - 1]}`;
        arr.splice(subArr, 1, value )
        value = arr.join(' ');
      }

    } else { // в противном случае просто к строке текущих куки добавляем строку с новым товаром
      value = currentCookie + " " + value;
    }
  }

  setCookie(nameCookie, value, options);
  document.querySelector(".count_goods").innerText = checkCartCookie();

  if (callPoint === 'modalWindow') {
    e.target.closest(".modal").classList.remove("active-modal");
    document.querySelector(".js-overlay-modal").classList.remove("active-modal");
  }
  else if (callPoint === 'productPage') {
    return 'Товар добавлен в корзину!';
  }
}
