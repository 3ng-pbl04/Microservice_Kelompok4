<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    /**
     * Get correlation ID from request context
     */
    private function getCorrelationId($request = null)
    {
        if ($request && $request->attributes->has('correlation_id')) {
            return $request->attributes->get('correlation_id');
        }
        // Fallback dari context yang di-set middleware
        return null;
    }
    public function index(Request $request)
    {
        try {
            $correlationId = $this->getCorrelationId($request);
            Log::info('Get Product List', ['correlation_id' => $correlationId]);
            $products = Product::all();
            
            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully',
                'data' => $products
            ]);
            
        } catch (\Exception $e) {
            $correlationId = $this->getCorrelationId($request);
            Log::error('Error fetching product list', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:products,name',
                'price' => 'required|numeric|min:0|max:999999999.99',
                'stock' => 'required|integer|min:0',
                'description' => 'nullable|string'
            ], [
                'name.required' => 'Nama produk wajib diisi',
                'name.string' => 'Nama produk harus berupa teks',
                'name.max' => 'Nama produk maksimal 255 karakter',
                'name.unique' => 'Nama produk sudah digunakan',
                'price.required' => 'Harga produk wajib diisi',
                'price.numeric' => 'Harga harus berupa angka',
                'price.min' => 'Harga tidak boleh kurang dari 0',
                'price.max' => 'Harga terlalu besar',
                'stock.required' => 'Stok produk wajib diisi',
                'stock.integer' => 'Stok harus berupa bilangan bulat',
                'stock.min' => 'Stok tidak boleh kurang dari 0',
                'description.string' => 'Deskripsi harus berupa teks'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();
            
            $product = Product::create($validatedData);
            
            $correlationId = $this->getCorrelationId($request);
            Log::info('Product Created', [
                'correlation_id' => $correlationId,
                'id' => $product->id,
                'name' => $product->name
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product
            ], Response::HTTP_CREATED);
            
        } catch (ValidationException $e) {
            $correlationId = $this->getCorrelationId($request);
            Log::warning('Validation failed for product creation', [
                'correlation_id' => $correlationId,
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
            
        } catch (QueryException $e) {
            $correlationId = $this->getCorrelationId($request);
            Log::error('Database error while creating product', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            
            $errorMessage = 'Database error occurred';
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            
            // Handle specific database errors
            if ($e->getCode() == 22003) { // Numeric value out of range
                $errorMessage = 'Price value is too large';
                $statusCode = Response::HTTP_BAD_REQUEST;
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => config('app.debug') ? $e->getMessage() : null
            ], $statusCode);
            
        } catch (\Exception $e) {
            $correlationId = $this->getCorrelationId($request);
            Log::error('Unexpected error while creating product', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            // Validasi ID
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid product ID format'
                ], Response::HTTP_BAD_REQUEST);
            }

            $product = Product::find($id);
            
            if (!$product) {
                $correlationId = $this->getCorrelationId($request);
                Log::warning('Product not found', ['correlation_id' => $correlationId, 'id' => $id]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $correlationId = $this->getCorrelationId($request);
            Log::info('Get Detail Product', ['correlation_id' => $correlationId, 'id' => $id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Product retrieved successfully',
                'data' => $product
            ]);
            
        } catch (\Exception $e) {
            $correlationId = $this->getCorrelationId($request);
            Log::error('Error fetching product detail', [
                'correlation_id' => $correlationId,
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Validasi ID
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid product ID format'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Cek apakah produk ada
            $product = Product::find($id);
            if (!$product) {
                $correlationId = $this->getCorrelationId($request);
                Log::warning('Product not found (update)', ['correlation_id' => $correlationId, 'id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Validasi data input
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255|unique:products,name,' . $id,
                'price' => 'sometimes|required|numeric|min:0|max:999999999.99',
                'stock' => 'sometimes|required|integer|min:0',
                'description' => 'nullable|string'
            ], [
                'name.required' => 'Nama produk wajib diisi',
                'name.string' => 'Nama produk harus berupa teks',
                'name.max' => 'Nama produk maksimal 255 karakter',
                'name.unique' => 'Nama produk sudah digunakan',
                'price.required' => 'Harga produk wajib diisi',
                'price.numeric' => 'Harga harus berupa angka',
                'price.min' => 'Harga tidak boleh kurang dari 0',
                'price.max' => 'Harga terlalu besar',
                'stock.required' => 'Stok produk wajib diisi',
                'stock.integer' => 'Stok harus berupa bilangan bulat',
                'stock.min' => 'Stok tidak boleh kurang dari 0',
                'description.string' => 'Deskripsi harus berupa teks'
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validatedData = $validator->validated();
            
            // Simpan data lama untuk logging
            $oldData = $product->toArray();
            
            $product->update($validatedData);
            
            $correlationId = $this->getCorrelationId($request);
            Log::info('Product Updated', [
                'correlation_id' => $correlationId,
                'id' => $id,
                'old_data' => $oldData,
                'new_data' => $validatedData
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->fresh()
            ]);
            
        } catch (ValidationException $e) {
            $correlationId = $this->getCorrelationId($request);
            Log::warning('Validation failed for product update', [
                'correlation_id' => $correlationId,
                'id' => $id,
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
            
        } catch (QueryException $e) {
            $correlationId = $this->getCorrelationId($request);
            Log::error('Database error while updating product', [
                'correlation_id' => $correlationId,
                'id' => $id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            
            $errorMessage = 'Database error occurred while updating product';
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            
            if ($e->getCode() == 22003) { // Numeric value out of range
                $errorMessage = 'Price value is too large';
                $statusCode = Response::HTTP_BAD_REQUEST;
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => config('app.debug') ? $e->getMessage() : null
            ], $statusCode);
            
        } catch (\Exception $e) {
            $correlationId = $this->getCorrelationId($request);
            Log::error('Unexpected error while updating product', [
                'correlation_id' => $correlationId,
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            // Validasi ID
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid product ID format'
                ], Response::HTTP_BAD_REQUEST);
            }

            $product = Product::find($id);
            
            if (!$product) {
                $correlationId = $this->getCorrelationId($request);
                Log::warning('Product not found for deletion', ['correlation_id' => $correlationId, 'id' => $id]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Simpan data untuk logging sebelum dihapus
            $productData = $product->toArray();
            
            $product->delete();
            
            $correlationId = $this->getCorrelationId($request);
            Log::info('Product Deleted', [
                'correlation_id' => $correlationId,
                'id' => $id,
                'deleted_data' => $productData
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
            
        } catch (QueryException $e) {
            $correlationId = $this->getCorrelationId($request);
            Log::error('Database error while deleting product', [
                'correlation_id' => $correlationId,
                'id' => $id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            
            $message = 'Failed to delete product';
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            
            // Foreign key constraint error
            if ($e->getCode() == 23000) {
                $message = 'Cannot delete product because it is referenced by other records';
                $statusCode = Response::HTTP_CONFLICT;
            }
            
            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => config('app.debug') ? $e->getMessage() : null
            ], $statusCode);
            
        } catch (\Exception $e) {
            $correlationId = $this->getCorrelationId($request);
            Log::error('Unexpected error while deleting product', [
                'correlation_id' => $correlationId,
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}