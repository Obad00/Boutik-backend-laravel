<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DemoDataSeeder::class);

        // SuperadminSeeder n'est PAS appelé ici volontairement : il provisionne
        // les comptes superadmin de production et doit rester indépendant des
        // données de démo (boutiques/produits/clients de test). Pour le lancer
        // seul, y compris en production :
        //   php artisan db:seed --class=SuperadminSeeder
    }
}
