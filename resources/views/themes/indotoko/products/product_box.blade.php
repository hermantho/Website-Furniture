<div class="col-lg-3 col-6">
    <div class="card card-product card-body p-lg-4 p3">
        <a href="{{ shop_product_link($product) }}">
        @if ($product->name == 'MACY SIDE CHAIR BLACK')
                    <img src="{{ asset('img/macy.jpg') }}" alt="{{ $product->name }}" class="img-fluid">
                @elseif ($product->name == 'CIELO SIDE CHAIR')
                    <img src="{{ asset('img/cielo.jpg') }}" alt="{{ $product->name }}" class="img-fluid">
                @elseif ($product->name == 'CORDA ARMCHAIR')
                    <img src="{{ asset('img/corda.jpg') }}" alt="{{ $product->name }}" class="img-fluid">
                    @elseif ($product->name == 'ARM CHAIR')
                    <img src="{{ asset('img/aruna.jpg') }}" alt="{{ $product->name }}" class="img-fluid">
                @elseif ($product->name == 'SCRIBBLE STUDY HIGH CHAIR')
                    <img src="{{ asset('img/study.jpg') }}" alt="{{ $product->name }}" class="img-fluid">
                @elseif ($product->name == 'SAHARA SIDE TABLE')
                    <img src="{{ asset('img/sahara.jpg') }}" alt="{{ $product->name }}" class="img-fluid">
                @else
                    <img src="{{ asset('img/cielo.jpg') }}" alt="{{ $product->name }}" class="img-fluid">
                @endif
    </a>
        <h3 class="product-name mt-3">{{ $product->name }}</h3>
        <div class="rating">
            <i class="bx bxs-star"></i>
            <i class="bx bxs-star"></i>
            <i class="bx bxs-star"></i>
            <i class="bx bxs-star"></i>
            <i class="bx bxs-star"></i>
        </div>
        <div class="detail d-flex justify-content-between align-items-center mt-4">
            <p class="price">IDR {{ $product->price_label }}</p>
            <a href="shop_product_link($product)" class="btn-cart"><i class="bx bx-cart-alt"></i></a>
        </div>
    </div>
</div>