// ============================================================
// Dark & Light Mode Toggle Script
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    const themeBtn = document.getElementById('themeToggle');
    
    // Check localStorage first, then system preference
    const savedTheme = localStorage.getItem('theme');
    const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Set initial state
    const currentTheme = savedTheme || (systemDark ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', currentTheme);
    
    if (themeBtn) {
        themeBtn.innerHTML = currentTheme === 'dark' ? '🌙' : '☀️';
        
        // Toggle on click
        themeBtn.addEventListener('click', () => {
            let activeTheme = document.documentElement.getAttribute('data-theme');
            let newTheme = activeTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            themeBtn.innerHTML = newTheme === 'dark' ? '🌙' : '☀️';
        });
    }
});
