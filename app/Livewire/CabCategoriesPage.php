<?php

namespace App\Livewire;

use App\Models\Category;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Cab Categories -  Duracabs')]
class CabCategoriesPage extends Component
{
    public function render()
    {
        $categories =  Category::where('is_active', 1)->get();
        return view('livewire.cab-categories-page',[
            'categories' => $categories
        ]);
    }
}
