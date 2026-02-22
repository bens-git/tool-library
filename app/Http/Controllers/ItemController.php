<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\ItemImage;
use App\Models\Archetype;
use App\Models\Brand;
use App\Models\ItemUnavailableDate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{
    /**
     * Display a listing of the items.
     */
    public function index(Request $request)
    {
        // Get request parameters
        $brandId = $request->query('brand_id');
        $archetypeId = $request->query('archetype_id');
        $userId = $request->query('user_id');
        $categoryId = $request->query('category_id');
        $usageId = $request->query('usage_id');
        $search = $request->query('search', '');
        $itemsPerPage = $request->query('itemsPerPage', 9);
        $page = $request->query('page', 1);

        // Check if any search filter is active
        $hasFilters = $brandId || $archetypeId || $userId || $categoryId || $usageId || $search;

        // If no filters, return empty response
        if (!$hasFilters) {
            return response()->json([
                'data' => [],
                'total' => 0,
            ]);
        }

        // Build the query with eager loading
        $query = Item::with(['archetype', 'brand', 'images', 'accessValue', 'archetype.categories', 'archetype.usages']);

        // Apply archetype filter
        if (!empty($archetypeId)) {
            $query->where('archetype_id', '=', $archetypeId);
        }

        // Apply brand filter
        if (!empty($brandId)) {
            $query->where('brand_id', '=', $brandId);
        }

        // Apply category filter
        if (!empty($categoryId)) {
            $query->whereHas('archetype.categories', function ($q) use ($categoryId) {
                $q->where('categories.id', '=', $categoryId);
            });
        }

        // Apply usage filter
        if (!empty($usageId)) {
            $query->whereHas('archetype.usages', function ($q) use ($usageId) {
                $q->where('usages.id', '=', $usageId);
            });
        }

        // Apply user filter
        if (!empty($userId)) {
            $query->where('owned_by', '=', $userId);
        }

        // Apply search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('archetype', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        // Paginate results
        $items = $query->paginate($itemsPerPage, ['*'], 'page', $page);

        // Return using ItemResource
        return response()->json([
            'data' => ItemResource::collection($items)->resolve(),
            'total' => $items->total(),
        ]);
    }




    public function patchMakeItemUnavailable(Request $request, Item $item)
    {

        $item->update([
            'make_item_unavailable' => $request->input('make_item_unavailable'),
        ]);


        return response()->json([
            'message' => 'Item availability updated successfully',
            'item'    => $item,
        ]);
    }


    /**
     * Store a newly created item in storage.
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'description' => 'nullable|string',
            'serial' => 'nullable|string',
            'purchase_value' => 'nullable|numeric',
            'manufactured_at' => 'nullable|date',
            'archetype.id' => 'required|numeric|exists:archetypes,id',
        ]);


        $user = auth::user();

        // Decode the validated JSON
        $archetype = Archetype::findOrFail($request->archetype['id']);
        /** @var Brand|null $brand */
        $brand = null;
        if ($request->brand) {
            $brand = Brand::findOrFail($request->brand['id']);
        }

        $item = new Item();
        $item->archetype_id = $archetype->id;              // Store the ID from the validated JSON
        $item->brand_id = $brand->id ?? null;       // Optional: Store other fields from the JSON
        $item->description = $request->description;
        $item->serial = $request->serial;
        $item->purchase_value = $request->purchase_value;
        $item->purchased_at = Carbon::parse($request->purchased_at)->format('Y-m-d H:i:s');
        $item->manufactured_at = $request->manufactured_at ? Carbon::parse($request->manufactured_at)->format('Y-m-d H:i:s') : null;
        $item->owned_by = $user->id;
        $item->save();


        $response['data'] = $item;
        $response['message'] = 'Item created';

        return response()->json(['success' => true, 'data' => $item, 'message' => 'Item created']);
    }



    /**
     * Store a newly created image in storage.
     */
    public function storeImage(Request $request, $id)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:12000', // Image validation rules
        ], [
            'image.max' => 'The uploaded file size must not exceed 12 MB.',
            'image.image' => 'The uploaded file must be an image.',

        ]);

        $user = Auth::user();

        $validated['created_by'] = $user->id;

        $item = Item::findOrFail($id);


        DB::transaction(function () use ($request, $item, $user) {
            // Assuming you have validated the request as shown earlier
            if ($request->hasFile('image')) {
                $image = $request->file('image');

                // Generate a unique filename based on the current date and user ID
                $timestamp = now()->format('YmdHis'); // Current date and time
                $userId = $user->id; // Authenticated user's ID
                $extension = $image->getClientOriginalExtension(); // Get the image's original extension
                $filename = "{$timestamp}_{$userId}.{$extension}";

                // Store the image with the unique filename
                $imagePath = $image->storeAs('images', $filename, 'public'); // Store in `storage/app/public/images`

                // Save image path to the database
                ItemImage::create([
                    'item_id' => $item->id, // Replace $itemId with the actual item ID
                    'path' => $imagePath,
                    'created_by' => $userId, // Assuming you are using authentication
                ]);
            }
        });
        return response()->json(['success' => true, 'message' => 'Image created']);
    }

    /**
     * Display the specified item.
     */
    public function show($id)
    {
        // Find the item by its I
        $item = Item::with('archetype', 'brand', 'images', 'accessValue')
            ->join('users', 'items.owned_by', '=', 'users.id')
            ->join('archetypes', 'items.archetype_id', '=', 'archetypes.id')
            ->where('items.id', $id)
            ->select('items.*', 'users.name as owner_name', 'archetypes.name AS archetype_name') // Select relevant columns
            ->first();

        // Add url to each image
        foreach ($item->images as $image) {
            /** @var ItemImage $image */
            $image->url = $image->url; // This uses the getUrlAttribute accessor
        }

        $response['data'] = $item;

        // Return response
        return response()->json($response);
    }



    /**
     * Update the specified item in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'purchase_value' => 'nullable|numeric|min:0', // Adjust according to your needs
            'description' => 'nullable|string', // Ensure description is a nullable string
            'serial' => 'nullable|string',
            'purchased_at' => 'required|date', // Validate created_at as a nullable date
            'manufactured_at' => 'nullable|date', // Validate manufactured_at as a nullable date
            'make_item_unavailable' => 'boolean', // Validate manufactured_at as a nullable date
            'images' => 'nullable|array', // Validate manufactured_at as a nullable date
            'images.*.id' => 'required|integer',
            'images.*.path' => 'required|string',
            'brand.id' => 'nullable|numeric|exists:brands,id', // Adjust according to your needs
        ]);



        $item = DB::transaction(function () use ($request, $id) {



            // Decode the validated JSON
            $archetype = Archetype::findOrFail($request->archetype['id']);
            /** @var Brand|null $brand */
            $brand = null;
            if ($request->brand) {
                $brand = Brand::findOrFail($request->brand['id']);
            }


            /** @var Item $item */
            $item = Item::findOrFail($id);
            $item->archetype_id = $archetype->id;
            $item->brand_id = $brand->id ?? null;       // Optional: Store other fields from the JSON
            $item->description = $request->description;
            $item->serial = $request->serial;
            $item->purchase_value = $request->purchase_value;
            $item->make_item_unavailable = $request->make_item_unavailable;
            $item->purchased_at = Carbon::parse($request->purchased_at)->format('Y-m-d H:i:s');
            $item->manufactured_at = $request->manufactured_at ? Carbon::parse($request->manufactured_at)->format('Y-m-d H:i:s') : null;
            $item->save();


            $postedImageIds = collect($request['images'])->pluck('id')->filter()->toArray(); // Get posted image IDs

            // Fetch existing images from the database for the given item
            $existingImages = ItemImage::where('item_id', $id)->get();


            // Get IDs of existing images
            $existingImageIds = $existingImages->pluck('id')->toArray();


            // Determine which images to delete
            $imageIdsToDelete = array_diff($existingImageIds, $postedImageIds);


            foreach ($imageIdsToDelete as $imageId) {

                $image = ItemImage::find($imageId);

                // Check if the image exists
                if ($image) {
                    // Delete the image file from storage
                    /** @var ItemImage $image */
                    Storage::disk('public')->delete($image->path);

                    // Delete the image record from the database
                    $image->delete();
                }
            }
            return $item;
        });

        return response()->json(['data' => $item, 'message' => 'Item updated', 'success' => true]);
    }
    /**
     * Remove the specified item from storage.
     */
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {

            $user = Auth::user();
            $item = Item::with('rentals')->where('id', $id)->where('owned_by', $user->id)->first();

            if (!$item) {
                return response()->json(['message' => 'Item not found or you do not have permission to delete it'], 404);
            }

            //check if there are any active rentals
            $hasActiveRental = $item->rentals->contains('status', 'active');
            if ($hasActiveRental) {
                abort(404, "Item can not be deleted because it is currently rented");
            }

            $itemImages = $item->images;

            // Delete the image files from storage
            foreach ($itemImages as $image) {
                /** @var ItemImage $image */
                Storage::disk('public')->delete($image->path);
            }

            // Delete the records from the item_images table
            $item->images()->delete();

            // Delete the rentals
            $item->rentals()->delete();

            // Delete the item itself
            $item->delete();
        });
        return response()->json(['message' => 'Item and associated images deleted successfully']);
    }


    /**
     * get featured items
     */
    public function featured(): AnonymousResourceCollection
    {
        return ItemResource::collection(
            Item::whereHas('images') // must have at least 1 image
                ->with(['images', 'brand', 'archetype', 'accessValue'])
                ->inRandomOrder()
                ->limit(6)
                ->get()
        );
    }

    public function getItemUnavailableDates($itemId)
    {
        // Fetch unavailable dates for the specified itemId
        $unavailableDates = ItemUnavailableDate::where('item_id', $itemId)
            ->get(['unavailable_date']);



        // Flatten the collection by plucking the unavailable_date and convert to ISO 8601 format
        $dates = $unavailableDates->pluck('unavailable_date')->map(function ($date) {
            return $date;
        });

        // Convert to a plain array if needed
        $response['data'] = $dates->all();

        return response()->json($response);
    }



    function convertToUTCAndMySQLDate($isoDate)
    {
        // Create a DateTime object from the ISO 8601 date string
        $dateTime = new \DateTime($isoDate, new \DateTimeZone('UTC'));

        // Format the DateTime object to MySQL DATETIME format
        return $dateTime->format('Y-m-d H:i:s');
    }
    public function updateItemAvailability(Request $request, $itemId)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'unavailableDates' => 'nullable|array',
            'unavailableDates.*' => 'date', // Validates that each item is a valid date
            'itemId' => 'required|integer|exists:items,id'
        ]);

        DB::transaction(function () use ($validatedData) {


            // Extracting validated data
            $unavailableDates = $validatedData['unavailableDates'] ?? [];

            // Delete existing unavailable dates for this item
            ItemUnavailableDate::where('item_id', $validatedData['itemId'])->delete();




            // Insert new unavailable dates
            if (!empty($unavailableDates)) {


                // Convert each ISO 8601 date to UTC and MySQL format
                $convertedDates = array_map(function ($date) {
                    return $this->convertToUTCAndMySQLDate($date);
                }, $validatedData['unavailableDates']);

                $dataToInsert = [];
                foreach ($convertedDates as $date) {
                    $dataToInsert[] = [
                        'item_id' => $validatedData['itemId'],
                        'unavailable_date' => $date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Insert the new records in one query
                ItemUnavailableDate::insert($dataToInsert);
            }
        });
        return response()->json(['message' => 'Item availability updated successfully.']);
    }
}
