<?php

use Illuminate\Support\Facades\Route;

// L'interface de consultation des demandes est servie par Laravel.
Route::get('/', function () {
    return view('requests');
});
