<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Usage;

class UsageController extends Controller
{



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserUsages(Request $request)
    {

        // Get request parameters
        $page = $request->input('page', 1);
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $sortBy = $request->input('sortBy.0.key', 'usages.name'); // Default sort by id
        $order = $request->input('sortBy.0.order', 'asc'); // Default order ascending
        $search = $request->input('search', '');


        $query = Usage::query();

        // Apply search filter if needed
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('usages.name', 'like', '%' . $search . '%');
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
            $usagesArray = $types->items();
            $totalCount = $types->total();
        }

        // Return response
        return response()->json([
            'count' => $totalCount,
            'usages' => $usagesArray
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
            'name' => 'required|string|max:255|unique:usages',
        ]);

        $user = auth()->user();

        $validated['created_by'] = $user->id;


        $usage = Usage::create($validated);

        return response()->json($usage);
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
            'name' => 'required|string|max:255|unique:usages,name,' . $id,
        ]);

        $usage = Usage::findOrFail($id);
        $usage->fill($request->except('newImages', 'removedImages'));
        $usage->save();



        return response()->json($usage);
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
        $usage = Usage::where('id', $id)->where('created_by', $user->id)->first();

        if (!$usage) {
            return response()->json(['message' => 'Usage not found or you do not have permission to delete it'], 404);
        }


        // Delete the item itself
        $usage->delete();

        return response()->json(['message' => 'Usage and associated images deleted successfully']);
    }
}
