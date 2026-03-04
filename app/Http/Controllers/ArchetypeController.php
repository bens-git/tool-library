<?php

namespace App\Http\Controllers;

use App\Models\Archetype;
use App\Models\ArchetypeAccessValue;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ArchetypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getArchetypesWithItems(Request $request)
    {
        // Get request parameters
        $page = $request->input('page', 1);
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $sortBy = $request->input('sortBy.0.key', 'archetypes.id'); // Default sort by id
        $order = $request->input('sortBy.0.order', 'asc'); // Default order ascending
        $search = $request->input('search', '');
        $startDate = $request->input('startDate', '1970-01-01');
        $endDate = $request->input('endDate', '1970-01-01');
        $resource = [$request->input('resource', 'TOOL'), 'ANY'];

        // Base query for data
        $query = Archetype::query()
            ->leftJoin('items', 'items.archetype_id', '=', 'archetypes.id')
            ->leftJoin('users as owner', 'items.owned_by', '=', 'owner.id')
            ->leftJoin('archetype_access_values', 'archetypes.id', '=', 'archetype_access_values.archetype_id');

        $query->leftJoin('rentals', function ($join) use ($startDate, $endDate) {
            $join->on('items.id', '=', 'rentals.item_id')
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->where('rentals.starts_at', '<=', $endDate)
                        ->where('rentals.ends_at', '>=', $startDate);
                });
        });

        $query->select(
            'archetypes.id',
            'archetypes.name',
            'archetypes.created_by',
            'archetypes.resource',
            DB::raw('COALESCE(archetype_access_values.current_daily_rate, ' . ArchetypeAccessValue::DEFAULT_DAILY_RATE . ') as current_daily_rate'),
            DB::raw('COALESCE(archetype_access_values.base_credit_value, ' . ArchetypeAccessValue::DEFAULT_DAILY_RATE . ') as base_credit_value'),
            DB::raw('COALESCE(archetype_access_values.vote_count, 0) as vote_count'),
            DB::raw('COUNT(DISTINCT CASE WHEN rentals.id IS NULL THEN items.id END) as available_item_count'),
            DB::raw('COUNT(DISTINCT CASE WHEN rentals.id IS NOT NULL THEN rentals.item_id END) as rented_item_count'),
            DB::raw('GROUP_CONCAT(DISTINCT items.id ORDER BY items.owned_by ASC SEPARATOR ", ") as item_ids')
        );

        $query->groupBy('archetypes.id', 'archetypes.name', 'archetypes.created_by', 'archetypes.resource', 'archetype_access_values.current_daily_rate', 'archetype_access_values.base_credit_value', 'archetype_access_values.vote_count');

        // Apply search filter if needed
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('archetypes.name', 'like', '%' . $search . '%')
                    ->orWhere('items.description', 'like', '%' . $search . '%');
            });
        }

        // Apply user filter for specific paths
        if ($request->path() == 'api/user/archetypes') {
            $user = $request->user();
            $query->where('archetypes.created_by', $user->id);
        }

        // Apply archetype filter if provided
        if ($request->filled('archetypeId')) {
            $query->where('archetypes.id', '=', $request->input('archetypeId'));
        }

        // Apply resource filter if provided
        $query->whereIn('resource', $resource);

        // Apply the HAVING clause to filter archetypes with no items
        $query->havingRaw('available_item_count > 0 OR rented_item_count > 0');

        // Apply sorting
        $query->orderBy($sortBy, $order);

        // Apply pagination
        $archetypes = $query->paginate($itemsPerPage, ['*'], 'page', $page);
        $archetypesArray = $archetypes->items();
        $totalCount = $archetypes->total();

        $archetypeIds = array_column($archetypesArray, 'id');

        // Fetch random item thumbnails for archetypes missing archetype images
        $itemThumbnails = Item::whereIn('archetype_id', $archetypeIds)
            ->whereNotNull('thumbnail_path')
            ->select('archetype_id', 'thumbnail_path')
            ->inRandomOrder()
            ->get()
            ->groupBy('archetype_id');

        $images = [];
        foreach ($itemThumbnails as $archetypeId => $items) {
            if (!isset($images[$archetypeId])) {
                $firstItem = $items->first();
                $images[$archetypeId] = [
                    [
                        'path' => '/storage/' . $firstItem->thumbnail_path
                    ]
                ];
            }
        }

        // Combine archetypes with their images
        foreach ($archetypesArray as &$archetype) {
            $archetype['images'] = $images[$archetype['id']] ?? null;
        }

        $response['data'] = $archetypesArray;
        $response['total'] = $totalCount;

        return response()->json($response);
    }


    public function getResources()
    {
        $resources = getEnumValues('archetypes', 'resource');
        $response['data'] = $resources;
        $response['total'] = count($resources);

        return response()->json($response);
    }


    public function index(Request $request)
    {
        // Get request parameters
        $page = $request->input('page', 1);
        $itemsPerPage = $request->input('itemsPerPage', -1); // Default to all for autocomplete
        $sortBy = $request->input('sortBy');
        $search = $request->input('search');
        $resource = $request->input('resource');

        // Base query for data
        $query = Archetype::query()
            ->leftJoin('items', 'items.archetype_id', '=', 'archetypes.id')
            ->leftJoin('archetype_access_values', 'archetypes.id', '=', 'archetype_access_values.archetype_id')
            ->select(
                'archetypes.id',
                'archetypes.name',
                'archetypes.created_by',
                'archetypes.description',
                'archetypes.notes',
                'archetypes.code',
                'archetypes.resource',
                DB::raw('COALESCE(archetype_access_values.current_daily_rate, ' . ArchetypeAccessValue::DEFAULT_DAILY_RATE . ') as current_daily_rate'),
                DB::raw('COALESCE(archetype_access_values.base_credit_value, ' . ArchetypeAccessValue::DEFAULT_DAILY_RATE . ') as base_credit_value'),
                DB::raw('COALESCE(archetype_access_values.vote_count, 0) as vote_count'),
                DB::raw('GROUP_CONCAT(DISTINCT items.id ORDER BY items.owned_by ASC SEPARATOR ", ") as item_ids'),
            );

        $query->groupBy(
            'archetypes.id',
            'archetypes.name',
            'archetypes.created_by',
            'archetypes.description',
            'archetypes.notes',
            'archetypes.code',
            'archetypes.resource',
            'archetype_access_values.current_daily_rate',
            'archetype_access_values.base_credit_value',
            'archetype_access_values.vote_count'
        );

        // Apply search filter if needed
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('archetypes.name', 'like', '%' . $search . '%');
            });
        }

        if (!empty($resource)) {
            $query->where(function ($query) use ($resource) {
                $query->where('archetypes.resource', '=',  $resource);
            });
        }

        // Check if the path is 'me/items' and filter by user if so
        if ($request->path() == 'api/me/archetypes') {
            $user = $request->user();
            $query->where('archetypes.created_by', $user->id);
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
            $archetypesArray = $query->get()->toArray();
            $totalCount = count($archetypesArray);
        } else {
            $archetypes = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $archetypesArray = $archetypes->items();
            $totalCount = $archetypes->total();
        }

        $archetypeIds = array_column($archetypesArray, 'id');

        // Handle empty results
        if (empty($archetypeIds)) {
            return response()->json([
                'total' => 0,
                'data' => []
            ]);
        }

        // Fetch the archetype images
        $archetypeImages = DB::table('archetype_images')
            ->select('archetype_id', 'id', 'path')
            ->whereIn('archetype_id', $archetypeIds)
            ->get()
            ->groupBy('archetype_id');

        // Determine which archetypes are missing images
        $archetypesMissingImages = array_diff($archetypeIds, array_keys($archetypeImages->toArray()));

        // Fetch random item thumbnails for archetypes missing archetype images
        $itemThumbnails = Item::whereIn('archetype_id', $archetypesMissingImages)
            ->whereNotNull('thumbnail_path')
            ->select('archetype_id', 'thumbnail_path')
            ->inRandomOrder()
            ->get()
            ->groupBy('archetype_id');

        // Combine both archetype images and item thumbnails
        $combinedImages = $archetypeImages->mapWithKeys(function ($imageGroup, $archetypeId) {
            return [
                $archetypeId => $imageGroup->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'path' => '/storage/' . $image->path
                    ];
                })
            ];
        });

        // Merge in the item thumbnails where archetype images are missing
        foreach ($itemThumbnails as $archetypeId => $items) {
            if (!isset($combinedImages[$archetypeId])) {
                $firstItem = $items->first();
                $combinedImages[$archetypeId] = collect([
                    [
                        'path' => '/storage/' . $firstItem->thumbnail_path
                    ]
                ]);
            }
        }

        // Combine archetypes with their images
        foreach ($archetypesArray as &$archetype) {
            $archetype['images'] = $combinedImages->get($archetype['id'], []);
        }

        return response()->json([
            'total' => $totalCount,
            'data' => $archetypesArray
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $archetype = Archetype::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $archetype
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:archetypes',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'code' => 'nullable|string|max:255',
            'resource' => 'nullable|string',
        ]);
        $user = Auth::user();

        $validated['created_by'] = $user->id;

        $archetype = Archetype::create($validated);

        return response()->json(['success' => true, 'data' => $archetype, 'message' => 'Archetype created']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => "required|string|max:255|unique:archetypes,name,{$id}",
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'code' => 'nullable|string|max:255',
            'resource' => 'nullable|string',
        ]);

        $archetype = DB::transaction(function () use ($validated, $id) {
            $archetype = Archetype::findOrFail($id);
            $archetype->fill($validated);
            $archetype->save();

            return $archetype;
        });

        return response()->json(['success' => true, 'message' => 'Archetype saved', 'data' => $archetype]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $archetype = Archetype::where('id', $id)->where('created_by', $user->id)->first();
        if (!$archetype) {
            return response()->json(['message' => 'Archetype not found or you do not have permission to delete it'], 404);
        }

        // Check if there are related items
        $items = Item::where('archetype_id', '=', $archetype->id);
        if ($items->count() > 0) {
            return response()->json(['message' => 'There are ' . $items->count() . ' items associated with this archetype. You must delete them first.'], 404);
        }

        $archetype->delete();

        return response()->json(['success' => true, 'message' => 'Archetype deleted']);
    }
}

