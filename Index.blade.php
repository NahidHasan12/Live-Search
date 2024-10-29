@extends('layouts.master')
@section('content')
    @if (Session::has('success'))
        <div class="col-md-10 alert alert-success mt-4">
            {{ Session::get('success') }}
        </div>
    @endif

    <div class="col-md-10 d-flex justify-content-between mt-4">
        <a href="{{ route('admin.product.create') }}" class="btn btn-dark">Create Product</a>

        {{--  <form action="{{ route('admin.product.search') }}" method="GET" class="form-inline mb-3">
            @csrf
            <input type="text" name="search" id="search" class="form-control" placeholder="Search..."
                value="{{ request('search') }}">
            <button type="submit" class="btn btn-primary ml-2">Search</button>
        </form>  --}}

        <div class="form-group">
            <div class="form-group">
                <input type="text" class="form-control" id="search" name="search" placeholder="Search products...">

            </div>
        </div>


        <a href="{{ route('admin.product.downloadPdf') }}" class="btn btn-success">Download PDF</a>


    </div>

    <div class="row justify-content-center mt-4">
        <div class="col-md-10 d-flex justify-content-center">
            <h1>All Products</h1>
        </div>
    </div>

    <div class="card-body">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Minimum Unit value</th>
                    <th>CategoryID</th>
                    <th>Default UnitID</th>
                    <th>Entry UserID</th>
                    <th>Entry DateTime</th>
                    <th>Data Mode</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($product as $products)
                    <tr>
                        <td>{{ $products->id }}</td>
                        <td>{{ $products->Code }}</td>
                        <td>{{ $products->Name }}</td>
                        <td>{{ $products->Description }}</td>
                        <td>{{ $products->MinimumUnitValue }}</td>
                        <td>{{ $products->category_id }}</td>
                        <td>{{ $products->unit_id }}</td>
                        <td>{{ $products->EntryUserID }}</td>
                        <td>{{ $products->EntryDateTime }}</td>
                        <td>{{ $products->DataMode }}</td>
                        <td>
                            <a class="btn btn-warning" href="{{ route('admin.product.edit', $products->id) }}"><i
                                    class="fas fa-edit"></i></a>
                            <a class="btn btn-danger" onclick="return confirm('Are you sure?')"
                                href="{{ route('admin.product.delete', $products->id) }}"><i
                                    class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div>
        Total Records: <span id="total_records"></span>
    </div>



    <script>
        $(document).ready(function() {

            fetch_product_data();

            function fetch_product_data(query = '') {
                $.ajax({
                    url: "{{ route('admin.product.search') }}",
                    method: 'GET',
                    data: {
                        query: query
                    },
                    dataType: 'json',
                    success: function(data) {
                        $('tbody').html(data.table_data);
                        $('#total_records').text(data.total_data);
                    }
                });
            }

            $(document).on('keyup', '#search', function() {
                var query = $(this).val();
                fetch_product_data(query);
            });
        });
    </script>


@endsection
