<?php

namespace App\Http\Controllers\web\Product;

use PDF;
use Dompdf\Dompdf;
use App\Models\Unit;
use App\Models\Product;
use App\Models\Category;
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
