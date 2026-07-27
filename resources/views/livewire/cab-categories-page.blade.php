<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    @section('title', 'Taxi service in Agra , Delhi, Mathura | Book Cheapest car on rent')
    @section('description',
        'Reliable & Safe Cabs and Taxi Services at Affordable Prices . Duracabs offers city taxis, inter-city cabs, and local cabs at hourly packages.')
    @section('image', 'https://www.duracabs.com/img/logo/duracabs_logo.jpeg')
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-7 mx-auto">
        <section class="mb-12">
            <h1 class="text-3xl font-bold py-5">Find the Perfect Ride: Explore Cab Categories with Duracabs - Car Rental
                Service</h1>

            <p class="px-4 py-2">Duracabs offers a wide range of <strong>cab categories</strong> to meet all your travel
                needs across
                India. Whether you're planning a quick city ride or a long-distance journey, our <strong>car
                    rental</strong> and <strong>taxi service</strong> options provide flexibility, comfort, and
                affordability.</p>

            <p class="px-4 py-2">Looking for a <strong>Delhi to Agra taxi</strong> or <strong>Agra to Delhi
                    taxi</strong>? Our
                <strong>one way taxi services</strong> make intercity travel seamless and budget-friendly. Need a
                larger vehicle for group travel? We offer <strong>tempo traveller for rent</strong>, perfect for
                family vacations and corporate trips.
            </p>

            <p class="px-4 py-2">With Duracabs, enjoy easy <strong>online cab booking</strong> and choose from our clean,
                GPS-enabled
                fleet including hatchbacks, sedans, SUVs, and luxury vehicles. Whether you’re scheduling a
                <strong>Delhi airport taxi booking</strong> or simply searching for a <strong>taxi service near
                    me</strong>, Duracabs ensures fast, safe, and reliable service.
            </p>


        </section>

        <div class="grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-6">

            @foreach ($categories as $category)
                <a class="group flex flex-col bg-white border shadow-sm rounded-xl hover:shadow-md transition dark:bg-slate-900 dark:border-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                    href="rides?selected_categories[0]={{ $category->id }}" wire:key='{{ $category->id }}'>


                    <div class="p-4 md:p-5">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <img class="h-[5rem] w-[5rem]" src="{{ url('storage') }}/{{ $category->image }}"
                                    alt="{{ $category->name }}">
                                <div class="ms-3">
                                    <h3
                                        class="group-hover:text-blue-600 text-2xl font-semibold text-gray-800 dark:group-hover:text-gray-400 dark:text-gray-200">
                                        {{ $category->name }}
                                    </h3>
                                </div>
                            </div>
                            <div class="ps-3">
                                <svg class="flex-shrink-0 w-5 h-5" xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach



        </div>

        <section class="my-4">
            <p class="px-4 py-2">We are committed to delivering the best <strong>one way cab</strong> experience with
                24/7 customer
                support and transparent pricing. Choose Duracabs to travel smarter and safer, every time you hit the
                road.</p>
        </section>
    </div>
</div>
