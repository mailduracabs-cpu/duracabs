
<div class="lg:mx-10 mx-2 my-4 ">
  @section('title', '404 Page Not Found')
  
<div class=" flex justify-center items-center">
<img src="/img/404page.png" width="50%"  alt="Duracabs 404 Page"/> <br/>

</div>
<div class="text-center mt-6">
<h1 class="text-2xl">404 Page Not Found</h1>
<h2>You Will Redirect To homepage</h2>
</div>
<div class="flex justify-center items-center">
 <a wire:navigate
                            class="font-medium bg-sky-700 rounded-2xl text-white hover:text-black hover:bg-slate-400 px-4 py-3 md:py-3 mt-6 dark:text-blue-500 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/" aria-current="page">Back To Home</a>   
</div>
  
<script>
 // Your application has indicated there's an error
    window.setTimeout(function(){

        // Move to a new location or you can do something else
        window.location.href = "/";

    }, 3000);
</script>

</div>


