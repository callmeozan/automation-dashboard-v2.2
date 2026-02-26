<head>
    <meta charset="UTF-8">
    <meta name="theme-color" content="#03142c">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo isset($pageTitle) ? $pageTitle . " | JIS Automation" : "Automation Command Center"; ?></title>

    <link rel="icon" href="image/gajah_tunggal.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/layouts/sidebar.css">
    <link rel="stylesheet" href="assets/css/layouts/header.css">
    <link rel="stylesheet" href="assets/css/components/button.css">
    <link rel="stylesheet" href="assets/css/components/card.css">
    <link rel="stylesheet" href="assets/css/components/modal.css">
    <link rel="stylesheet" href="assets/css/main.css">
    
    <script src="assets/vendor/tailwind.js"></script>
    <script src="assets/vendor/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <?php if (isset($extraHead)) echo $extraHead; ?>

    <style>
        /* Animasi Modal */
        #modalProject:not(.hidden)>div:last-child>div,
        #modalEditProject:not(.hidden)>div:last-child>div,
        #modalLaporan:not(.hidden)>div:last-child>div,
        #modalAddUser:not(.hidden)>div:last-child>div {
            animation: popUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        @keyframes popUp {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Tom Select Dark Mode Styles */
        .ts-control { background-color: #0f172a !important; color: #fff !important; border-radius: 0.5rem; }
        .ts-dropdown { background-color: #1e293b !important; border: 1px solid #334155 !important; color: #fff !important; }
        .ts-dropdown .active { background-color: #334155 !important; color: #fff !important; }
        .ts-control .item, .ts-wrapper.multi .ts-control>div { background-color: #4f46e5 !important; color: white !important; border-radius: 4px; }

        /* Form Input styling (Date & Time) */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator { cursor: pointer; }
        input[type="date"], input[type="time"] { color-scheme: dark; }
        body.light-mode input[type="date"], body.light-mode input[type="time"] { color-scheme: light; }
    </style>

    <script type="module" src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.4/dist/turbo.es2017-esm.js"></script>
    <meta name="view-transition" content="same-origin">
</head>