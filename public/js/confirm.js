document.addEventListener('DOMContentLoaded', function () {
    console.log('Confirm.js подключен!');

    const confirmButton = document.querySelector('.js-confirm-action');

    if (confirmButton) {
        confirmButton.addEventListener('click', function (e) {
            e.preventDefault(); // Предотвращаем стандартный переход по ссылке

            const unitInput = document.querySelector('input[name="Recipes[unit]"]');
            const comment = document.querySelector('input[name="Recipes[comment]"]');
            if (!unitInput) {
                alert('Поле "unit" не найдено!');
                return;
            }

            const unitValue = unitInput.value;
            const commentValue = comment.value;

            // Убираем предупреждение об изменениях в форме
            window.onbeforeunload = null; // Очищаем обработчик предупреждения

            const formData = new FormData();
            formData.append('recipe_unit', unitValue);
            formData.append('comment', commentValue);

            fetch(confirmButton.getAttribute('href'), {
                method: 'POST',
                body: formData,
            })
                .then(response => {
                    if (response.ok) {
                        window.location.href = response.url; // Перенаправляем после успеха
                    } else {
                        alert('Ошибка при подтверждении рецепта!');
                    }
                })
                .catch(() => alert('Ошибка при подключении к серверу.'));
        });
    } else {
        console.warn('Кнопка подтверждения не найдена!');
    }
});
