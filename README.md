# Live-Search

Route:
======
<?php

use App\Http\Controllers\web\auth\AuthController;
use App\Http\Controllers\web\Product\ProductController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin/supplier'], function () {
    Route::get('all', [SupplierController::class, 'index'])->name('admin.supplier.index');
    Route::get('create', [SupplierController::class, 'create'])->name('admin.supplier.create');
    Route::post('store', [SupplierController::class, 'store'])->name('admin.supplier.store');
    Route::get('edit/{id}', [SupplierController::class, 'edit'])->name('admin.supplier.edit');
    Route::put('update/{id}', [SupplierController::class, 'update'])->name('admin.supplier.update');
    Route::get('delete/{id}', [SupplierController::class, 'delete'])->name('admin.supplier.delete');
    Route::get('search', [SupplierController::class, 'search'])->name('admin.supplier.search');
});


ProductController:
==================

<?php

namespace App\Http\Controllers\web\Product;
use PDF;
use Dompdf\Dompdf;
use App\Models\Unit;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use sirajcse\UniqueIdGenerator\UniqueIdGenerator;

class ProductController extends Controller
{
    //All Product
    // protected $baseUrl = 'http://103.145.138.100:8000/api/';

    public function index()
    {
        $product = Product::all();

        if ($product) {
            return view('admin.product.index', compact('product'));
        } else {
            // Handle the error
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
    }
    //Create Product
    // Show the form to create a new product
    public function create()
    {
        $categories = Category::all();
        $units = Unit::all();
        return view('admin.product.create', compact('categories', 'units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'Name' => 'required|max:100|unique:products,Name',
            'MinimumUnitValue' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
        ]);
        $response = Product::create( [
            'Code' => $request->Code ?? 'DefaultCode',
            'Name' => $request->Name,
            'Description' => $request->Description,
            'MinimumUnitValue' => $request->MinimumUnitValue,
            'category_id' => $request->category_id,
            'unit_id' => $request->unit_id,
            'EntryUserID' => 5, 
            'EntryDateTime' => now()->toDateTimeString(),
            'UpdateUserID' => $request->UpdateUserID,
            'UpdateDateTime' => $request->UpdateDateTime,
        ]);

        if ($response) {
            return redirect()->route('admin.product.index')->with('success', 'Product created successfully.');
        } else {
            return back()->withErrors(['message' => 'Failed to create product.']);
        }
    }

    public function edit($id)
    {
        $product = Product::find($id);
        return view('admin.product.edit', compact('product'));
    }

    public function update(Request $request, $id)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'Name' => 'required|unique:products,Name,' . $id,
            'Description' => 'required',
        ]);
    
        // Handle validation failures
        if ($validator->fails()) {
            return redirect()->route('admin.product.edit', ['id' => $id])
                ->withErrors($validator)
                ->withInput();
        }
    
        // Find the product or fail
        $product = Product::findOrFail($id);
    
        // Update product details
        $updateData = [
            'Name' => $request->Name,
            'Description' => $request->Description,
            'UpdateDateTime' => now(),
            'DataMode' => $request->DataMode, // Ensure this is handled if it's nullable
        ];
    
        // Attempt to update the product
        if ($product->update($updateData)) {
            return redirect()->route('admin.product.index')
                ->with('success', 'Product updated successfully');
        }
    
        // Handle failure case
        return redirect()->route('admin.product.index')
            ->with('error', 'Failed to update product');
    }

    public function delete($id)
    {
        // $product = Product::find($id);
        // if($product){
        //     $product->delete();
        // }
        // return redirect()->route('admin.product.index')->with('success', 'Product Deleted Successfully');

        $product = Product::findOrFail($id);

        if (!$product) {
            return redirect()->route('admin.product.index')->with('error', 'Product not found');
        }

        $product->DataMode = 'Delete';
        $product->save();

        return redirect()->route('admin.product.index')->with('success', 'Product deleted successfully');
    }

    public function search(Request $request)
    {
        if ($request->ajax()) {
            $query = $request->get('query');
            $output = '';

            if ($query != '') {
                $data = DB::table('products')
                    ->where('Name', 'like', '%' . $query . '%')
                    ->orWhere('Code', 'like', '%' . $query . '%')
                    ->orderBy('id', 'desc')
                    ->get();
            } else {
                $data = DB::table('products')
                    ->orderBy('id', 'desc')
                    ->get();
            }

            $total_row = $data->count();
            if ($total_row > 0) {
                foreach ($data as $row) {
                    $editUrl = route('admin.product.edit', ['id' => $row->id]);
                    $deleteUrl = route('admin.product.delete', ['id' => $row->id]);
                    $output .= '
                <tr>
                    <td>' . $row->id . '</td>
                    <td>' . $row->Code . '</td>
                    <td>' . $row->Name . '</td>
                    <td>' . $row->Description . '</td>
                    <td>' . $row->MinimumUnitValue . '</td>
                    <td>' . $row->category_id . '</td>
                    <td>' . $row->unit_id . '</td>
                    <td>' . $row->EntryUserID . '</td>
                    <td>' . $row->EntryDateTime . '</td>
                    <td>
                        <a href="' . $editUrl . '" class="btn btn-warning"><i class="fas fa-edit"></i></a>
                        <form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </td>
                </tr>';
                }
            } else {
                $output = '
            <tr>
                <td align="center" colspan="10">No Data Found</td>
            </tr>';
            }
            $data = array(
                'table_data'  => $output,
                'total_data'  => $total_row
            );
            return response()->json($data);
        }
    }

    public function generatePdf(Request $request)
    {
        // Fetch data from the API
        $baseUrl = 'http://103.145.138.100:8000/api/';
        $response = Http::get($baseUrl . 'product');

        if ($response->successful()) {
            $products = $response->json();
        } else {
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }

        // Generate PDF
        $pdf = new Dompdf();
        $pdf->loadHtml(view('admin.product.pdf', compact('products')));

        // (Optional) Setup paper size and orientation
        $pdf->setPaper('A4', 'portrait');

        // Render PDF (output)
        $pdf->render();

        // Download PDF
        return $pdf->stream('products_data.pdf');
    }
}


Index.blade.php
===============

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

