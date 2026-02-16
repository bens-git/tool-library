<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Brand;
use Illuminate\Validation\Rule;


class BrandController extends Controller
{

    // Fetch all brands
    public function getUsages(Request $request)
    {
        $query = Brand::query();
        $query->orderBy('name', 'ASC');

        if ($request->path() == 'api/user/brands') {
            $user = $request->user();
            $query->where('created_by', $user->id);
        }

        $brands = $query->get();

        return response()->json([
            'count' => $brands->count(),
            'data' => $brands
        ]);
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        // Get request parameters
        $brandId = $request->input('brandId');
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $page = $request->input('page', 1);
        $search = $request->input('search', '');
        $sortBy = $request->input('sortBy');


        $query = Brand::query();

        // Apply search filter if needed
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('brands.name', 'like', '%' . $search . '%');
            });
        }

        // Check if the path is 'me/items' and filter by user if so
        if ($request->path() == 'api/me/brands') {
            $user = $request->user();
            $query->where('created_by', $user->id);
        }

        // Apply sorting
        if ($sortBy) {
            foreach ($sortBy as $sort) {
                $key = $sort['key'] ?? 'id';
                $order = strtolower($sort['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

                $query->orderBy($key, $order);
            }
        }

        if ($request->itemsPerPage == -1) {
            $brandsArray = $query->get()->toArray();
            $totalCount = count($brandsArray);
        } else {
            $brands = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $brandsArray = $brands->items();
            $totalCount = $brands->total();
        }

        // Return response
        return response()->json([
            'total' => $totalCount,
            'data' => $brandsArray
        ]);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands',
        ]);

        $user = auth()->user();

        $validated['created_by'] = $user->id;

        $brand = Brand::create($validated);

        return response()->json(['success' => true, 'data' => $brand, 'message' => 'Brand created']);
    }




    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $brand = Brand::findOrFail($id);

        // Validate the request
        $request->validate([
            'name' => [
                'required',
                Rule::unique('brands')->ignore($brand->id), // Allow current name if unchanged
            ],
        ]);

        // Update the brand name
        $brand->name = $request->name;
        $brand->save();

        return response()->json(['success' => true, 'data' => $brand, 'message' => 'Brand created']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $brand = Brand::where('id', $id)->where('created_by', $user->id)->first();

        if (!$brand) {
            return response()->json(['success' => false, 'message' => 'Brand not found or you do not have permission to delete it'], 404);
        }

      
        // Delete the brand 
        $brand->delete();

        return response()->json(['message' => 'Brand deleted successfully']);
    }
}
