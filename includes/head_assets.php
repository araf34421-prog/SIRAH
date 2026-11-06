<?php
/** Shared browser assets used by application pages - Upgraded Theme */
?>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
                display: ['"Fraunces"', 'Georgia', 'serif'],
            },
            colors: {
                // Remap 'indigo' (yang dipakai di seluruh halaman) menjadi Emas Kaligrafi / Warm Amber-Bronze
                indigo: {
                    50:  '#fdfaf3',
                    100: '#faefd9',
                    200: '#f3dcb0',
                    300: '#eac27d',
                    400: '#dfa249',
                    500: '#c98227',
                    600: '#0f513e', // Tombol utama (bg-indigo-600) otomatis menjadi Deep Emerald Surau
                    700: '#0b3d2e',
                    800: '#093024',
                    900: '#06231a',
                    950: '#041610',
                }
            }
        }
    }
}
</script>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">