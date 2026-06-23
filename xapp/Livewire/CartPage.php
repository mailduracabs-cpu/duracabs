<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Cart - DCodeMania')]
class CartPage extends Component
{
    public $cart_items = [];
    public $grand_total;

    public function mount(){
        $this->cart_items = CartManagement::getCartItemsFromCookie();
        $this->grand_total = CartManagement::calculateGrandTotal($this->cart_items);
    }

    public function removeItem($product_id){
        $this->cart_items = CartManagement::removeCartItems($product_id);
        $this->grand_total = CartManagement::calculateGrandTotal($this->cart_items);
        $this->dispatch('update-cart-count', total_count: count($this->cart_items))->to(Navbar::class);
    }

    public  function increaseQty($product_id){
        $this->cart_items = CartManagement::increamentQuntityToCartItem($product_id);
        $this->grand_total = CartManagement::calculateGrandTotal($this->cart_items);

    }

    public  function decreaseQty($product_id){
        $this->cart_items = CartManagement::decrementQuntityToCartItem($product_id);
        $this->grand_total = CartManagement::calculateGrandTotal($this->cart_items);

    }
    public function render()
    {
        return view('livewire.cart-page');
    }
}
