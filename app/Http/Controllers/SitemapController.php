<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $negocios = Negocio::active()->get();

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Home page
        $content .= '<url>';
        $content .= '<loc>' . url('/') . '</loc>';
        $content .= '<changefreq>daily</changefreq>';
        $content .= '<priority>1.0</priority>';
        $content .= '</url>';

        // Login / Register
        $content .= '<url><loc>' . route('login') . '</loc><priority>0.8</priority></url>';
        $content .= '<url><loc>' . route('registro') . '</loc><priority>0.8</priority></url>';

        // Negocios public booking pages
        foreach ($negocios as $negocio) {
            $content .= '<url>';
            $content .= '<loc>' . $negocio->public_booking_url . '</loc>';
            $content .= '<lastmod>' . ($negocio->updated_at ? $negocio->updated_at->toAtomString() : now()->toAtomString()) . '</lastmod>';
            $content .= '<changefreq>weekly</changefreq>';
            $content .= '<priority>0.9</priority>';
            $content .= '</url>';
        }

        $content .= '</urlset>';

        return Response::make($content, 200, [
            'Content-Type' => 'text/xml'
        ]);
    }
}
