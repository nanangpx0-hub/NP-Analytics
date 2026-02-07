export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./app/Livewire/**/*.php",
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    navy: '#0f172a',
                    gold: '#f59e0b',
                    bg: '#f8fafc',
                    surface: '#ffffff',
                }
            }
        },
    },
    plugins: [],
}
