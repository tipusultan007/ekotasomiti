<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoanProduct;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanProductController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(LoanProduct::class, 'product');
    }

    public function index()
    {
        $products = LoanProduct::withCount('loans')->orderBy('code')->get();

        return view('loans.products.index', compact('products'));
    }

    public function create()
    {
        return view('loans.products.form', ['product' => new LoanProduct]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $product = LoanProduct::create($data);
        AuditLog::record('loan_product.created', $product, [], $product->toArray());

        return redirect()->route('loans.products.index')->with('success', __('Loan product created successfully.'));
    }

    public function edit(LoanProduct $product)
    {
        return view('loans.products.form', compact('product'));
    }

    public function update(Request $request, LoanProduct $product)
    {
        $data = $this->validateData($request);

        $before = $product->toArray();
        $product->update($data);
        AuditLog::record('loan_product.updated', $product, $before, $product->toArray());

        return redirect()->route('loans.products.index')->with('success', __('Loan product updated successfully.'));
    }

    public function destroy(LoanProduct $product)
    {
        if ($product->loans()->exists()) {
            return back()->with('error', __('Product cannot be deleted because it has loans.'));
        }

        AuditLog::record('loan_product.deleted', $product, $product->toArray(), []);
        $product->delete();

        return redirect()->route('loans.products.index')->with('success', __('Loan product deleted successfully.'));
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('loan_products', 'code')->ignore($request->route('product'))],
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly',
            'prefix' => 'required|string|max:10',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'interest_type' => 'required|in:flat,reducing',
            'processing_fee' => 'nullable|numeric|min:0',
            'insurance_fee' => 'nullable|numeric|min:0',
            'min_term' => 'nullable|integer|min:1',
            'max_term' => 'nullable|integer|min:1',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
        ]);
    }
}