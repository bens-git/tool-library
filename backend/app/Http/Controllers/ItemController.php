<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\ItemImage;
use App\Models\ItemUnavailableDate;
use App\Models\Brand;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {



        // Get request parameters
        $sortBy = $request->input('sortBy.0.key', 'items.id'); // Default sort by id
        $order = $request->input('sortBy.0.order', 'asc'); // Default order ascending
        $page = $request->input('page', 1);
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $typeId = $request->input('typeId');
        $brandId = $request->input('brandId');
        $search = $request->input('search', '');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $hideOwn = $request->input('hideOwn');
        $latitude = $request->input('location.lat');
        $longitude = $request->input('location.lng');
        $radius = $request->input('radius');

        // Build the query
        $query = Item::query()
            ->join('users', 'items.owned_by', '=', 'users.id')
            ->join('locations', 'users.location_id', '=', 'locations.id')
            ->join('types', 'items.type_id', '=', 'types.id')
            ->leftJoin('brands', 'items.brand_id', '=', 'brands.id');

        // Apply type filter
        if (!empty($typeId)) {
            $query->where('type_id', '=', $typeId);
        }
        // Apply brand filter
        if (!empty($brandId)) {
            $query->where('brand_id', '=', $brandId);
        }

        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('users.name', 'like', '%' . $search . '%')
                    ->orWhere('items.id', 'like', '%' . $search . '%')
                    ->orWhere('types.name', 'like', '%' . $search . '%');
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

        // Check if the path is 'user/items' and filter by user if so
        if ($request->path() == 'api/user/items') {
            $user = $request->user();
            $query->where('owned_by', $user->id);
        }

        // Hide own items if requested
        if ($hideOwn) {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'User not authenticated', 'error' => Response::HTTP_UNAUTHORIZED], 500);
            }
            $query->where('owned_by', '!=', $user->id);
        }

        //Require discord user
        $query->where('users.discord_user_id', '!=', null);

        // Select items with their images as an array
        $query->select(
            DB::raw("CONCAT(
            LOWER(REGEXP_REPLACE(LEFT(users.name, 3), '[^a-zA-Z0-9]', '')), '_', 
            LOWER(REGEXP_REPLACE(LEFT(types.name, 3), '[^a-zA-Z0-9]', '')), '_', 
            items.id
        ) AS item_name"),
            'items.owned_by',
            'type_id',
            'items.created_at',
            'items.description',
            'items.id',
            'manufactured_at',
            'users.name as owner_name',
            'purchase_value',
            'purchased_at',
            'serial',
            'types.name AS type_name',
            'brands.name AS brand_name',
            'items.brand_id',
            DB::raw('  CONCAT_WS(" ", COALESCE(locations.city, ""), COALESCE(locations.state, ""), COALESCE(locations.country, "")) as location')
        )
            ->groupBy('items.id', 'users.name', 'types.name', 'brands.name');

        // Apply sorting
        $items = $query->orderBy($sortBy, $order);
        if ($request->paginate) {
            $items = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $itemsArray = $items->items();
            $totalCount = $items->total();
        } else {
            $itemsArray = $query->get()->toArray();
            $totalCount = count($itemsArray);
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
                        'path' => '/storage/' . $image->path
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
            'items' => $itemsArray,
            'count' => $totalCount,
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
            'purchase_value' => 'required|numeric|min:0', // Adjust according to your needs
            'type_id' => 'required|integer|exists:types,id', // Ensure type_id exists in the types table
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Image validation rules
            'purchased_at' => 'nullable|date', // Validate created_at as a nullable date
            'manufactured_at' => 'nullable|date', // Validate manufactured_at as a nullable date
        ]);
        $user = auth()->user();
        $validated['owned_by'] = $user->id;

        $item = Item::create($validated);

        // Assuming you have validated the request as shown earlier
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
                ItemImage::create([
                    'item_id' => $item->id, // Replace $itemId with the actual item ID
                    'path' => $imagePath,
                    'created_by' => $userId, // Assuming you are using authentication
                ]);
            }
        }


        return response()->json($item);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Find the item by its ID
        $item = Item::query()
            ->join('users', 'items.owned_by', '=', 'users.id')
            ->join('types', 'items.type_id', '=', 'types.id')
            ->where('items.id', $id)
            ->select('items.*', 'users.name as owner_name', 'types.name AS type_name') // Select relevant columns
            ->first();


        // Check if the item was found
        if (!$item) {
            return response()->json([
                'message' => 'Item not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Return the item as JSON
        return response()->json($item);
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
            'purchase_value' => 'required|numeric|min:0', // Adjust according to your needs
            'type_id' => 'required|integer|exists:types,id', // Ensure type_id exists in the types table
            'newImages.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'removedImages' => 'nullable|array',
            'description' => 'nullable|string', // Ensure description is a nullable string
            'purchased_at' => 'nullable|date', // Validate created_at as a nullable date
            'manufactured_at' => 'nullable|date', // Validate manufactured_at as a nullable date
            'brand_id' => 'nullable|integer|exists:brands,id', // Ensure type_id exists in the brands table
        ]);


        $item = Item::findOrFail($id);
        $item->fill($request->except('newImages', 'removedImages'));
        $item->save();

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
                ItemImage::create([
                    'item_id' => $item->id, // Replace $itemId with the actual item ID
                    'path' => $imagePath,
                    'created_by' => $userId, // Assuming you are using authentication
                ]);
            }
        }


        if ($request->removedImages) {
            foreach ($request->removedImages as $imageId) {
                // Find the image by ID
                $image = ItemImage::find($imageId);


                // Check if the image exists
                if ($image) {
                    // Delete the image file from storage
                    Storage::disk('public')->delete($image->path);

                    // Delete the image record from the database
                    $image->delete();
                }
            }
        }

        return response()->json($item);
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
        $item = Item::where('id', $id)->where('owned_by', $user->id)->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found or you do not have permission to delete it'], 404);
        }

        $itemImages = $item->images;

        // Delete the image files from storage
        foreach ($itemImages as $image) {
            Storage::disk('public')->delete($image->path);
        }

        // Delete the records from the item_images table
        $item->images()->delete();

        // Delete the item itself
        $item->delete();

        return response()->json(['message' => 'Item and associated images deleted successfully']);
    }

    public function getItemUnavailableDates(Request $request)
    {
        $itemId = $request->query('itemId');

        // Fetch unavailable dates for the specified itemId
        $unavailableDates = ItemUnavailableDate::where('item_id', $itemId)
            ->get(['unavailable_date']);



        // Flatten the collection by plucking the unavailable_date and convert to ISO 8601 format
        $dates = $unavailableDates->pluck('unavailable_date')->map(function ($date) {
            return $date;
        });

        // Convert to a plain array if needed
        $datesArray = $dates->all();

        return response()->json(['itemUnavailableDates' => $datesArray]);
    }

    function convertToUTCAndMySQLDate($isoDate)
    {
        // Create a DateTime object from the ISO 8601 date string
        $dateTime = new \DateTime($isoDate, new \DateTimeZone('UTC'));

        // Format the DateTime object to MySQL DATETIME format
        return $dateTime->format('Y-m-d H:i:s');
    }
    public function updateItemAvailability(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'unavailableDates' => 'nullable|array',
            'unavailableDates.*' => 'date', // Validates that each item is a valid date
            'id' => 'required|integer|exists:items,id',
        ]);

        // Extracting validated data
        $itemId = $validatedData['id'];
        $unavailableDates = $validatedData['unavailableDates'] ?? [];

        // Delete existing unavailable dates for this item
        ItemUnavailableDate::where('item_id', $itemId)->delete();




        // Insert new unavailable dates
        if (!empty($unavailableDates)) {


            // Convert each ISO 8601 date to UTC and MySQL format
            $convertedDates = array_map(function ($date) {
                return $this->convertToUTCAndMySQLDate($date);
            }, $validatedData['unavailableDates']);

            $dataToInsert = [];
            foreach ($convertedDates as $date) {
                $dataToInsert[] = [
                    'item_id' => $itemId,
                    'unavailable_date' => $date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert the new records in one query
            ItemUnavailableDate::insert($dataToInsert);
        }
        return response()->json(['message' => 'Item availability updated successfully.']);
    }
}
