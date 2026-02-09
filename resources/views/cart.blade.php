<h1>Your Cart</h1>

@if(empty($cart))
    <p>Cart is empty</p>
@else
    @foreach($cart as $item)
        <div>
            <strong>{{ $item['name'] }}</strong><br>
            Price: ₹{{ $item['price'] }}<br>
            Quantity: {{ $item['quantity'] }}
        </div>
        <hr>
    @endforeach
@endif
