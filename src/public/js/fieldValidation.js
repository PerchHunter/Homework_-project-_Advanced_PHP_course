/**
 * Валидация полей ввода на страницах
 * @param e
 */
function fieldValidation(e) {
    const field = e.target.name;
    let regexp;
    let title;

    if (field === 'name' || field === 'surname') {
        regexp = /^[A-Za-zА-Яа-я]{2,50}$/;
        title = 'От 2 до 50 буквенных символов';
    }
    else if (field === 'email') {
        regexp = /^([a-z0-9_\.-]+)@([a-z0-9_\.-]+)\.([a-z\.]{2,6})$/;
        title = 'Пример: ivan-petrovich5-5_5@mail.ru';
    }
    else if (field === 'password' || field === 'newPassword') {
        regexp = /^(?=.*[a-zа-я])(?=.*[A-ZА-Я])(?=.*[0-9@#$%]).{8,}$/;
    }
    else if (field === 'postalAddress') {
        regexp = /^[a-zA-Zа-яА-Я0-9,\.\-\/_\s]{10,400}$/miu;
        title = 'От 10 до 400 символов a-zA-Zа-яА-Я0-9,.-\/_';
    }
    else if (field === 'postalCode') {
        regexp = /^[0-9]{1,20}$/;
        title = 'От 1 до 20 цифр';
    }
    else if (field === 'telephone') {
        regexp = /^[0-9()\-]{4,50}$/;
        title = 'От 4 до 50 символов 0-9-)(';
    }


    if (regexp.test(e.target.value)) {
        e.target.classList.remove('invalidField');
        e.target.classList.add('validField');
    }
    else {
        e.target.classList.remove('validField');
        e.target.classList.add('invalidField');
        e.target.setAttribute('title', title);
    }
}



