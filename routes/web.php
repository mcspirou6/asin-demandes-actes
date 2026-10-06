<?php

// Deux interfaces distinctes :
//  - "/"      : espace usager (dépôt, suivi par code de suivi, consultation)
//  - "/admin" : espace agent (connexion, dashboard de traitement)
Route::view('/', 'requests');
Route::view('/admin', 'admin');
