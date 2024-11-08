<?php

namespace App\Http\Controllers;

use App\Models\Type;
use App\Models\Item;
use App\Models\Usage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\TypeImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPaginatedTypes(Request $request)
    {
        // Get request parameters
        $page = $request->input('page', 1);
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $sortBy = $request->input('sortBy.0.key', 'types.id'); // Default sort by id
        $order = $request->input('sortBy.0.order', 'asc'); // Default order ascending
        $search = $request->input('search', '');
        $radius = $request->input('radius');
        $latitude = $request->input('location.lat');
        $longitude = $request->input('location.lng');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        // Base query for data
        $query = Type::query()
            ->leftJoin('category_type', 'types.id', '=', 'category_type.type_id')
            ->leftJoin('categories', 'category_type.category_id', '=', 'categories.id')
            ->leftJoin('type_usage', 'types.id', '=', 'type_usage.type_id')
            ->leftJoin('usages', 'type_usage.usage_id', '=', 'usages.id')
            ->leftJoin('items', 'items.type_id', '=', 'types.id')
            ->leftJoin('brands', 'items.brand_id', '=', 'brands.id')
            ->leftJoin('users as owner', 'items.owned_by', '=', 'owner.id')
            ->leftJoin('locations as owner_location', 'owner.location_id', '=', 'owner_location.id')
            ->leftJoin('rentals', function ($join) use ($startDate, $endDate) {
                $join->on('items.id', '=', 'rentals.item_id')
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->where('rentals.starts_at', '<=', $endDate)
                            ->where('rentals.ends_at', '>=', $startDate);
                    });
            })
            ->leftJoin('users as renter', 'rentals.rented_by', '=', 'renter.id')
            ->leftJoin('locations as renter_location', 'renter.location_id', '=', 'renter_location.id')
            ->select(
                'types.id',
                'types.name',
                'types.created_by',
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

                // Subquery for rented items count based on distance
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

        $query->groupBy('types.id');


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
                $query->where('types.name', 'like', '%' . $search . '%')
                    ->orWhere('items.description', 'like', '%' . $search . '%');
            });
        }

        // Apply user filter for specific paths
        if ($request->path() == 'api/user/types') {
            $user = $request->user();
            $query->where('types.created_by', $user->id);
        }

        // Apply category filter if provided
        if ($request->filled('categoryId')) {
            $query->where('category_type.category_id', '=', $request->input('categoryId'));
        }

        // Apply usage filter if provided
        if ($request->filled('usageId')) {
            $query->where('type_usage.usage_id', '=', $request->input('usageId'));
        }

        // Apply brand filter if provided
        if ($request->filled('brandId')) {
            $query->where('items.brand_id', '=', $request->input('brandId'));
        }

        // Apply type filter if provided
        if ($request->filled('typeId')) {
            $query->where('types.id', '=', $request->input('typeId'));
        }

        // Apply the HAVING clause to filter types with no items
        $query->havingRaw('available_item_count > 0 OR rented_item_count > 0');

        //Require discord user
        $query->where('owner.discord_user_id', '!=', null);


        // Apply sorting
        $query->orderBy($sortBy, $order);


        // Apply pagination



        $types = $query->paginate($itemsPerPage, ['*'], 'page', $page);
        $typesArray = $types->items();
        $totalCount = $types->total();

        $typeIds = array_column($typesArray, 'id');


        // Fetch the type images as before
        $typeImages = DB::table('type_images')
            ->select('type_id', 'id', 'path')
            ->whereIn('type_id', $typeIds)
            ->get()
            ->groupBy('type_id');

        // Determine which types are missing images
        $typesMissingImages = array_diff($typeIds, array_keys($typeImages->toArray()));

        // Fetch random item images for types missing type images
        $itemImages = DB::table('item_images')
            ->select('items.type_id', DB::raw('MIN(item_images.id) as id'), DB::raw('MIN(item_images.path) as path'))
            ->join('items', 'items.id', '=', 'item_images.item_id')
            ->whereIn('items.type_id', $typesMissingImages)
            ->groupBy('items.type_id')
            ->inRandomOrder()
            ->get()
            ->keyBy('type_id');

        // Combine both type images and item images
        $combinedImages = $typeImages->mapWithKeys(function ($imageGroup, $typeId) {
            return [
                $typeId => $imageGroup->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'path' => '/storage/' . $image->path
                    ];
                })
            ];
        });

        // Merge in the item images where type images are missing
        foreach ($itemImages as $typeId => $image) {
            if (!isset($combinedImages[$typeId])) {
                $combinedImages[$typeId] = [
                    [
                        'id' => $image->id,
                        'path' => '/storage/' . $image->path
                    ]
                ];
            }
        }

        // Combine types with their images
        foreach ($typesArray as &$type) {
            $type['images'] = $combinedImages->get($type['id'], []);
        }




        // Return response
        return response()->json([
            'count' => $totalCount,
            'types' => $typesArray
        ]);
    }




    public function getAllTypes(Request $request)
    {
        // Get request parameters
        $sortBy = $request->input('sortBy.0.key', 'types.id'); // Default sort by id
        $order = $request->input('sortBy.0.order', 'asc'); // Default order ascending




        // Base query for data
        $query = Type::query()
            ->leftJoin('category_type', 'types.id', '=', 'category_type.type_id')
            ->leftJoin('categories', 'category_type.category_id', '=', 'categories.id')
            ->leftJoin('type_usage', 'types.id', '=', 'type_usage.type_id')
            ->leftJoin('usages', 'type_usage.usage_id', '=', 'usages.id')
            ->leftJoin('items', 'items.type_id', '=', 'types.id')
            ->leftJoin('users as owner', 'items.owned_by', '=', 'owner.id')
            ->leftJoin('locations as owner_location', 'owner.location_id', '=', 'owner_location.id')
            ->select(
                'types.id',
                'types.name',
                'types.created_by',
                DB::raw('GROUP_CONCAT(DISTINCT categories.name ORDER BY categories.name ASC SEPARATOR ", ") as categories'),
                DB::raw('GROUP_CONCAT(DISTINCT categories.id ORDER BY categories.id ASC SEPARATOR ", ") as category_ids'),
                DB::raw('GROUP_CONCAT(DISTINCT usages.name ORDER BY usages.name ASC SEPARATOR ", ") as usages'),
                DB::raw('GROUP_CONCAT(DISTINCT usages.id ORDER BY usages.id ASC SEPARATOR ", ") as usage_ids'),
                DB::raw('GROUP_CONCAT(DISTINCT items.id ORDER BY items.owned_by ASC SEPARATOR ", ") as item_ids'),

            );

        $query->groupBy('types.id');

        // Apply sorting
        $query->orderBy($sortBy, $order);


        // Apply pagination

        $typesArray = $query->get()->toArray();
        $totalCount = count($typesArray);


        $typeIds = array_column($typesArray, 'id');


        // Fetch the type images as before
        $typeImages = DB::table('type_images')
            ->select('type_id', 'id', 'path')
            ->whereIn('type_id', $typeIds)
            ->get()
            ->groupBy('type_id');

        // Determine which types are missing images
        $typesMissingImages = array_diff($typeIds, array_keys($typeImages->toArray()));

        // Fetch random item images for types missing type images
        $itemImages = DB::table('item_images')
            ->select('items.type_id', DB::raw('MIN(item_images.id) as id'), DB::raw('MIN(item_images.path) as path'))
            ->join('items', 'items.id', '=', 'item_images.item_id')
            ->whereIn('items.type_id', $typesMissingImages)
            ->groupBy('items.type_id')
            ->inRandomOrder()
            ->get()
            ->keyBy('type_id');

        // Combine both type images and item images
        $combinedImages = $typeImages->mapWithKeys(function ($imageGroup, $typeId) {
            return [
                $typeId => $imageGroup->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'path' => '/storage/' . $image->path
                    ];
                })
            ];
        });

        // Merge in the item images where type images are missing
        foreach ($itemImages as $typeId => $image) {
            if (!isset($combinedImages[$typeId])) {
                $combinedImages[$typeId] = [
                    [
                        'id' => $image->id,
                        'path' => '/storage/' . $image->path
                    ]
                ];
            }
        }

        // Combine types with their images
        foreach ($typesArray as &$type) {
            $type['images'] = $combinedImages->get($type['id'], []);
        }




        // Return response
        return response()->json([
            'count' => $totalCount,
            'types' => $typesArray
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserTypes(Request $request)
    {
        // Get request parameters
        $page = $request->input('page', 1);
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $sortBy = $request->input('sortBy.0.key', 'types.name'); // Default sort by id
        $order = $request->input('sortBy.0.order', 'asc'); // Default order ascending
        $search = $request->input('search', '');

        // Base query for data
        $query = Type::query()
            ->leftJoin('category_type', 'types.id', '=', 'category_type.type_id')
            ->leftJoin('categories', 'category_type.category_id', '=', 'categories.id')
            ->leftJoin('items', 'items.type_id', '=', 'types.id')
            ->leftJoin('type_usage', 'types.id', '=', 'type_usage.type_id')
            ->leftJoin('usages', 'type_usage.usage_id', '=', 'usages.id')
            ->leftJoin('users as owner', 'items.owned_by', '=', 'owner.id')
            ->select(
                'types.id',
                'types.name',
                'types.created_by',
                DB::raw('GROUP_CONCAT(DISTINCT categories.name ORDER BY categories.name ASC SEPARATOR ", ") as categories'),
                DB::raw('GROUP_CONCAT(DISTINCT categories.id ORDER BY categories.id ASC SEPARATOR ", ") as category_ids'),
                DB::raw('GROUP_CONCAT(DISTINCT usages.name ORDER BY usages.name ASC SEPARATOR ", ") as usages'),
                DB::raw('GROUP_CONCAT(DISTINCT usages.id ORDER BY usages.id ASC SEPARATOR ", ") as usage_ids'),

            );

        $query->groupBy('types.id');



        // Apply search filter if needed
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('types.name', 'like', '%' . $search . '%');
            });
        }

        // Apply user filter 
        $user = $request->user();
        $query->where('types.created_by', $user->id);


        // Apply category filter if provided
        if ($request->filled('categoryId')) {
            $query->where('category_type.category_id', '=', $request->input('categoryId'));
        }

        // Apply usage filter if provided
        if ($request->filled('usageId')) {
            $query->where('type_usage.usage_id', '=', $request->input('usageId'));
        }




        // Apply sorting
        $query->orderBy($sortBy, $order);


        // Apply pagination
        $types = $query->paginate($itemsPerPage, ['*'], 'page', $page);
        $typesArray = $types->items();
        $totalCount = $types->total();

        $typeIds = array_column($typesArray, 'id');


        // Fetch the type images as before
        $typeImages = DB::table('type_images')
            ->select('type_id', 'id', 'path')
            ->whereIn('type_id', $typeIds)
            ->get()
            ->groupBy('type_id');

        // Determine which types are missing images
        $typesMissingImages = array_diff($typeIds, array_keys($typeImages->toArray()));

        // Fetch random item images for types missing type images
        $itemImages = DB::table('item_images')
            ->select('items.type_id', DB::raw('MIN(item_images.id) as id'), DB::raw('MIN(item_images.path) as path'))
            ->join('items', 'items.id', '=', 'item_images.item_id')
            ->whereIn('items.type_id', $typesMissingImages)
            ->groupBy('items.type_id')
            ->inRandomOrder()
            ->get()
            ->keyBy('type_id');

        // Combine both type images and item images
        $combinedImages = $typeImages->mapWithKeys(function ($imageGroup, $typeId) {
            return [
                $typeId => $imageGroup->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'path' => '/storage/' . $image->path
                    ];
                })
            ];
        });

        // Merge in the item images where type images are missing
        foreach ($itemImages as $typeId => $image) {
            if (!isset($combinedImages[$typeId])) {
                $combinedImages[$typeId] = [
                    [
                        'id' => $image->id,
                        'path' => '/storage/' . $image->path
                    ]
                ];
            }
        }

        // Combine types with their images
        foreach ($typesArray as &$type) {
            $type['images'] = $combinedImages->get($type['id'], []);
        }




        // Return response
        return response()->json([
            'count' => $totalCount,
            'types' => $typesArray
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
            'name' => 'required|string|max:255|unique:types',
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

        $type = Type::create($validated);

        // Attach categories and usages
        if (isset($validated['category_ids'])) {
            $type->categories()->attach($validated['category_ids']);
        }

        if (isset($validated['usage_ids'])) {
            $type->usages()->attach($validated['usage_ids']);
        }

        // Assuming you have validated the request as shown earlier
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            // Generate a unique filename based on the current date and user ID
            $timestamp = now()->format('YmdHis'); // Current date and time
            $userId = auth()->id(); // Authenticated user's ID
            $extension = $image->getClientOriginalExtension(); // Get the image's original extension
            $filename = "{$timestamp}_{$userId}.{$extension}";

            // Store the image with the unique filename
            $imagePath = $image->storeAs('images', $filename, 'public'); // Store in `storage/app/public/images`

            // Save image path to the database
            TypeImage::create([
                'type_id' => $type->id, // Replace $itemId with the actual item ID
                'path' => $imagePath,
                'created_by' => $userId, // Assuming you are using authentication
            ]);
        }


        return response()->json($type);
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

        $request->validate([
            'name' => 'required|string|max:255|unique:types,name,' . $id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Image validation rules
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'code' => 'nullable|string|max:255',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'usage_ids' => 'nullable|array',
            'usage_ids.*' => 'integer|exists:usages,id',

        ]);

        $type = Type::findOrFail($id);
        $type->fill($request->except('newImages', 'removedImages'));
        $type->save();

        // Sync categories and usages
        if ($request->has('category_ids')) {
            $type->categories()->sync($request->input('category_ids'));
        }

        if ($request->has('usage_ids')) {
            $type->usages()->sync($request->input('usage_ids'));
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
                TypeImage::create([
                    'type_id' => $type->id,
                    'path' => $imagePath,
                    'created_by' => $userId,
                ]);
            }
        }


        if ($request->removedImages) {
            foreach ($request->removedImages as $imageId) {
                // Find the image by ID
                $image = TypeImage::find($imageId);


                // Check if the image exists
                if ($image) {
                    // Delete the image file from storage
                    Storage::disk('public')->delete($image->path);

                    // Delete the image record from the database
                    $image->delete();
                }
            }
        }

        return response()->json($type);
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
        $type = Type::where('id', $id)->where('created_by', $user->id)->first();
        if (!$type) {
            return response()->json(['message' => 'Type not found or you do not have permission to delete it'], 404);
        }

        //check if there are related items
        $items = Item::where('type_id', '=', $type->id);
        if ($items->count() > 0) {
            return response()->json(['message' => 'There are ' . $items->count() . ' items associated with this type. You must delete them first.'], 404);
        }

        $typeImages = $type->images;

        // Delete the image files from storage
        foreach ($typeImages as $image) {
            Storage::disk('public')->delete($image->path);
        }

        // Delete the records from the item_images table
        $type->images()->delete();

        // Delete the item itself
        $type->delete();

        return response()->json(['message' => 'Type and associated images deleted successfully']);
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
