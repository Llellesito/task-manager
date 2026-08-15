const themeToggle = document.getElementById('theme-toggle');


// Cargar el tema guardado
const savedTheme = localStorage.getItem('theme');

if (savedTheme === 'dark') {
    document.body.classList.add('dark');
    themeToggle.textContent = '☀️ Light mode';
}


// Cambiar tema
themeToggle.addEventListener('click', () => {

    document.body.classList.toggle('dark');

    const isDark = document.body.classList.contains('dark');

    localStorage.setItem(
        'theme',
        isDark ? 'dark' : 'light'
    );

    themeToggle.textContent = isDark
        ? '☀️ Light mode'
        : '🌙 Dark mode';
});