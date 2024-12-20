document.addEventListener('DOMContentLoaded', () => {
    // Находим кастомную кнопку подтверждения
    const confirmButton = document.querySelector('.btn-success');

    if (confirmButton) {
        confirmButton.addEventListener('click', () => {
            // Убираем предупреждение о несохранённых данных
            window.onbeforeunload = null;
        });
    }
});