<?php

namespace Database\Seeders;

use App\Models\Superadmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Provisionne uniquement les comptes superadmin — jamais de données de démo
 * (boutiques/produits/clients, voir DemoDataSeeder). Prévu pour tourner seul
 * sur un serveur de production :
 *
 *   php artisan db:seed --class=SuperadminSeeder
 *
 * Mots de passe lus depuis l'environnement (jamais en clair dans le code) :
 *   SUPERADMIN_PASSWORD_1 -> adodabo00@gmail.com
 *   SUPERADMIN_PASSWORD_2 -> superadmin@boutik.shop
 *
 * Si une variable n'est pas définie ET que le compte n'existe pas encore, un
 * mot de passe aléatoire est généré et affiché UNE SEULE FOIS dans la sortie
 * console (il n'est ensuite récupérable nulle part, seul son hash est stocké).
 *
 * Idempotent : relancer le seeder ne duplique jamais les comptes (updateOrCreate
 * par email) et ne fait PAS tourner silencieusement un mot de passe déjà en
 * place — le hash n'est mis à jour que lors de la création initiale, ou si la
 * variable d'env correspondante est explicitement fournie lors du re-run.
 */
class SuperadminSeeder extends Seeder
{
    private const ACCOUNTS = [
        'adodabo00@gmail.com' => 'SUPERADMIN_PASSWORD_1',
        'superadmin@boutik.shop' => 'SUPERADMIN_PASSWORD_2',
    ];

    public function run(): void
    {
        foreach (self::ACCOUNTS as $email => $envVar) {
            $existing = Superadmin::where('email', $email)->first();
            $envPassword = env($envVar);

            $password = null;
            $generated = false;

            if ($envPassword) {
                $password = $envPassword;
            } elseif (! $existing) {
                $password = Str::random(20);
                $generated = true;
            }

            $values = ['name' => 'Superadmin Boutik'];
            if ($password !== null) {
                $values['password_hash'] = Hash::make($password);
            }

            Superadmin::updateOrCreate(['email' => $email], $values);

            if ($generated) {
                $this->command?->warn("[$email] $envVar non définie — mot de passe généré (à noter maintenant, il ne sera plus jamais affiché) :");
                $this->command?->info("    $password");
            } elseif ($existing && ! $envPassword) {
                $this->command?->info("[$email] compte déjà présent, mot de passe inchangé (définissez $envVar pour le faire tourner).");
            } else {
                $this->command?->info("[$email] superadmin provisionné.");
            }
        }
    }
}
