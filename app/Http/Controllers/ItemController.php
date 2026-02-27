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
        $getAll = $request->query('get_all', false);

        // Check if any search filter is active
        $hasFilters = $brandId || $archetypeId || $userId || $categoryId || $usageId || $search;

        // If no filters and not requesting all, return empty response
        if (!$hasFilters && !$getAll) {
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
        $item->archetype_id = $archetype->id;
        $item->brand_id = $brand->id ?? null;
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
     * Store a newly created image in storage with compression.
     */
    public function storeImage(Request $request, $id)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:12000',
        ], [
            'image.max' => 'The uploaded file size must not exceed 12 MB.',
            'image.image' => 'The uploaded file must be an image.',
        ]);

        $user = Auth::user();
        $item = Item::findOrFail($id);

        DB::transaction(function () use ($request, $item, $user) {
            if ($request->hasFile('image')) {
                $image = $request->file('image');

                // Generate a unique filename
                $timestamp = now()->format('YmdHis');
                $userId = $user->id;
                $filename = "{$timestamp}_{$userId}.jpg";

                // Get image info and calculate new dimensions
                $imageInfo = getimagesize($image->getRealPath());
                $width = $imageInfo[0];
                $height = $imageInfo[1];
                
                // Max dimension 1200px
                $maxDimension = 1200;
                if ($width > $maxDimension || $height > $maxDimension) {
                    if ($width > $height) {
                        $newWidth = $maxDimension;
                        $newHeight = round(($height / $width) * $maxDimension);
                    } else {
                        $newHeight = $maxDimension;
                        $newWidth = round(($width / $height) * $maxDimension);
                    }
                } else {
                    $newWidth = $width;
                    $newHeight = $height;
                }

                // Create image resource based on type
                switch ($imageInfo['mime']) {
                    case 'image/jpeg':
                        $source = imagecreatefromjpeg($image->getRealPath());
                        break;
                    case 'image/png':
                        $source = imagecreatefrompng($image->getRealPath());
                        break;
                    case 'image/gif':
                        $source = imagecreatefromgif($image->getRealPath());
                        break;
                    default:
                        $source = imagecreatefromstring(file_get_contents($image->getRealPath()));
                }

                if ($source) {
                    // Create new image with white background
                    $newImage = imagecreatetruecolor($newWidth, $newHeight);
                    $white = imagecolorallocate($newImage, 255, 255, 255);
                    imagefill($newImage, 0, 0, $white);
                    
                    // Resample
                    imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    
                    // Save compressed JPEG (quality 80%)
                    $outputPath = storage_path('app/public/images/' . $filename);
                    imagejpeg($newImage, $outputPath, 80);
                    
                    // Free memory
                    imagedestroy($source);
                    imagedestroy($newImage);

                    // Save to database
                    ItemImage::create([
                        'item_id' => $item->id,
                        'path' => 'images/' . $filename,
                        'created_by' => $userId,
                    ]);
                }
            }
        });
        
        return response()->json(['success' => true, 'message' => 'Image created']);
    }

    /**
     * Display the specified item.
     */
    public function show($id)
    {
        $item = Item::with('archetype', 'brand', 'images', 'accessValue')
            ->join('users', 'items.owned_by', '=', 'users.id')
            ->join('archetypes', 'items.archetype_id', '=', 'archetypes.id')
            ->where('items.id', $id)
            ->select('items.*', 'users.name as owner_name', 'archetypes.name AS archetype_name')
            ->first();

        foreach ($item->images as $image) {
            /** @var ItemImage $image */
            $image->url = $image->url;
        }

        return response()->json(['data' => $item]);
    }

    /**
     * Update the specified item in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'purchase_value' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'serial' => 'nullable|string',
            'purchased_at' => 'required|date',
            'manufactured_at' => 'nullable|date',
            'make_item_unavailable' => 'boolean',
            'images' => 'nullable|array',
            'images.*.id' => 'required|integer',
            'images.*.path' => 'required|string',
            'brand.id' => 'nullable|numeric|exists:brands,id',
        ]);

        $item = DB::transaction(function () use ($request, $id) {
            $archetype = Archetype::findOrFail($request->archetype['id']);
            /** @var Brand|null $brand */
            $brand = null;
            if ($request->brand) {
                $brand = Brand::findOrFail($request->brand['id']);
            }

            /** @var Item $item */
            $item = Item::findOrFail($id);
            $item->archetype_id = $archetype->id;
            $item->brand_id = $brand->id ?? null;
            $item->description = $request->description;
            $item->serial = $request->serial;
            $item->purchase_value = $request->purchase_value;
            $item->make_item_unavailable = $request->make_item_unavailable;
            $item->purchased_at = Carbon::parse($request->purchased_at)->format('Y-m-d H:i:s');
            $item->manufactured_at = $request->manufactured_at ? Carbon::parse($request->manufactured_at)->format('Y-m-d H:i:s') : null;
            $item->save();

            $postedImageIds = collect($request['images'])->pluck('id')->filter()->toArray();
            $existingImages = ItemImage::where('item_id', $id)->get();
            $existingImageIds = $existingImages->pluck('id')->toArray();
            $imageIdsToDelete = array_diff($existingImageIds, $postedImageIds);

            foreach ($imageIdsToDelete as $imageId) {
                $image = ItemImage::find($imageId);
                if ($image) {
                    Storage::disk('public')->delete($image->path);
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

            $hasActiveRental = $item->rentals->contains('status', 'active');
            if ($hasActiveRental) {
                abort(404, "Item can not be deleted because it is currently rented");
            }

            $itemImages = $item->images;
            foreach ($itemImages as $image) {
                /** @var ItemImage $image */
                Storage::disk('public')->delete($image->path);
            }

            $item->images()->delete();
            $item->rentals()->delete();
            $item->delete();
        });
        
        return response()->json(['message' => 'Item and associated images deleted successfully']);
    }

    /**
     * Get featured items - one per archetype (up to 6 unique archetypes)
     */
    public function featured(): AnonymousResourceCollection
    {
        // Get distinct archetype IDs that have items with images AND a valid archetype
        $archetypeIds = Item::whereHas('images')
            ->whereHas('archetype')
            ->whereNotNull('archetype_id')
            ->distinct()
            ->pluck('archetype_id');

        // Shuffle and take up to 6 archetype IDs
        $archetypeIdsArray = $archetypeIds->shuffle()->take(6)->toArray();

        if (empty($archetypeIdsArray)) {
            return ItemResource::collection(collect([]));
        }

        // Get one random item per archetype
        $items = [];
        foreach ($archetypeIdsArray as $archetypeId) {
            $item = Item::where('archetype_id', $archetypeId)
                ->whereHas('images')
                ->whereHas('archetype')
                ->with(['images', 'brand', 'archetype', 'accessValue'])
                ->inRandomOrder()
                ->first();
            
            if ($item) {
                $items[] = $item;
            }
        }

        return ItemResource::collection(collect($items));
    }

    public function getItemUnavailableDates($itemId)
    {
        $unavailableDates = ItemUnavailableDate::where('item_id', $itemId)
            ->get(['unavailable_date']);

        $dates = $unavailableDates->pluck('unavailable_date')->map(function ($date) {
            return $date;
        });

        return response()->json(['data' => $dates->all()]);
    }

    function convertToUTCAndMySQLDate($isoDate)
    {
        $dateTime = new \DateTime($isoDate, new \DateTimeZone('UTC'));
        return $dateTime->format('Y-m-d H:i:s');
    }

    public function updateItemAvailability(Request $request, $itemId)
    {
        $validatedData = $request->validate([
            'unavailableDates' => 'nullable|array',
            'unavailableDates.*' => 'date',
            'itemId' => 'required|integer|exists:items,id'
        ]);

        DB::transaction(function () use ($validatedData) {
            $unavailableDates = $validatedData['unavailableDates'] ?? [];
            ItemUnavailableDate::where('item_id', $validatedData['itemId'])->delete();

            if (!empty($unavailableDates)) {
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

                ItemUnavailableDate::insert($dataToInsert);
            }
        });
        
        return response()->json(['message' => 'Item availability updated successfully.']);
    }
}

