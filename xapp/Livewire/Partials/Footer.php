<?php

namespace App\Livewire\Partials;

use App\Models\Links;
use Livewire\Component;

class Footer extends Component
{
    public function render()

    {
        $carRental = Links::where('ride_type', 'car_rental')->get();
        $taxiService = Links::where('ride_type', 'taxi_service')->get();
        $topRoutes = Links::where('ride_type', 'top_routes')->get();
        $airportTaxi = Links::where('ride_type', 'airport_taxi')->get();
        $tourPackage = Links::where('ride_type', 'tour_package')->get();
        $selfDrive = Links::where('ride_type', 'self_drive')->get();


        return view('livewire.partials.footer', [
            'carRental'=>$carRental,
            'taxiService'=>$taxiService,
            'topRoutes'=>$topRoutes,
            'airportTaxi'=>$airportTaxi,
            'tourPackage'=>$tourPackage,
            'selfDrive'=>$selfDrive,

        ]);
    }
}
