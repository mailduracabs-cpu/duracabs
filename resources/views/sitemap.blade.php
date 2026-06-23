<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url>
<loc>{{url("/")}}/</loc>
<lastmod>2024-10-01T04:13:10+00:00</lastmod>
<priority>1.00</priority>
</url>
<url>
<loc>{{url("/")}}/about-us</loc>
<lastmod>2024-10-01T04:13:10+00:00</lastmod>
<priority>0.80</priority>
</url>
<url>
<loc>{{url("/")}}/terms-and-conditions</loc>
<lastmod>2024-10-01T04:13:10+00:00</lastmod>
<priority>0.80</priority>
</url>
<url>
<loc>{{url("/")}}/contact-us</loc>
<lastmod>2024-10-01T04:13:10+00:00</lastmod>
<priority>0.80</priority>
</url>
<url>
<loc>{{url("/")}}/page/event-planner</loc>
<lastmod>2024-10-01T04:13:10+00:00</lastmod>
<priority>0.80</priority>
</url>
<url>
<loc>{{url("/")}}/page/b2b</loc>
<lastmod>2024-10-01T04:13:10+00:00</lastmod>
<priority>0.80</priority>
</url>
<url>
<loc>{{url("/")}}/vendor-register</loc>
<lastmod>2024-10-01T04:13:10+00:00</lastmod>
<priority>0.80</priority>
</url>
@foreach ($routes as $route)
 <url>
    <loc>{{url("/")}}/route/{{$route->slug}}</loc>
    <lastmod>{{$route->created_at->tz('UTC')->toAtomString()}}</lastmod>
    <changefreq>daily</changefreq>
    <priority>.8</priority>
  </url>
@endforeach
@foreach ($pages as $page)
 <url>
    <loc>{{url("/")}}/pages/{{$page->slug}}</loc>
    <lastmod>{{$page->created_at->tz('UTC')->toAtomString()}}</lastmod>
    <changefreq>daily</changefreq>
    <priority>.8</priority>
  </url>
@endforeach
 
</urlset>