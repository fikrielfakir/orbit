<table>
    <thead>
        <tr>
            <th>Product Name</th>
            <th>Quantity</th>

            <th>Location</th>
            <th>Transaction Type</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($report as $item)
        <tr>
            <td>{{ $item->product_name }}</td>
            <td>{{ $item->quantity }}</td>
  
            <td>{{ $item->location_name }}</td>
            <td>{{ $item->type}}</td>
        </tr>
        @endforeach
    </tbody>
</table>
