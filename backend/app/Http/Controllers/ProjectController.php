<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
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
        $query = Project::with(['creator', 'jobs', 'jobs.base', 'jobs.component', 'jobs.product', 'finalJob']);


        // Apply search filter if needed
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }



        // Apply pagination
        if ($itemsPerPage == -1) {
            $projectArray = $query->get()->toArray(); // Get all items without pagination
            $total = count($projectArray);
        } else {
            $projects = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $projectArray = $projects->items();
            $total = $projects->total();
        }

        $productIds = [];
        foreach ($projectArray as $project) {
            $finalJob = $project->finalJob()->first();

            if ($finalJob) {
                $productIds[] = $finalJob->product_id;
            }
        }


        // Fetch random item images for archetypes missing archetype images
        $itemImages = DB::table('item_images')
            ->select('items.archetype_id', DB::raw('MIN(item_images.id) as id'), DB::raw('MIN(item_images.path) as path'))
            ->join('items', 'items.id', '=', 'item_images.item_id')
            ->whereIn('items.archetype_id', $productIds)
            ->groupBy('items.archetype_id')
            ->inRandomOrder()
            ->get()
            ->keyBy('archetype_id');

        // return($itemImages);


        $images = [];
        // Merge in the item images where archetype images are missing
        foreach ($itemImages as $archetypeId => $image) {
            if (!isset($images[$archetypeId])) {
                $images[$archetypeId] = [
                    [
                        'id' => $image->id,
                        'path' => '/storage/' . $image->path
                    ]
                ];
            }
        }


        // Combine archetypes with their images
        foreach ($projectArray as $project) {
            // $finalJob=Project::with('finalJob')
            $finalJob = $project->finalJob()->first();

            if ($finalJob && $finalJob->product_id) {

                if (isset($images[$finalJob->product_id])) {
                    $project['images'] = $images[$finalJob->product_id];
                }
            }
        }


        // Return response
        return response()->json([
            'total' => $total,
            'data' => $projectArray
        ]);
    }




    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $project = Project::with(['creator', 'jobs', 'jobs.base', 'jobs.component', 'jobs.product', 'finalJob'])->find($id);

        $productId = null;

        $finalJob = $project->finalJob()->first();

        if ($finalJob) {
            $productId = $finalJob->product_id;
        }

        // Fetch random item images for archetypes missing archetype images
        $itemImages = DB::table('item_images')
            ->select('items.archetype_id', DB::raw('MIN(item_images.id) as id'), DB::raw('MIN(item_images.path) as path'))
            ->join('items', 'items.id', '=', 'item_images.item_id')
            ->where('items.archetype_id', $productId)
            ->groupBy('items.archetype_id')
            ->inRandomOrder()
            ->get()
            ->keyBy('archetype_id');

        $images = [];
        // Merge in the item images where archetype images are missing
        foreach ($itemImages as $archetypeId => $image) {
            if (!isset($images[$archetypeId])) {
                $images[$archetypeId] = [
                    [
                        'id' => $image->id,
                        'path' => '/storage/' . $image->path
                    ]
                ];
            }
        }




        if ($finalJob && $finalJob->product_id) {

            if (isset($images[$finalJob->product_id])) {
                $project['images'] = $images[$finalJob->product_id];
            }
        }



        // Return response
        return response()->json([
            'success' => true,
            'data' => $project
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

        $data = $request->all();

        // Flatten the `pivot` keys in `jobs`
        if (isset($data['jobs']) && is_array($data['jobs'])) {
            foreach ($data['jobs'] as $index => $job) {
                if (isset($job['pivot']) && is_array($job['pivot'])) {
                    $data['jobs'][$index] = array_merge($job, $job['pivot']);
                    unset($data['jobs'][$index]['pivot']);
                }
            }
        }

        $validated = Validator::make($data, [
            'name' => 'required|string|max:255|unique:projects',
            'description' => 'nullable|string',
            'jobs'   => 'required|array',
            'jobs.*.id' => 'required|exists:jobs,id', // Validate each job ID
            'jobs.*.order' => 'required|integer|min:1',
        ], ['jobs.required' => 'There must be at least one job'])->validate();;

        $project = DB::transaction(function () use ($validated) {

            $user = Auth::user();

            $validated['created_by'] = $user->id;

            $project = Project::create($validated);

            $jobsWithOrder = [];
            foreach ($validated['jobs'] as $job) {
                $jobsWithOrder[$job['id']] = ['order' => $job['order']];
            }

            $project->jobs()->attach($jobsWithOrder);



            return $project;
        });


        $response['data'] = $project;
        $response['success'] = true;
        $response['message'] = 'Project created';

        return response()->json($response);
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

        $data = $request->all();

        // Flatten the `pivot` keys in `jobs`
        if (isset($data['jobs']) && is_array($data['jobs'])) {
            foreach ($data['jobs'] as $index => $job) {
                if (isset($job['pivot']) && is_array($job['pivot'])) {
                    $data['jobs'][$index] = array_merge($job, $job['pivot']);
                    unset($data['jobs'][$index]['pivot']);
                }
            }
        }

        $validated = Validator::make($data, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects')->ignore($id), // Allows unchanged names
            ],
            'description' => 'nullable|string',
            'jobs'   => 'required|array',
            'jobs.*.id' => 'required|exists:jobs,id', // Validate each job ID
            'jobs.*.order' => 'required|integer|min:1',
        ], ['jobs.required' => 'There must be at least one job'])->validate();
        $project = DB::transaction(function () use ($id, $validated) {

            $project = Project::findOrFail($id);

            $project->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            $project->jobs()->sync(
                collect($validated['jobs'])->mapWithKeys(fn($job) => [$job['id'] => ['order' => $job['order']]])
            );


            return $project;
        });


        $response['data'] = $project;
        $response['success'] = true;
        $response['message'] = 'Project updated';

        return response()->json($response);
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
        $project = Project::where('id', $id)->where('created_by', $user->id)->first();
        if (!$project) {
            return response()->json(['message' => 'Project not found or you do not have permission to delete it'], 404);
        }


        // Delete the blueprint
        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }
}
