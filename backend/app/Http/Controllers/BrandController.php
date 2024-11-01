<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Brand;
use App\Models\Category;
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
    public function getUserBrands(Request $request)
    {

        // Get request parameters
        $page = $request->input('page', 1);
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $sortBy = $request->input('sortBy.0.key', 'brands.name'); // Default sort by id
        $order = $request->input('sortBy.0.order', 'asc'); // Default order ascending
        $search = $request->input('search', '');


        $query = Brand::query();

        // Apply search filter if needed
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('brands.name', 'like', '%' . $search . '%');
            });
        }

        $query->orderBy('name', 'ASC');

        $user = $request->user();
        $query->where('created_by', $user->id);

        // Apply sorting
        $query->orderBy($sortBy, $order);


        // Apply pagination

        if ($request->paginate && $request->paginate != 'false') {

            $brands = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $brandsArray = $brands->items();
            $totalCount = $brands->total();
        }

        // Return response
        return response()->json([
            'count' => $totalCount,
            'brands' => $brandsArray
        ]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserBrands1(Request $request)
    {

        // Get request parameters
        $page = $request->input('page', 1);
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $sortBy = $request->input('sortBy.0.key', 'brands.name'); // Default sort by id
        $order = $request->input('sortBy.0.order', 'asc'); // Default order ascending
        $search = $request->input('search', '');

        dd($page);

        $query = Brand::query();

        // Apply search filter if needed
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('brands.name', 'like', '%' . $search . '%');
            });
        }

        $query->orderBy('name', 'ASC');

        $user = $request->user();
        $query->where('created_by', $user->id);

        // Apply sorting
        $query->orderBy($sortBy, $order);


        // Apply pagination

        if ($request->paginate && $request->paginate != 'false') {

            $brands = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $brandsArray = $brands->items();
            $totalCount = $brands->total();
        }

        // Return response
        return response()->json([
            'count' => $totalCount,
            'brands' => $brandsArray
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

        return response()->json($brand);
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



        return response()->json($brand);
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
            return response()->json(['message' => 'Brand not found or you do not have permission to delete it'], 404);
        }

        //check if there are related items
        $items = Item::where('brand_id', '=', $brand->id);
        if ($items->count() > 0) {
            return response()->json(['message' => 'There are ' . $items->count() . ' items associated with this brand. You must delete them first.'], 404);
        }

        // Delete the brand 
        $brand->delete();

        return response()->json(['message' => 'Brand deleted successfully']);
    }
}
