<?php

namespace App\Http\Controllers\language;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LanguageController extends Controller
{
  public function swap(Request $request, $locale)
  {
    if (!in_array($locale, ['en', 'fr', 'ar', 'de'])) {
      abort(400);
    } else {
      // Store in session
      $request->session()->put('locale', $locale);
      
      // Also store in cookie for better persistence
      $response = redirect()->back();
      $response->cookie('appLocale', $locale, 60 * 24 * 365); // 1 year
      return $response;
    }
  }
}