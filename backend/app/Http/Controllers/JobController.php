<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Archetype;

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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $job = Job::with(['creator', 'base', 'component', 'tool', 'product', 'projects'])->find($id);


        $productId = $job->product_id;

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


        // Combine archetypes with their images
        $job['images'] = $images[$job['product_id']] ?? null;



        // Return response
        return response()->json([
            'success' => true,
            'data' => $job
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
                $projects = collect($validated['projects']);

                $attachments = $projects->mapWithKeys(function ($project) use ($job) {
                    // Get the highest order for this project in the pivot table
                    $maxOrder = DB::table('project_job')
                        ->where('project_id', $project['id'])
                        ->max('order') ?? 0; // Default to 0 if no order exists

                    return [
                        $project['id'] => [
                            'order' => $project['order'] ?? ($maxOrder + 1) // Use provided order or increment maxOrder
                        ]
                    ];
                });


                $job->projects()->attach($attachments);
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


        DB::transaction(function () use ($request, $id) {

            $validated = $request->validate([
                'newIntermediateProduct' => [
                    'required',
                    'array'
                ],
                'newIntermediateProduct.id' => ['required', 'integer', 'exists:archetypes,id'],

                'newJob1' => [
                    'required',
                    'array',
                ],
                'newJob1.name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('jobs', 'name')->ignore($request['originalJob.id']), // Allows unchanged names
                ],
                'newJob1.base'   => 'required|array',
                'newJob1.base.id' => ['required', 'integer', 'exists:archetypes,id', 'different:product.id', 'different:tool.id',],
                'newJob1.product'   => 'required|array',
                'newJob1.product.id' => ['required', 'integer', 'exists:archetypes,id', 'different:base.id', 'different:component.id', 'different:tool.id',],
                'newJob1.component'   => 'nullable|array',
                'newJob1.component.id' => ['nullable', 'integer', 'exists:archetypes,id', 'different:product.id', 'different:tool.id',],
                'newJob1.tool'   => 'nullable|array',
                'newJob1.tool.id' => ['nullable', 'integer', 'exists:archetypes,id', 'different:product.id', 'different:component.id', 'different:base.id',],
                'newJob1.projects'   => 'nullable|array',
                'newJob1.projects.*.id' => 'nullable|integer|exists:projects,id', // Validate each job ID


                'newJob2' => [
                    'required',
                    'array',
                ],
                'newJob2.name' => [
                    'required',
                    'string',
                    'max:255',
                    'different:newJob1.name',
                    Rule::unique('jobs', 'name')->ignore($request['originalJob.id']), // Allows unchanged names
                ],
                'newJob2.base'   => 'required|array',
                'newJob2.base.id' => ['required', 'integer', 'exists:archetypes,id', 'different:product.id', 'different:tool.id',],
                'newJob2.product'   => 'required|array',
                'newJob2.product.id' => ['required', 'integer', 'exists:archetypes,id', 'different:base.id', 'different:component.id', 'different:tool.id',],
                'newJob2.component'   => 'nullable|array',
                'newJob2.component.id' => ['nullable', 'integer', 'exists:archetypes,id', 'different:product.id', 'different:tool.id',],
                'newJob2.tool'   => 'nullable|array',
                'newJob2.tool.id' => ['nullable', 'integer', 'exists:archetypes,id', 'different:product.id', 'different:component.id', 'different:base.id',],
                'newJob2.projects'   => 'nullable|array',
                'newJob2.projects.*.id' => 'nullable|integer|exists:projects,id', // Validate each job ID


                'originalJob' => [
                    'required',
                    'array',
                ],

            ]);
            $user = Auth::user();

            $newIntermediateArchetype = Archetype::findOrFail($validated['newIntermediateProduct']['id']);
            Log::info('test' . $validated['originalJob']['id']);

            $originalJob = Job::where('id', $validated['originalJob']['id'])->where('created_by', $user->id)->firstOrFail();

            $base1Id = $validated['newJob1']['base']['id'];
            $product1Id = $validated['newJob1']['product']['id'];

            if (isset($validated['newJob1']['component'])) {
                $component1Data = $validated['newJob1']['component'];
            }

            if (isset($validated['newJob1']['tool'])) {
                $tool1Data = $validated['newJob1']['tool'];
            }
            $newJob1 = Job::create([
                'name' => $validated['newJob1']['name'],
                'base_id' => $base1Id,
                'product_id' => $product1Id,
                'component_id' => $component1Data['id'] ?? null,
                'tool_id' =>  $tool1Data['id'] ?? null,
                'description' => $validated['newJob1']['description'] ?? null,
                'created_by' => $user->id

            ]);

            $base2Id = $validated['newJob2']['base']['id'];
            $product2Id = $validated['newJob2']['product']['id'];

            if (isset($validated['newJob2']['component'])) {
                $component2Data = $validated['newJob2']['component'];
            }

            if (isset($validated['newJob2']['tool'])) {
                $tool2Data = $validated['newJob2']['tool'];
            }
            $newJob2 = Job::create([
                'name' => $validated['newJob2']['name'],
                'base_id' => $base2Id,
                'product_id' => $product2Id,
                'component_id' => $component2Data['id'] ?? null,
                'tool_id' =>  $tool2Data['id'] ?? null,
                'description' => $validated['newJob2']['description'] ?? null,
                'created_by' => $user->id

            ]);

            $originalJob->load('projects');

            $syncData1 = [];
            foreach ($originalJob->projects as $project) {
                $syncData1[$project->id] = ['order' => $project->pivot->order ?? 1]; // Include 'order' field
            }

            $syncData2 = [];
            foreach ($originalJob->projects as $project) {
                $syncData2[$project->id] = ['order' => $project->pivot->order ? $project->pivot->order + 1 : 2]; // Include 'order' field
            }

            // Sync projects with order field
            $newJob1->projects()->sync($syncData1);
            $newJob2->projects()->sync($syncData2);


            $originalJob->delete();


            return true;
        });

        $response['success'] = true;
        $response['message'] = 'Job subdivided';

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

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('jobs')->ignore($id), // Allows unchanged names
            ],
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
