<?php

use App\Models\ShortLink;
use Illuminate\Support\Facades\Route;

Route::permanentRedirect('/insta', '/catalog?utm_source=instagram&utm_medium=social&utm_campaign=accaunt_link');
Route::permanentRedirect('/tiktok', '/catalog?utm_source=tiktok&utm_medium=social&utm_campaign=accaunt_link');

Route::get('lnk/{short_link:short_link}', fn (ShortLink $shortLink) => redirect($shortLink->full_link))
    ->missing(fn () => redirect()->route('shop'));