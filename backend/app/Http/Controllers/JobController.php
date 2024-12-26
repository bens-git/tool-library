<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Package;
use App\Models\Material;
use App\Models\Vendor;
use App\Models\Country;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Get request parameters
        $page = $request->input('page', 1);
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $sortBy = $request->input('sortBy');
        $search = $request->input('search', '');

        // Base query for data
        $query = Job::with(['creator', 'material', 'product']);


        // Apply search filter if needed
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        // Apply sorting
        if ($sortBy) {
            foreach ($sortBy as $sort) {
                $key = $sort['key'] ?? 'id';
                $order = strtolower($sort['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

                $query->orderBy($key, $order);
            }
        }

        // Apply pagination
        if ($itemsPerPage == -1) {
            $jobArray = $query->get()->toArray(); // Get all items without pagination
            $total = count($jobArray);
        } else {
            $jobs = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $jobArray = $jobs->items();
            $total = $jobs->total();
        }


        // Return response
        return response()->json([
            'count' => $total,
            'data' => $jobArray
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
            'name' => 'required|string|max:255',
            'material_id' => 'required|integer|exists:materials,id',
            'product_id' => 'required|integer|exists:materials,id',
        ]);
        $job = DB::transaction(function () use ($validated) {

            $user = auth()->user();
            $validated['creator_id'] = $user->id;

            $job = Job::create($validated);

            return $job;
        });

        return response()->json($job);
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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'material_id' => 'required|integer|exists:materials,id',
            'product_id' => 'required|integer|exists:materials,id',
        ]);

        $job = Job::findOrFail($id);
        $job->fill($validated);
        $job->save();


        return response()->json($job);
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
        $job = Job::where('id', $id)->where('creator_id', $user->id)->first();
        if (!$job) {
            return response()->json(['message' => 'Blueprint not found or you do not have permission to delete it'], 404);
        }


        // Delete the blueprint
        $job->delete();

        return response()->json(['message' => 'Job deleted successfully']);
    }

}
