<?php

namespace App\Http\Controllers;

use App\Models\Job;


use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

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
        $archetypeId = $request->input('archetypeId');
        $projectId = $request->input('projectId');
        $baseId = $request->input('baseId');

        // Base query for data
        $query = Job::with(['creator', 'base', 'component', 'tool', 'product', 'projects']);


        // Apply search filter if needed
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        if (!empty($archetypeId)) {
            $query->where(function ($query) use ($archetypeId) {
                $query->where('base_id', '=',  $archetypeId);
                $query->orWhere('component_id', '=',  $archetypeId);
                $query->orWhere('product_id', '=',  $archetypeId);
            });
        }

        if (!empty($baseId)) {
            $query->where(function ($query) use ($baseId) {
                $query->where('base_id', '=',  $baseId);
            });
        }

        if (!empty($projectId)) {
            $query->where(function ($query) use ($projectId) {
                $query->where('project_id', '=',  $projectId);
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

        $productIds = array_column($jobArray, 'product_id');

        // Fetch random item images for archetypes missing archetype images
        $itemImages = DB::table('item_images')
            ->select('items.archetype_id', DB::raw('MIN(item_images.id) as id'), DB::raw('MIN(item_images.path) as path'))
            ->join('items', 'items.id', '=', 'item_images.item_id')
            ->whereIn('items.archetype_id', $productIds)
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


        // Combine archetypes with their images
        foreach ($jobArray as &$job) {
            $job['images'] = $images[$job['product_id']] ?? null;
        }


        // Return response
        return response()->json([
            'total' => $total,
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
            'name' => 'required|string|max:255|unique:jobs',
            'base'   => 'required|array',
            'base.id' => ['required', 'integer', 'exists:archetypes,id', 'different:product.id', 'different:tool.id',],
            'product'   => 'required|array',
            'product.id' => ['required', 'integer', 'exists:archetypes,id', 'different:base.id', 'different:component.id', 'different:tool.id',],
            'component'   => 'nullable|array',
            'component.id' => ['nullable', 'integer', 'exists:archetypes,id', 'different:product.id', 'different:tool.id',],
            'tool'   => 'nullable|array',
            'tool.id' => ['nullable', 'integer', 'exists:archetypes,id', 'different:product.id', 'different:component.id', 'different:base.id',],
            'projects'   => 'nullable|array',
            'projects.*.id' => 'nullable|integer|exists:projects,id', // Validate each job ID

        ], [
            'base.id.required' => 'There must be a base',
            'base.id.different' => 'The base must be different than the product and tool',
            'product.id.required' => 'There must be a product',
            'product.id.different' => 'The product must be different than the base component and tool',
            'projects.required' => 'There must be a project',
            'component.id.different' => 'The component must be different than the product and tool',
            'tool.id.different' => 'The tool must be different than the base, component and product',
        ]);
        $job = DB::transaction(function () use ($validated) {

            $user = Auth::user();

            $baseId = $validated['base']['id'];
            $productId = $validated['product']['id'];

            if (isset($validated['component'])) {
                $componentData = $validated['component'];
            }

            if (isset($validated['tool'])) {
                $toolData = $validated['tool'];
            }

            Log::info('Base Data:', ['base_id' => $baseId ?? null]);

            $job = Job::create([
                'name' => $validated['name'],
                'base_id' => $baseId,
                'product_id' => $productId,
                'component_id' => $componentData['id'] ?? null,
                'tool_id' =>  $toolData['id'] ?? null,
                'description' => $validated['description'] ?? null,
                'created_by' => $user->id
            ]);


            // If projects are provided, attach them to the job
            if (!empty($validated['projects'])) {
                $projectIds = collect($validated['projects'])->pluck('id'); // Extract project IDs
                $job->projects()->attach($projectIds); // Attach projects to the job
            }



            return $job;
        });

        $response['data'] = $job;
        $response['success'] = true;
        $response['message'] = 'Job created';

        return response()->json($response);
    }




    /**
     * Subdivide a job into 2 jobs
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function subdivide(Request $request, $id)
    {



        $validated = $request->validate([
            'newIntermediateProduct' => [
                'required',
                'array'
            ],
            'newIntermediateProduct.id' => ['required', 'integer', 'exists:archetypes,id'],

            'newJobName1' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name')->ignore($id), // Allows unchanged names
            ],
            'newJobName2' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name'),
            ],
            'base_id' => 'required|integer|exists:archetypes,id|different:product_id|different:component_id',
            'product_id' => 'required|integer|exists:archetypes,id|different:base_id|different:component_id',
            'component_id' => 'nullable|integer|exists:archetypes,id|different:base_id|different:product_id',
            'tool_id' => 'nullable|integer|exists:archetypes,id|different:base_id|different:product_id|different:component_id',
            'projects'   => 'required|array',
            'projects.*.id' => 'required|exists:projects,id', // Validate each job ID

        ], [
            'base_id.required' => 'There must be a base',
            'product_id.required' => 'There must be a product',
        ]);
        $job = DB::transaction(function () use ($validated) {

            $user = Auth::user();

            $validated['created_by'] = $user->id;

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
            'name' => 'required|string|max:255|unique:jobs',
            'base'   => 'required|array',
            'base.id' => ['required', 'integer', 'exists:archetypes,id', 'different:product.id', 'different:tool.id',],
            'product'   => 'required|array',
            'product.id' => ['required', 'integer', 'exists:archetypes,id', 'different:base.id', 'different:component.id', 'different:tool.id',],
            'component'   => 'nullable|array',
            'component.id' => ['nullable', 'integer', 'exists:archetypes,id', 'different:product.id', 'different:tool.id',],
            'tool'   => 'nullable|array',
            'tool.id' => ['nullable', 'integer', 'exists:archetypes,id', 'different:product.id', 'different:component.id', 'different:base.id',],
            'projects'   => 'nullable|array',
            'projects.*.id' => 'nullable|integer|exists:projects,id', // Validate each job ID

        ], [
            'base.id.required' => 'There must be a base',
            'base.id.different' => 'The base must be different than the product and tool',
            'product.id.required' => 'There must be a product',
            'product.id.different' => 'The product must be different than the base component and tool',
            'projects.required' => 'There must be a project',
            'component.id.different' => 'The component must be different than the product and tool',
            'tool.id.different' => 'The tool must be different than the base, component and product',
        ]);

        $job = DB::transaction(function () use ($id, $validated) {

            $user = Auth::user();

            $baseId = $validated['base']['id'];
            $productId = $validated['product']['id'];

            if (isset($validated['component'])) {
                $componentData = $validated['component'];
            }

            if (isset($validated['tool'])) {
                $toolData = $validated['tool'];
            }

            Log::info('Base Data:', ['base_id' => $baseId ?? null]);
            $job = Job::findOrFail($id);
            $job->name = $validated['name'];
            $job->base_id = $baseId;
            $job->product_id = $productId;
            $job->component_id = $componentData['id'] ?? null;
            $job->tool_id =  $toolData['id'] ?? null;
            $job->description = $validated['description'] ?? null;
            $job->created_by = $user->id;
            $job->save();


            // If there are projects to sync, do so
            if (isset($validated['projects']) && is_array($validated['projects'])) {
                // Extract the project IDs from the validated data
                $projectIds = array_map(function ($project) {
                    return $project['id'];
                }, $validated['projects']);

                // Sync the projects to the job
                $job->projects()->sync($projectIds);
            }

            return $job;
        });

        return response()->json(['success' => true, 'message' => 'Job updated', 'data' => $job]);
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
        $job = Job::where('id', $id)->where('created_by', $user->id)->first();
        if (!$job) {
            return response()->json(['message' => 'Job not found or you do not have permission to delete it'], 404);
        }


        // Delete the blueprint
        $job->delete();

        return response()->json(['message' => 'Job deleted successfully']);
    }
}
