<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Page;
use Illuminate\Http\Response;

class SiteMapController extends Controller
{
    public function index(): Response
    {
        $pages = Page::latest()->get();

        $routes = Product::where('on_sale', 1)
            ->latest('updated_at')
            ->get();

        return response()
            ->view('sitemap', [
                'pages'  => $pages,
                'routes' => $routes,
            ])
            ->header('Content-Type', 'text/xml');
    }
}