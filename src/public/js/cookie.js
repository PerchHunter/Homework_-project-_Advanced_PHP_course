//
//  функции получения, изменения и удаления куки на стороне клиента
//

// возвращает куки с указанным name или undefined, если ничего не найдено
function getCookie(name) {
    let matches = document.cookie.match(
        new RegExp(
            "(?:^|; )" +
            name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, "\\$1") +
            "=([^;]*)"
        )
    );
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

// Пример использования:
// setCookie("user", "John", { secure: true, "max-age": 3600 });

function setCookie(name, value, options = {}) {
    options = {
        path: "/",
        // при необходимости добавляем другие значения по умолчанию
        ...options,
    };

    if (options.expires instanceof Date) {
        options.expires = options.expires.toUTCString();
    }

    let updatedCookie =
        encodeURIComponent(name) + "=" + encodeURIComponent(value);

    for (let optionKey in options) {
        updatedCookie += "; " + optionKey;
        let optionValue = options[optionKey];
        if (optionValue !== true) {
            updatedCookie += "=" + optionValue;
        }
    }

    document.cookie = updatedCookie;
}

// удаляет куки с нужным именем
function deleteCookie(name) {
    setCookie(name, "", {
        'max-age': -1
    })
}