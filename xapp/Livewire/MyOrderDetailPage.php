<?php

namespace App\Livewire;

use App\Models\address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vehicle;
use App\Models\User;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Carbon\Carbon;

#[Title("My Orders Details -  Duracabs")]
class MyOrderDetailPage extends Component
{
    use LivewireAlert;
    
    public $order_id;
    public $order;
    public $showCancelModal = false;
    public $cancelReason = '';
    public $showModificationModal = false;
    public $modificationDetails = '';

    public function mount($order_id){
        $this->order_id = $order_id;
        $this->order = Order::with(['user', 'vehicle', 'address'])->findOrFail($order_id);
        
        // Check if user owns this order
        if ($this->order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to order details.');
        }
    }

    public function getStatusColor($status) {
        return match($status) {
            'new' => 'bg-blue-500',
            'reconfirmation' => 'bg-yellow-500',
            'confirm' => 'bg-green-500',
            'modification' => 'bg-orange-500',
            'start' => 'bg-indigo-500',
            'closed' => 'bg-gray-500',
            'cancelled' => 'bg-red-500',
            'refund' => 'bg-red-600',
            default => 'bg-gray-400'
        };
    }

    public function getStatusText($status) {
        return match($status) {
            'new' => 'New Booking',
            'reconfirmation' => 'Reconfirmation',
            'confirm' => 'Confirmed',
            'modification' => 'Modified',
            'start' => 'Trip Started',
            'closed' => 'Completed',
            'cancelled' => 'Cancelled',
            'refund' => 'Refunded',
            default => ucfirst($status)
        };
    }

    public function canCancel() {
        return in_array($this->order->status, ['new', 'reconfirmation', 'confirm']) && 
               Carbon::parse($this->order->date)->greaterThan(Carbon::now()->addHours(24));
    }

    public function canModify() {
        return in_array($this->order->status, ['new', 'reconfirmation', 'confirm']) && 
               Carbon::parse($this->order->date)->greaterThan(Carbon::now()->addHours(48));
    }

    public function canRebook() {
        return in_array($this->order->status, ['cancelled', 'closed']);
    }

    public function showCancelDialog() {
        if (!$this->canCancel()) {
            $this->alert('error', 'Cannot cancel this order at this time.');
            return;
        }
        $this->showCancelModal = true;
    }

    public function cancelOrder() {
        $this->validate([
            'cancelReason' => 'required|string|min:10|max:500'
        ]);

        $this->order->update([
            'status' => 'cancelled',
            'notes' => 'Cancelled by customer: ' . $this->cancelReason
        ]);

        $this->showCancelModal = false;
        $this->cancelReason = '';
        
        $this->alert('success', 'Order cancelled successfully!', [
            'position' => 'center',
            'timer' => 3000,
            'toast' => true,
        ]);
        
        $this->order->refresh();
    }

    public function showModificationDialog() {
        if (!$this->canModify()) {
            $this->alert('error', 'Cannot modify this order at this time.');
            return;
        }
        $this->showModificationModal = true;
    }

    public function requestModification() {
        $this->validate([
            'modificationDetails' => 'required|string|min:10|max:500'
        ]);

        $this->order->update([
            'status' => 'modification',
            'notes' => 'Modification requested: ' . $this->modificationDetails
        ]);

        $this->showModificationModal = false;
        $this->modificationDetails = '';
        
        $this->alert('success', 'Modification request sent successfully!', [
            'position' => 'center',
            'timer' => 3000,
            'toast' => true,
        ]);
        
        $this->order->refresh();
    }

    public function rebookOrder() {
        // Redirect to homepage with pre-filled data
        $params = [
            'tab' => $this->order->ride_type,
            'cityFrom' => $this->order->cityFrom,
            'cityTo' => $this->order->cityTo,
            'nameTo' => $this->order->booking_from,
            'nameFrom' => $this->order->booking_to,
            'plan' => $this->order->plan,
        ];

        return redirect()->to('/' . '?' . http_build_query($params));
    }

    public function downloadInvoice() {
        // This would generate and download invoice
        $this->alert('info', 'Invoice download feature coming soon!');
    }

    public function getRideTypeDetails() {
        $details = [];
        
        switch($this->order->ride_type) {
            case 'one_way':
                $details = [
                    'title' => 'One Way Trip',
                    'icon' => 'M13 7l5 5-5 5M6 12h12',
                    'fields' => [
                        'From' => $this->order->booking_from,
                        'To' => $this->order->booking_to,
                        'Date' => $this->order->date ? Carbon::parse($this->order->date)->format('M d, Y') : '',
                        'Time' => $this->order->time,
                    ]
                ];
                break;
                
            case 'return':
                $details = [
                    'title' => 'Round Trip',
                    'icon' => 'M8 7l4-4m0 0l4 4m-4-4v18',
                    'fields' => [
                        'From' => $this->order->booking_from,
                        'To' => $this->order->booking_to,
                        'Start Date' => $this->order->date ? Carbon::parse($this->order->date)->format('M d, Y') : '',
                        'End Date' => $this->order->dateTo ? Carbon::parse($this->order->dateTo)->format('M d, Y') : '',
                        'Time' => $this->order->time,
                    ]
                ];
                break;
                
            case 'local':
                $details = [
                    'title' => 'Local Trip',
                    'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
                    'fields' => [
                        'Location' => $this->order->booking_from,
                        'Plan' => $this->order->plan,
                        'Date' => $this->order->date ? Carbon::parse($this->order->date)->format('M d, Y') : '',
                        'Time' => $this->order->time,
                    ]
                ];
                break;
                
            case 'self_drive':
                $details = [
                    'title' => 'Self Drive',
                    'icon' => 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z',
                    'fields' => [
                        'Location' => $this->order->booking_from,
                        'Start Date' => $this->order->date ? Carbon::parse($this->order->date)->format('M d, Y') : '',
                        'End Date' => $this->order->dateTo ? Carbon::parse($this->order->dateTo)->format('M d, Y') : '',
                        'Start Time' => $this->order->time,
                        'End Time' => $this->order->endTime,
                    ]
                ];
                break;
                
            default:
                $details = [
                    'title' => 'Trip Details',
                    'icon' => 'M12 14l9-5-9-5-9 5 9 5z',
                    'fields' => []
                ];
        }
        
        return $details;
    }

    public function render()
    {
        $order_items = OrderItem::with('product')->where('order_id', $this->order_id)->get();
        $address = address::where('order_id', $this->order_id)->first();
        $rideDetails = $this->getRideTypeDetails();
        
        return view('livewire.my-order-detail-page', [
            'order_items' => $order_items,
            'address' => $address,
            'order' => $this->order,
            'rideDetails' => $rideDetails,
        ]);
    }
}
