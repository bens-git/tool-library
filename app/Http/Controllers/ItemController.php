<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Support\Facades\Response;
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
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {


        // Get request parameters
        $brandId = $request->query('brand_id');
        $endDate = $request->query('endDate');
        $itemsPerPage = $request->query('itemsPerPage', 10);
        $latitude = $request->query('location.lat');
        $longitude = $request->query('location.lng');
        $page = $request->query('page', 1);
        $radius = $request->query('radius');
        $search = $request->query('search', '');
        $sortBy = $request->query('sortBy');
        $startDate = $request->query('startDate');
        $archetypeId = $request->query('archetype_id');
        $userId = $request->query('user_id');
        $categoryId = $request->query('category_id');
        $usageId = $request->query('usage_id');


        // show nothing until search
        if (
            !$latitude &&
            !$longitude &&
            !$brandId &&
            !$endDate &&
            !$search &&
            !$startDate &&
            !$archetypeId &&
            !$userId &&
            !$categoryId &&
            !$usageId
        ) {
            return response()->json([
                'data' => [],
                'total' => 0,
            ]);
        }


        // Build the query
        $query = Item::with('archetype', 'archetype.categories', 'archetype.usages', 'brand')
            ->join('users', 'items.owned_by', '=', 'users.id')
            ->join('locations', 'users.location_id', '=', 'locations.id')
            ->join('archetypes', 'items.archetype_id', '=', 'archetypes.id')
            ->leftJoin('archetype_category', 'items.archetype_id', '=', 'archetype_category.archetype_id')
            ->leftJoin('archetype_usage', 'items.archetype_id', '=', 'archetype_usage.archetype_id')
            ->leftJoin('brands', 'items.brand_id', '=', 'brands.id');

        if (!empty($archetypeId)) {
            $query->where('items.archetype_id', '=', $archetypeId);
        }
        // Apply brand filter
        if (!empty($brandId)) {
            $query->where('brand_id', '=', $brandId);
        }

        // Apply category filter
        if (!empty($categoryId)) {
            $query->where('archetype_category.category_id', '=', $categoryId);
        }

        // Apply usage filter
        if (!empty($usageId)) {
            $query->where('archetype_usage.usage_id', '=', $usageId);
        }



        // Apply user filter
        if (!empty($userId)) {
            $user = Auth::user();
            $query->where('owned_by', $user->id);
        }



        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('items.code', 'like', '%' . $search . '%');
            });
        }

        // Apply date range filter for availability
        if (!empty($startDate) && !empty($endDate)) {
            $query->leftJoin('rentals', function ($join) use ($startDate, $endDate) {
                $join->on('items.id', '=', 'rentals.item_id')
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('rentals.starts_at', [$startDate, $endDate])
                            ->orWhereBetween('rentals.ends_at', [$startDate, $endDate])
                            ->orWhere(function ($query) use ($startDate, $endDate) {
                                $query->where('rentals.starts_at', '<=', $startDate)
                                    ->where('rentals.ends_at', '>=', $endDate);
                            });
                    });
            })
                ->whereNull('rentals.id'); // Exclude items that are rented during the date range


            // Apply date range filter for unavailable dates
            $query->leftJoin('item_unavailable_dates', function ($join) use ($startDate, $endDate) {
                $join->on('items.id', '=', 'item_unavailable_dates.item_id')
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('item_unavailable_dates.unavailable_date', [$startDate, $endDate]);
                    });
            })
                ->whereNull('item_unavailable_dates.id'); // Exclude items that are unavailable during the date range


        }


        // Apply distance filter for items within the radius
        if (!empty($latitude) && !empty($longitude) && !empty($radius)) {
            $distance = $radius * 1000;

            // Apply the distance filtering in the WHERE clause
            $query->where(function ($q) use ($longitude, $latitude, $distance) {
                $q->whereRaw('ST_Distance_Sphere(
            point(locations.longitude, locations.latitude), 
            point(?, ?)
        ) <= ?', [$longitude, $latitude, $distance]);
            });
        }



        // Select items with their images as an array
        $query->select(
            'items.code',
            'items.owned_by',
            'items.archetype_id',
            'items.created_at',
            'items.description',
            'items.id',
            'manufactured_at',
            'users.name as owner_name',
            'purchase_value',
            'purchased_at',
            'serial',
            'brands.name AS brand_name',
            'items.brand_id',
            DB::raw('  CONCAT_WS(" ", COALESCE(locations.city, ""), COALESCE(locations.state, ""), COALESCE(locations.country, "")) as location')
        )->with(['archetype', 'brand'])
            ->groupBy(
                'archetypes.name',
                'brands.name',
                'items.archetype_id',
                'items.brand_id',
                'items.code',
                'items.created_at',
                'items.description',
                'items.id',
                'items.owned_by',
                'manufactured_at',
                'purchase_value',
                'purchased_at',
                'serial',
                'users.name',
                'locations.city',
                'locations.state',
                'locations.country'
            );

        // Apply sorting
        if ($sortBy) {
            foreach ($sortBy as $sort) {
                $key = $sort['key'] ?? 'id';
                $order = strtolower($sort['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

                $query->orderBy($key, $order);
            }
        }


        //paginate or not depending on items per page
        if ($request->itemsPerPage == -1) {
            $itemsArray = $query->get()->toArray();
            $totalCount = count($itemsArray);
        } else {
            $items = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $itemsArray = $items->items();
            $totalCount = $items->total();
        }

        $itemIds = array_column($itemsArray, 'id');
        $images = DB::table('item_images')
            ->select('item_id', 'id', 'path')
            ->whereIn('item_id', $itemIds)
            ->get()
            ->groupBy('item_id');



        // Process image data to structure it as needed
        $processedImages = $images->mapWithKeys(function ($imageGroup, $itemId) {
            return [
                $itemId => $imageGroup->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => '/storage/' . $image->path
                    ];
                })
            ];
        });

        // return response()->json($itemsArray);

        //  return response()->json($itemsArray);
        // Combine items with their images
        foreach ($itemsArray as &$item) {
            $item['images'] = count($processedImages) ? $processedImages->get($item['id'], []) : [];
        }


        return response()->json([
            'data' => $itemsArray,
            'total' => $totalCount,
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
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'purchase_value' => 'required|numeric|min:0', // Adjust according to your needs
            'description' => 'nullable|string',
            'serial' => 'nullable|string',
            'purchase_value' => 'nullable|numeric',
            'manufactured_at' => 'nullable|date',
            'archetype.id' => 'required|numeric|exists:archetypes,id', // Adjust according to your needs
        ]);


        $user = auth::user();

        // Decode the validated JSON
        $archetype = Archetype::findOrFail($request->archetype['id']);
        if ($request->brand) {
            $brand = Brand::findOrFail($request->brand['id']);
        }

        $username = $user->name;
        $archetypeName = $archetype['name'];
        $dateString = Carbon::parse($request->purchased_at)->format('d-m-y');

        $code = $username . '_' .
            strtolower(str_replace(' ', '', $archetypeName)) . '_' .
            $dateString;

        $uniqueCode = getUniqueString('items', 'code', $code);


        $item = new Item();
        $item->archetype_id = $archetype->id;              // Store the ID from the validated JSON
        $item->brand_id = $brand->id ?? null;       // Optional: Store other fields from the JSON
        $item->description = $request->description;
        $item->serial = $request->serial;
        $item->purchase_value = $request->purchase_value;
        $item->purchased_at = Carbon::parse($request->purchased_at)->format('Y-m-d H:i:s');
        $item->manufactured_at = $request->manufactured_at ? Carbon::parse($request->manufactured_at)->format('Y-m-d H:i:s') : null;
        $item->owned_by = $user->id;
        $item->code = $uniqueCode;
        $item->save();


        $response['data'] = $item;
        $response['message'] = 'Item created';

        return response()->json(['success' => true, 'data' => $item, 'message' => 'Item created']);
    }



    /**
     * Store a newly created image in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Find the item by its I
        $item = Item::with('archetype', 'brand', 'images')
            ->join('users', 'items.owned_by', '=', 'users.id')
            ->join('archetypes', 'items.archetype_id', '=', 'archetypes.id')
            ->where('items.id', $id)
            ->select('items.*', 'users.name as owner_name', 'archetypes.name AS archetype_name') // Select relevant columns
            ->first();


        foreach ($item->images as $image) {
            $image->path = '/storage/' . $image->path;
        }

        $response['data'] = $item;

        // Return response
        return response()->json($response);
    }



    /**
     * Update the specified item in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
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
            if ($request->brand) {
                $brand = Brand::findOrFail($request->brand['id']);
            }


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
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
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
            $hasActiveRental = $item && $item->rentals->contains('status', 'active');
            if ($hasActiveRental) {
                abort(404, "Item can not be deleted because it is currently rented");
            }

            $itemImages = $item->images;

            // Delete the image files from storage
            foreach ($itemImages as $image) {
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
     * get fetured items
     *
     * @return Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function featured()
    {
        return ItemResource::collection(
            Item::whereHas('images') // must have at least 1 image
                ->with(['images', 'brand', 'archetype'])
                ->inRandomOrder()
                ->limit(6)
                ->get()
        );
    }

    public function getItemUnavailableDates($itemId)
    {
        $itemId;

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
