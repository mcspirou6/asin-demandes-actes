<?php

/*
|--------------------------------------------------------------------------
| Espace agent (validateur) — identifiants de démonstration
|--------------------------------------------------------------------------
|
| Un unique agent fictif, volontairement simple pour la démonstration.
| Ces identifiants sont fictifs et documentés dans le README ; ils ne
| sont pas des secrets de production. En conditions réelles, il faudrait
| une table users + hachage de mot de passe + rôles.
*/

return [
    'email' => env('ADMIN_EMAIL', 'agent@asin.bj'),
    'password' => env('ADMIN_PASSWORD', 'demo1234'),
];
