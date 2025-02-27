<?php

namespace App\Http\Controllers;

use App\Models\ResourceArchetype;
use App\Models\Item;
use App\Models\Usage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\ResourceArchetypeImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ResourceArchetypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getResourceArchetypesWithItems(Request $request)
    {
        // Get request parameters
        $page = $request->input('page', 1);
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $sortBy = $request->input('sortBy.0.key', 'resource_archetypes.id'); // Default sort by id
        $order = $request->input('sortBy.0.order', 'asc'); // Default order ascending
        $search = $request->input('search', '');
        $radius = $request->input('radius');
        $latitude = $request->input('location.lat');
        $longitude = $request->input('location.lng');
        $startDate = $request->input('startDate', '1970-01-01'); // Default to Unix epoch or a sufficiently past date
        $endDate = $request->input('endDate', '1970-01-01');
        $resource = [$request->input('resource', 'TOOL'), 'ANY'];


        // Base query for data
        $query = ResourceArchetype::query()
            ->leftJoin('category_resource_archetype', 'resource_archetypes.id', '=', 'category_resource_archetype.resource_archetype_id')
            ->leftJoin('categories', 'category_resource_archetype.category_id', '=', 'categories.id')
            ->leftJoin('resource_archetype_usage', 'resource_archetypes.id', '=', 'resource_archetype_usage.resource_archetype_id')
            ->leftJoin('usages', 'resource_archetype_usage.usage_id', '=', 'usages.id')
            ->leftJoin('items', 'items.resource_archetype_id', '=', 'resource_archetypes.id')
            ->leftJoin('brands', 'items.brand_id', '=', 'brands.id')
            ->leftJoin('users as owner', 'items.owned_by', '=', 'owner.id')
            ->leftJoin('locations as owner_location', 'owner.location_id', '=', 'owner_location.id');
        $query->leftJoin('rentals', function ($join) use ($startDate, $endDate) {
            $join->on('items.id', '=', 'rentals.item_id')
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->where('rentals.starts_at', '<=', $endDate)
                        ->where('rentals.ends_at', '>=', $startDate);
                });
        });
        $query->leftJoin('users as renter', 'rentals.rented_by', '=', 'renter.id');
        $query->leftJoin('locations as renter_location', 'renter.location_id', '=', 'renter_location.id');

        $query->select(
            'resource_archetypes.id',
            'resource_archetypes.name',
            'resource_archetypes.created_by',
            DB::raw('GROUP_CONCAT(DISTINCT categories.name ORDER BY categories.name ASC SEPARATOR ", ") as categories'),
            DB::raw('GROUP_CONCAT(DISTINCT categories.id ORDER BY categories.id ASC SEPARATOR ", ") as category_ids'),
            DB::raw('GROUP_CONCAT(DISTINCT usages.name ORDER BY usages.name ASC SEPARATOR ", ") as usages'),
            DB::raw('GROUP_CONCAT(DISTINCT usages.id ORDER BY usages.id ASC SEPARATOR ", ") as usage_ids'),
            DB::raw('GROUP_CONCAT(DISTINCT brands.name ORDER BY brands.name ASC SEPARATOR ", ") as brand_names'),
            DB::raw('GROUP_CONCAT(DISTINCT brands.id ORDER BY brands.id ASC SEPARATOR ", ") as brand_ids'),


            // Subquery for available items count based on distance
            DB::raw('COUNT(DISTINCT CASE 
                            WHEN rentals.id IS NULL 
                            AND ST_Distance_Sphere(
                                point(owner_location.longitude, owner_location.latitude), 
                                point(?, ?)
                            ) <= ? 
                            THEN items.id 
                        END) as available_item_count'),
            DB::raw('COUNT(DISTINCT CASE 
                            WHEN rentals.id IS NOT NULL 
                            AND ST_Distance_Sphere(
                                point(renter_location.longitude, renter_location.latitude), 
                                point(?, ?)
                            ) <= ? 
                            THEN rentals.item_id 
                        END) as rented_item_count'),




            DB::raw('GROUP_CONCAT(DISTINCT items.id ORDER BY items.owned_by ASC SEPARATOR ", ") as item_ids'),
            DB::raw('GROUP_CONCAT(DISTINCT 
                            CASE 
                                WHEN rentals.id IS NULL THEN 
                                    CONCAT_WS(" ", COALESCE(owner_location.city, ""), COALESCE(owner_location.state, ""), COALESCE(owner_location.country, ""))
                                ELSE 
                                    CONCAT_WS(" ", COALESCE(renter_location.city, ""), COALESCE(renter_location.state, ""), COALESCE(renter_location.country, ""))
                            END
                        SEPARATOR "; ") as locations')
        );

        $query->groupBy('resource_archetypes.id');


        $distance = $radius * 1000;

        $query->addBinding([$longitude, $latitude, $distance, $longitude, $latitude, $distance], 'select');


        // Apply distance filter for items within the radius
        if (!empty($latitude) && !empty($longitude) && !empty($radius)) {

            // Apply the distance filtering in the WHERE clause
            $query->where(function ($q) use ($longitude, $latitude, $distance) {
                $q->whereRaw('ST_Distance_Sphere(
                                point(owner_location.longitude, owner_location.latitude), 
                                point(?, ?)
                            ) <= ?', [$longitude, $latitude, $distance])
                    ->orWhereRaw('ST_Distance_Sphere(
                                point(renter_location.longitude, renter_location.latitude), 
                                point(?, ?)
                            ) <= ?', [$longitude, $latitude, $distance]);
            });
        }

        // Apply search filter if needed
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('resource_archetypes.name', 'like', '%' . $search . '%')
                    ->orWhere('items.description', 'like', '%' . $search . '%');
            });
        }

        // Apply user filter for specific paths
        if ($request->path() == 'api/user/resource-archetypes') {
            $user = $request->user();
            $query->where('resource_archetypes.created_by', $user->id);
        }

        // Apply category filter if provided
        if ($request->filled('categoryId')) {
            $query->where('category_resource_archetype.category_id', '=', $request->input('categoryId'));
        }

        // Apply usage filter if provided
        if ($request->filled('usageId')) {
            $query->where('resource_archetype_usage.usage_id', '=', $request->input('usageId'));
        }

        // Apply brand filter if provided
        if ($request->filled('brandId')) {
            $query->where('items.brand_id', '=', $request->input('brandId'));
        }

        // Apply resource archetype filter if provided
        if ($request->filled('resourcearchetypeId')) {
            $query->where('resource_archetypes.id', '=', $request->input('resourcearchetypeId'));
        }

        // Apply resource filter if provided
        $query->whereIn('resource', $resource);


        // Apply the HAVING clause to filter resource archetypes with no items
        $query->havingRaw('available_item_count > 0 OR rented_item_count > 0');

        //Require discord user
        $query->where('owner.discord_user_id', '!=', null);



        // Apply sorting
        $query->orderBy($sortBy, $order);



        // Apply pagination



        $resourceArchetypes = $query->paginate($itemsPerPage, ['*'], 'page', $page);
        $resourcearchetypesArray = $resourceArchetypes->items();
        $totalCount = $resourceArchetypes->total();

        $resourcearchetypeIds = array_column($resourcearchetypesArray, 'id');


        // Fetch random item images for resource archetypes missing resource archetype images
        $itemImages = DB::table('item_images')
            ->select('items.resource_archetype_id', DB::raw('MIN(item_images.id) as id'), DB::raw('MIN(item_images.path) as path'))
            ->join('items', 'items.id', '=', 'item_images.item_id')
            ->whereIn('items.resource_archetype_id', $resourcearchetypeIds)
            ->groupBy('items.resource_archetype_id')
            ->inRandomOrder()
            ->get()
            ->keyBy('resource_archetype_id');


        $images = [];
        // Merge in the item images where resource archetype images are missing
        foreach ($itemImages as $resourcearchetypeId => $image) {
            if (!isset($images[$resourcearchetypeId])) {
                $images[$resourcearchetypeId] = [
                    [
                        'id' => $image->id,
                        'path' => '/storage/' . $image->path
                    ]
                ];
            }
        }

        // Combine resource archetypes with their images
        foreach ($resourcearchetypesArray as &$resourceArchetype) {
            $resourceArchetype['images'] = $images[$resourceArchetype['id']] ?? null;
        }


        $response['data'] = $resourcearchetypesArray;
        $response['total'] = $totalCount;

        // Return response
        return response()->json($response);
    }


    public function getResources()
    {
        $resources = getEnumValues('resource_archetypes', 'resource');
        // Convert to a plain array if needed
        $response['data'] = $resources;
        $response['total'] = count($resources);

        return response()->json($response);
    }


    public function index(Request $request)
    {
        // Get request parameters
        $page = $request->input('page', 1);
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $sortBy = $request->input('sortBy');



        // Base query for data
        $query = ResourceArchetype::query()
            ->leftJoin('category_resource_archetype', 'resource_archetypes.id', '=', 'category_resource_archetype.resource_archetype_id')
            ->leftJoin('categories', 'category_resource_archetype.category_id', '=', 'categories.id')
            ->leftJoin('resource_archetype_usage', 'resource_archetypes.id', '=', 'resource_archetype_usage.resource_archetype_id')
            ->leftJoin('usages', 'resource_archetype_usage.usage_id', '=', 'usages.id')
            ->leftJoin('items', 'items.resource_archetype_id', '=', 'resource_archetypes.id')
            ->leftJoin('users as owner', 'items.owned_by', '=', 'owner.id')
            ->leftJoin('locations as owner_location', 'owner.location_id', '=', 'owner_location.id')
            ->select(
                'resource_archetypes.id',
                'resource_archetypes.name',
                'resource_archetypes.created_by',
                'resource_archetypes.description',
                'resource_archetypes.notes',
                'resource_archetypes.code',
                DB::raw('GROUP_CONCAT(DISTINCT categories.name ORDER BY categories.name ASC SEPARATOR ", ") as categories'),
                DB::raw('GROUP_CONCAT(DISTINCT categories.id ORDER BY categories.id ASC SEPARATOR ", ") as category_ids'),
                DB::raw('GROUP_CONCAT(DISTINCT usages.name ORDER BY usages.name ASC SEPARATOR ", ") as usages'),
                DB::raw('GROUP_CONCAT(DISTINCT usages.id ORDER BY usages.id ASC SEPARATOR ", ") as usage_ids'),
                DB::raw('GROUP_CONCAT(DISTINCT items.id ORDER BY items.owned_by ASC SEPARATOR ", ") as item_ids'),

            );

        $query->groupBy('resource_archetypes.id');


        // Apply search filter if needed
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        // Check if the path is 'me/items' and filter by user if so
        if ($request->path() == 'api/me/resource-archetypes') {
            $user = $request->user();
            $query->where('resource_archetypes.created_by', $user->id);
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
        //paginate or not depending on items per page
        if ($request->itemsPerPage == -1) {
            $resourcearchetypesArray = $query->get()->toArray();
            $totalCount = count($resourcearchetypesArray);
        } else {
            $resourceArchetypes = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $resourcearchetypesArray = $resourceArchetypes->items();
            $totalCount = $resourceArchetypes->total();
        }


        $resourcearchetypeIds = array_column($resourcearchetypesArray, 'id');


        // Fetch the resource archetype images as before
        $resourcearchetypeImages = DB::table('resource_archetype_images')
            ->select('resource_archetype_id', 'id', 'path')
            ->whereIn('resource_archetype_id', $resourcearchetypeIds)
            ->get()
            ->groupBy('resource_archetype_id');

        // Determine which resource archetypes are missing images
        $resourcearchetypesMissingImages = array_diff($resourcearchetypeIds, array_keys($resourcearchetypeImages->toArray()));

        // Fetch random item images for resource archetypes missing resource archetype images
        $itemImages = DB::table('item_images')
            ->select('items.resource_archetype_id', DB::raw('MIN(item_images.id) as id'), DB::raw('MIN(item_images.path) as path'))
            ->join('items', 'items.id', '=', 'item_images.item_id')
            ->whereIn('items.resource_archetype_id', $resourcearchetypesMissingImages)
            ->groupBy('items.resource_archetype_id')
            ->inRandomOrder()
            ->get()
            ->keyBy('resource_archetype_id');

        // Combine both resource archetype images and item images
        $combinedImages = $resourcearchetypeImages->mapWithKeys(function ($imageGroup, $resourcearchetypeId) {
            return [
                $resourcearchetypeId => $imageGroup->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'path' => '/storage/' . $image->path
                    ];
                })
            ];
        });

        // Merge in the item images where resource archetype images are missing
        foreach ($itemImages as $resourcearchetypeId => $image) {
            if (!isset($combinedImages[$resourcearchetypeId])) {
                $combinedImages[$resourcearchetypeId] = [
                    [
                        'id' => $image->id,
                        'path' => '/storage/' . $image->path
                    ]
                ];
            }
        }

        // Combine resource archetypes with their images
        foreach ($resourcearchetypesArray as &$resourceArchetype) {
            $resourceArchetype['images'] = $combinedImages->get($resourceArchetype['id'], []);
        }




        // Return response
        return response()->json([
            'total' => $totalCount,
            'data' => $resourcearchetypesArray
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

        // Preprocess the inputs to convert comma-separated strings into arrays
        if ($request->has('category_ids') && is_string($request->input('category_ids'))) {
            $request->merge([
                'category_ids' => array_map('intval', explode(',', $request->input('category_ids')))
            ]);
        }

        if ($request->has('usage_ids') && is_string($request->input('usage_ids'))) {
            $request->merge([
                'usage_ids' => array_map('intval', explode(',', $request->input('usage_ids')))
            ]);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:resource_archetypes',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Image validation rules
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'code' => 'nullable|string|max:255',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'usage_ids' => 'nullable|array',
            'usage_ids.*' => 'integer|exists:usages,id',
        ]);
        $user = auth()->user();

        $validated['created_by'] = $user->id;

        $resourceArchetype = ResourceArchetype::create($validated);

        // Attach categories and usages
        if (isset($validated['category_ids'])) {
            $resourceArchetype->categories()->attach($validated['category_ids']);
        }

        if (isset($validated['usage_ids'])) {
            $resourceArchetype->usages()->attach($validated['usage_ids']);
        }

        return response()->json(['success' => true, 'data' => $resourceArchetype, 'message' => 'ResourceArchetype created']);
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


        // Preprocess the inputs to convert comma-separated strings into arrays
        if ($request->has('category_ids') && is_string($request->input('category_ids'))) {
            $request->merge([
                'category_ids' => array_map('intval', explode(',', $request->input('category_ids')))
            ]);
        }

        if ($request->has('usage_ids') && is_string($request->input('usage_ids'))) {
            $request->merge([
                'usage_ids' => array_map('intval', explode(',', $request->input('usage_ids')))
            ]);
        }

        $resourceArchetype = $request->validate([
            'name' => "required|string|max:255|unique:resource_archetypes,name,{$id}", //allow the name field to remain unchanged or be unique, 
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Image validation rules
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'code' => 'nullable|string|max:255',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'usage_ids' => 'nullable|array',
            'usage_ids.*' => 'integer|exists:usages,id',

        ]);

        $resourceArchetype = DB::transaction(function () use ($request, $id) {

            $resourceArchetype = ResourceArchetype::findOrFail($id);
            $resourceArchetype->fill($request->except('newImages', 'removedImages'));
            $resourceArchetype->save();

            // Sync categories and usages
            if ($request->has('category_ids')) {
                $resourceArchetype->categories()->sync($request->input('category_ids'));
            }

            if ($request->has('usage_ids')) {
                $resourceArchetype->usages()->sync($request->input('usage_ids'));
            }

            // Handle new images
            if ($request->hasFile('newImages')) {
                foreach ($request->file('newImages') as $image) {
                    // Generate a unique filename based on the current date and user ID
                    $timestamp = now()->format('YmdHis'); // Current date and time
                    $userId = auth()->id(); // Authenticated user's ID
                    $extension = $image->getClientOriginalExtension(); // Get the image's original extension
                    $filename = "{$timestamp}_{$userId}.{$extension}";

                    // Store the image with the unique filename
                    $imagePath = $image->storeAs('images', $filename, 'public'); // Store in `storage/app/public/images`

                    // Save image path to the database
                    ResourceArchetypeImage::create([
                        'resource_archetype_id' => $resourceArchetype->id,
                        'path' => $imagePath,
                        'created_by' => $userId,
                    ]);
                }
            }


            if ($request->removedImages) {
                foreach ($request->removedImages as $imageId) {
                    // Find the image by ID
                    $image = ResourceArchetypeImage::find($imageId);


                    // Check if the image exists
                    if ($image) {
                        // Delete the image file from storage
                        Storage::disk('public')->delete($image->path);

                        // Delete the image record from the database
                        $image->delete();
                    }
                }
            }
            return $resourceArchetype;
        });

        return response()->json(['success' => true, 'message' => 'ResourceArchetype saved', 'data' => $resourceArchetype]);
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
        $resourceArchetype = ResourceArchetype::where('id', $id)->where('created_by', $user->id)->first();
        if (!$resourceArchetype) {
            return response()->json(['message' => 'ResourceArchetype not found or you do not have permission to delete it'], 404);
        }

        //check if there are related items
        $items = Item::where('resource_archetype_id', '=', $resourceArchetype->id);
        if ($items->count() > 0) {
            return response()->json(['message' => 'There are ' . $items->count() . ' items associated with this resource archetype. You must delete them first.'], 404);
        }

        $resourcearchetypeImages = $resourceArchetype->images;

        // Delete the image files from storage
        foreach ($resourcearchetypeImages as $image) {
            Storage::disk('public')->delete($image->path);
        }

        // Delete the records from the item_images table
        $resourceArchetype->images()->delete();

        // Delete the item itself
        $resourceArchetype->delete();

        return response()->json(['success' => true, 'message' => 'ResourceArchetype deleted']);
    }




    // Fetch all usages
    public function getUsages(Request $request)
    {
        $query = Usage::query();
        $query->orderBy('name', 'ASC');

        if ($request->path() == 'api/user/usages') {
            $user = $request->user();
            $query->where('created_by', $user->id);
        }

        $usages = $query->get();

        return response()->json([
            'count' => $usages->count(),
            'data' => $usages
        ]);
    }
}
