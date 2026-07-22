<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cache du tableau de bord statistiques
    |--------------------------------------------------------------------------
    |
    | Les agrégats "à vie" (CA total, dette totale, valeur de stock) scannent
    | des tables entières sans filtre de date possible. Ce flag permet de les
    | mettre en cache court une fois leur coût réel mesuré — désactivé par
    | défaut le temps de ce premier passage.
    |
    */
    'stats_cache_enabled' => (bool) env('SUPERADMIN_STATS_CACHE_ENABLED', false),

    'stats_cache_ttl' => (int) env('SUPERADMIN_STATS_CACHE_TTL', 120),

];
