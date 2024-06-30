<?php

namespace Modules\Shop\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Shop\Repositories\Front\Interfaces\AddressRepositoryInterface;
use Modules\Shop\Repositories\Front\Interfaces\CartRepositoryInterface;
use Modules\Shop\Repositories\Front\Interfaces\OrderRepositoryInterface;

class OrderController extends Controller
{

    protected $addressRepository;
    protected $cartRepository;
    protected $orderRepository;

    public function __construct(AddressRepositoryInterface $addressRepository, CartRepositoryInterface $cartRepository, OrderRepositoryInterface $orderRepository)
    {
        $this->addressRepository = $addressRepository;
        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
    }
   
    public function checkout()
    {
        $this->data['cart'] = $this->cartRepository->findByUser(auth()->user());
        $this->data['addresses'] = $this->addressRepository->findByUser(auth()->user());
    
        return $this->loadTheme('orders.checkout', $this->data);
    }
    public function store(Request $request)
    {
        $request->validate([
            'address_id' => 'required|string',
            'courier' => 'required|string',
            'delivery_package' => 'required|string',
        ]);
    
        $address = $this->addressRepository->findByID($request->get('address_id'));
        if (!$address) {
            return redirect()->back()->withErrors(['address_id' => 'Alamat tidak ditemukan.']);
        }
    
        $cart = $this->cartRepository->findByUser(auth()->user());
        $selectedShipping = $this->getSelectedShipping($request);
    
        if (empty($selectedShipping)) {
            return redirect()->back()->withErrors(['error' => 'Pengiriman tidak tersedia.']);
        }
    
        DB::beginTransaction();
        try {
            $order = $this->orderRepository->create($request->user(), $cart, $address, $selectedShipping);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal membuat pesanan.']);
        }
    
        $this->cartRepository->clear(auth()->user());
    
        return redirect($order->payment_url);
    }
    
    private function getSelectedShipping(Request $request)
    {
        $address = $this->addressRepository->findByID($request->get('address_id'));
        $cart = $this->cartRepository->findByUser(auth()->user());
    
        $availableServices = $this->calculateShippingFee($cart, $address, $request->get('courier'));
    
        foreach ($availableServices as $service) {
            if ($service['service'] === $request->get('delivery_package')) {
                return [
                    'delivery_package' => $request->get('delivery_package'),
                    'courier' => $request->get('courier'),
                    'shipping_fee' => $service['cost'],
                ];
            }
        }
    
        return [];
    }
    

    public function shippingFee(Request $request)
    {
        $address = $this->addressRepository->findByID($request->get('address_id'));
        $cart = $this->cartRepository->findByUser(auth()->user());
        
        $availableServices = $this->calculateShippingFee($cart, $address, $request->get('courier'));
        return $this->loadTheme('orders.available_services', ['services' => $availableServices]);
    }

    public function choosePackage(Request $request)
    {
        $address = $this->addressRepository->findByID($request->get('address_id'));
        $cart = $this->cartRepository->findByUser(auth()->user());
        
        $availableServices = $this->calculateShippingFee($cart, $address, $request->get('courier'));

        $selectedPackage = null;
        if (!empty($availableServices)) {
            foreach ($availableServices as $service) {
                if ($service['service'] === $request->get('delivery_package')) {
                    $selectedPackage = $service;
                    continue;
                }
            }
        }

        if ($selectedPackage == null) {
            return [];
        }

        return [
            'shipping_fee' => number_format($selectedPackage['cost']),
            'grand_total' => number_format($cart->grand_total + $selectedPackage['cost']),
        ];
    }

    private function calculateShippingFee($cart, $address, $courier)
{
    $shippingFees = [];

    try {
        $response = Http::withHeaders([
            'key' => env('API_ONGKIR_KEY'),
        ])->post(env('API_ONGKIR_BASE_URL') . 'cost', [
            'origin' => env('API_ONGKIR_ORIGIN'),
            'destination' => $address->city,
            'weight' => $cart->total_weight,
            'courier' => $courier,
        ]);

        $shippingFees = json_decode($response->getBody(), true);

        if (isset($shippingFees['rajaongkir']['results'])) {
            foreach ($shippingFees['rajaongkir']['results'] as $cost) {
                if (!empty($cost['costs'])) {
                    foreach ($cost['costs'] as $costDetail) {
                        $availableServices[] = [
                            'service' => $costDetail['service'],
                            'description' => $costDetail['description'],
                            'etd' => $costDetail['cost'][0]['etd'],
                            'cost' => $costDetail['cost'][0]['value'],
                            'courier' => $courier,
                            'address_id' => $address->id,
                        ];
                    }
                }
            }
        }
    } catch (\Exception $e) {
        // Log the exception for debugging
        \Log::error('Shipping Fee Calculation Error: ' . $e->getMessage());
        return [];
    }

    return $availableServices;
}

}
