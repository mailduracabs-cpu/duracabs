<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Page;
use illuminate\http\Response;

class SiteMapController extends Controller
{
    public function index(): Response 
    {
        $page = Page::latest()->get();
        $route = Product::where('on_sale', 1)->get();

        return response()->view('sitemap',[
            'pages' => $page,
            'routes' => $route,
        ])->header('Content-Type', 'text/xml');
    }
}
 