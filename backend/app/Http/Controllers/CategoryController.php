<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {


        $query = Category::query();



        $query->orderBy('name', 'ASC');


        $categories = $query->get();

        return response()->json([
            'count' => $categories->count(),
            'data' => $categories
        ]);
    }




    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserCategories(Request $request)
    {

        // Get request parameters
        $page = $request->input('page', 1);
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $sortBy = $request->input('sortBy.0.key', 'categories.name'); // Default sort by id
        $order = $request->input('sortBy.0.order', 'asc'); // Default order ascending
        $search = $request->input('search', '');


        $query = Category::query();

        // Apply search filter if needed
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('categories.name', 'like', '%' . $search . '%');
            });
        }

        $query->orderBy('name', 'ASC');

        $user = $request->user();
        $query->where('created_by', $user->id);

        // Apply sorting
        $query->orderBy($sortBy, $order);


        // Apply pagination

        if ($request->paginate && $request->paginate != 'false') {

            $types = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $categoriesArray = $types->items();
            $totalCount = $types->total();
        }

        // Return response
        return response()->json([
            'count' => $totalCount,
            'categories' => $categoriesArray
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
            'name' => 'required|string|max:255|unique:categories',
        ]);

        $user = auth()->user();

        $validated['created_by'] = $user->id;

        $category = Category::create($validated);

        return response()->json($category);
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
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
        ]);

        $category = Category::findOrFail($id);
        $category->fill($request->except('newImages', 'removedImages'));
        $category->save();



        return response()->json($category);
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
        $category = Category::where('id', $id)->where('created_by', $user->id)->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found or you do not have permission to delete it'], 404);
        }

        // Delete the item itself
        $category->delete();

        return response()->json(['message' => 'Category and associated images deleted successfully']);
    }
}
