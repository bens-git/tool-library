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
use App\Models\Archetype;
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
        $archetypeId = $request->query('archetype_id');
        $userId = $request->query('user_id');
        $search = $request->query('search', '');
        $resource = $request->query('resource');
        $itemsPerPage = $request->query('itemsPerPage', 9);
        $page = $request->query('page', 1);

        // Build the query with eager loading
        $query = Item::with(['archetype', 'accessValue']);

        // Apply archetype filter
        if (!empty($archetypeId)) {
            $query->where('archetype_id', '=', $archetypeId);
        }

        // Apply user filter
        if (!empty($userId)) {
            $query->where('owned_by', '=', $userId);
        }

        // Apply resource type filter
        if (!empty($resource)) {
            $query->whereHas('archetype', function ($q) use ($resource) {
                $q->where('resource', '=', $resource);
            });
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

        $item = new Item();
        $item->archetype_id = $archetype->id;
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
     * Now saves to items.thumbnail_path (single image per item).
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
                // Delete old thumbnail if exists
                if ($item->thumbnail_path) {
                    Storage::disk('public')->delete($item->thumbnail_path);
                }

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

                    // Save to items table (single image per item)
                    $item->thumbnail_path = 'images/' . $filename;
                    $item->save();
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
        $item = Item::with('archetype', 'accessValue')
            ->join('users', 'items.owned_by', '=', 'users.id')
            ->join('archetypes', 'items.archetype_id', '=', 'archetypes.id')
            ->where('items.id', $id)
            ->select('items.*', 'users.name as owner_name', 'archetypes.name AS archetype_name')
            ->first();

        // Add thumbnail URL if thumbnail exists
        if ($item->thumbnail_path) {
            $item->thumbnail_url = asset('storage/' . $item->thumbnail_path);
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
            'thumbnail_url' => 'nullable|string',
        ]);

        $item = DB::transaction(function () use ($request, $id) {
            $archetype = Archetype::findOrFail($request->archetype['id']);

            /** @var Item $item */
            $item = Item::findOrFail($id);
            $item->archetype_id = $archetype->id;
            $item->description = $request->description;
            $item->serial = $request->serial;
            $item->purchase_value = $request->purchase_value;
            $item->purchased_at = Carbon::parse($request->purchased_at)->format('Y-m-d H:i:s');
            $item->manufactured_at = $request->manufactured_at ? Carbon::parse($request->manufactured_at)->format('Y-m-d H:i:s') : null;
            $item->save();
            
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
            $item = Item::with('usages')->where('id', $id)->where('owned_by', $user->id)->first();

            if (!$item) {
                return response()->json(['message' => 'Item not found or you do not have permission to delete it'], 404);
            }

            $hasActiveUsage = $item->usages->contains('status', 'active');
            if ($hasActiveUsage) {
                abort(404, "Item can not be deleted because it is currently rented");
            }

            // Delete thumbnail if exists
            if ($item->thumbnail_path) {
                Storage::disk('public')->delete($item->thumbnail_path);
            }

            $item->usages()->delete();
            $item->delete();
        });
        
        return response()->json(['message' => 'Item and associated thumbnail deleted successfully']);
    }

    /**
     * Get featured items - one per archetype, with pagination support
     * Now uses thumbnail_path instead of images
     */
    public function featured(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $page = $request->query('page', 1);
        $itemsPerPage = $request->query('itemsPerPage', 6);

        // Get distinct archetype IDs that have items with thumbnail_path AND a valid archetype
        $allArchetypeIds = Item::whereNotNull('thumbnail_path')
            ->whereHas('archetype')
            ->whereNotNull('archetype_id')
            ->distinct()
            ->pluck('archetype_id')
            ->toArray();

        // Shuffle archetype IDs for randomness
        shuffle($allArchetypeIds);

        // Calculate pagination
        $totalArchetypes = count($allArchetypeIds);
        $archetypeIdsArray = array_slice($allArchetypeIds, ($page - 1) * $itemsPerPage, $itemsPerPage);

        if (empty($archetypeIdsArray)) {
            return response()->json([
                'data' => [],
                'total' => $totalArchetypes,
                'current_page' => $page,
                'last_page' => ceil($totalArchetypes / $itemsPerPage),
            ]);
        }

        // Get one random item per archetype
        $items = [];
        foreach ($archetypeIdsArray as $archetypeId) {
            $item = Item::where('archetype_id', $archetypeId)
                ->whereNotNull('thumbnail_path')
                ->whereHas('archetype')
                ->with(['archetype', 'accessValue'])
                ->inRandomOrder()
                ->first();
            
            if ($item) {
                $items[] = $item;
            }
        }

        return response()->json([
            'data' => ItemResource::collection(collect($items))->resolve(),
            'total' => $totalArchetypes,
            'current_page' => $page,
            'last_page' => ceil($totalArchetypes / $itemsPerPage),
        ]);
    }
}

