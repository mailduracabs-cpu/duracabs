<?php

namespace App\Helpers;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Cookie;

class CartManagement {
    // add item to cart 

    static public function addItemsToCart($product_id){
        $cart_items = [];
        
        $product = Product::where('id', $product_id)->first(['id', 'name','brand_id', 'images', 'price','booking_to','category_id']);
        $cityFrom = Brand::where('id', $product->brand_id)->first(['id', 'name']);
        $cityTo = Brand::where('id', $product->booking_to)->first(['id', 'name']);
        $cabModel = Category::where('id', $product->category_id)->first(['id', 'name']);
        
        if($product){
            $cart_items[] = [
                'product_id' => $product_id,
                'name' => $product->name,
                'image' => $product->images[0],
                'quantity' => 1,
                'unit_ammount' => $product->price,
                'total_ammount' => $product->price,
                'cityFrom' => $cityFrom->name,
                'cityTo' => $cityTo->name,
                'cabModel' => $cabModel->name,
                'type' => $product->ride_type,
            ];
        }
       
       
        self::addCartItemsToCookie($cart_items);
        return count($cart_items);
    }
    static public function addItemsToCartOneWay($product_id){

       
       
        $cart_items = [];

        $product = Product::where('id', $product_id)->first(['id', 'name','brand_id', 'images', 'price','booking_to','category_id']);
        $cityFrom = Brand::where('id', $product->brand_id)->first(['id', 'name']);
        $cityTo = Brand::where('id', $product->booking_to)->first(['id', 'name']);
       
       
        if($product){

           
            
            $cart_items[] = [
                'product_id' => $product_id[0],
                'name' => $product_id[5],
                'image' => $product->images[0],
                'quantity' => 1,
                'unit_ammount' => $product_id[4],
                'total_ammount' => $product_id[4],
                'cityFrom' => $cityFrom->name,
                'cityTo' => $cityTo->name,
                'cabModel' => $product_id[6],
                'date' => $product_id[3],
                'type' => $product_id[2],
                'time' => $product_id[1],
                'toll' => $product_id[7],
                'new_vehicle' => $product_id[8],
                'pet_friendly' => $product_id[9],
                'roof_career' => $product_id[10],
            ];
        }
      

        self::addCartItemsToCookie($cart_items);
        return count($cart_items);
    }

    static public function addItemsToCartLocal($product_id){       
       
      
        $cart_items = [];

            $product = Product::where('id', $product_id)->first(['id', 'name','brand_id', 'images', 'price','booking_to','category_id']);
            $cityFrom = Brand::where('id', $product->brand_id)->first(['id', 'name']);
            $cityTo = Brand::where('id', $product->booking_to)->first(['id', 'name']);
            
            
            if($product){
               
                $cart_items[] = [
                    'product_id' => $product_id[0],
                    'name' => $product_id[7],
                    'image' => $product->images[0],
                    'quantity' => 1,
                    'unit_ammount' => $product_id[6],
                    'total_ammount' => $product_id[6],
                    'cityFrom' => $cityFrom->name,
                    'cityTo' => $cityTo->name,
                    'cabModel' => $product_id[8],
                    'date' => $product_id[3],
                    'type' => $product_id[2],
                    'time' => $product_id[1],
                    'plan' => $product_id[4],
                    'car' => $product_id[5],
                    'toll' => $product_id[9],
                    'new_vehicle' => $product_id[10],
                    'pet_friendly' => $product_id[11],
                    'roof_career' => $product_id[12],
                ];
            }

        self::addCartItemsToCookie($cart_items);
        return count($cart_items);
    }

    static public function addItemsToCartSelfDrive($product_id){

      //dd($product_id);
       
        $cart_items = [];

        $product = Product::where('id', $product_id)->first(['id', 'name','brand_id', 'images', 'price','booking_to','category_id']);
        $cityFrom = Brand::where('id', $product->brand_id)->first(['id', 'name']);
        $cityTo = Brand::where('id', $product->booking_to)->first(['id', 'name']);
        $cabModel = Category::where('id', $product->category_id)->first(['id', 'name']);
        
      

        if($product){
            $cart_items[] = [
                'product_id' => $product_id[0],
                'name' => $product->name,
                'image' => $product->images[0],
                'quantity' => 1,
                'unit_ammount' => $product_id[6],
                'total_ammount' => $product_id[6],
                'cityFrom' => $cityFrom->name,
                'cityTo' => $cityTo->name,
                'cabModel' => $cabModel->name,
                'type' => $product_id[3],
                'dateTo' => $product_id[5],
                'date' => $product_id[4],
                'time' => $product_id[1],
                'endTime' => $product_id[2],
                'toll' => 0,
                'security' => $product_id[7],
            ];
        }

        self::addCartItemsToCookie($cart_items);
        return count($cart_items);
    }

    static public function addItemsToCartReturn($data){
      
        $cart_items = [];

        

        $product = Product::where('id', 5)->first(['id', 'name','brand_id', 'images', 'price','booking_to','category_id']);
        $cabModel = Category::where('id', $data[0])->first(['id', 'name','image']);
            
            
       
        if($product){
                $cart_items[] = [
                    'product_id' => 1686,
                    'name' => $cabModel->name,
                    'image' => $cabModel->image,
                    'quantity' => 1,
                    'unit_ammount' => $data[3],
                    'total_ammount' => $data[3],
                    'cityFrom' => $data[1],
                    'cityTo' => $data[2],
                    'date' => $data[4],
                    'dateTo' => $data[5],
                    'cabModel' => $cabModel->name,
                    'time' => $data[6],
                    'type' => 'return',
                    'TotalKm'=>$data[8],
                    'new_vehicle' => $data[9],
                    'pet_friendly' => $data[10],
                    'roof_career' => $data[11],
                    'toll' => 0

                ];
            }

       
        self::addCartItemsToCookie($cart_items);
        return count($cart_items);
    }

    static public function addItemsToCartWithQty($product_id, $qty=1){
        $cart_items = [];

        $product = Product::where('id', $product_id)->first(['id', 'name','price', 'images']);
            if($product){
                $cart_items[] = [
                    'product_id' => $product_id,
                    'name' => $product->name,
                    'image' => $product->images[0],
                    'quantity' => 1,
                    'unit_ammount' => $product->price,
                    'total_ammount' => $product->price
                ];
            }

            
        self::addCartItemsToCookie($cart_items);
        return count($cart_items);
    }

    // remove item from cart

    static public function removeCartItems($product_id){
        $cart_items = self::getCartItemsFromCookie();

        foreach($cart_items as $key => $item){
            if($item['product_id'] == $product_id){
                unset($cart_items[$key]);
            }
        }

        self::addCartItemsToCookie($cart_items);

        return $cart_items;
    }

    // add cart items to cookie

    static public function addCartItemsToCookie($cart_items){
        Cookie::queue('cart_items', json_encode(value: $cart_items), 60*24*30);
    }

    // clear cart items from cookie

    static public function clearCartItems($cart_items){
        Cookie::queue(Cookie::forget('cart_items'));
    }

    // get all cart items from cookie
    static public function getCartItemsFromCookie(){
        $cart_items = json_decode(Cookie::get('cart_items'), true);
        if(!$cart_items){
            $cart_items = [];
        }

        return $cart_items;
    }

    // increment item quantity

    static public function increamentQuntityToCartItem($product_id){
        $cart_items = self::getCartItemsFromCookie();

        foreach($cart_items as $key => $item){
            if($item['product_id'] == $product_id){
                $cart_items[$key]['quantity']++;
                $cart_items[$key]['total_ammount'] = $cart_items[$key]['quantity'] * $cart_items[$key]
                ['unit_ammount'];
            }
        }

        self::addCartItemsToCookie($cart_items);
        return $cart_items;
    }

    // decrement item quantity
    static public function decrementQuntityToCartItem($product_id){
        $cart_items = self::getCartItemsFromCookie();

        foreach ($cart_items as $key => $item) {
           if($item['product_id'] == $product_id){
            if($cart_items[$key]['quantity']> 1){
                $cart_items[$key]['quantity']--;
                $cart_items[$key]['total_ammount'] = $cart_items[$key]['quantity'] * $cart_items[$key]['unit_ammount'];
            }
           }
        }
        self::addCartItemsToCookie($cart_items);
        return $cart_items;
    }

    // calculate grand total

    static public function calculateGrandTotal($items){
        return array_sum(array_column($items, 'total_ammount'));
    }
}