<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomepageContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Homepage Content",
 *     description="Homepage content management endpoints"
 * )
 */
class HomepageContentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/homepage-content",
     *     summary="Get all homepage content",
     *     tags={"Homepage Content"},
     *     @OA\Parameter(
     *         name="section",
     *         in="query",
     *         description="Filter by section",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Homepage content retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = HomepageContent::active()->ordered();
        
        if ($request->has('section')) {
            $query->bySection($request->section);
        }
        
        $content = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $content
        ]);
    }

    /**
     * @OA\Get(
     *     path="/admin/homepage-content",
     *     summary="Get all homepage content (admin)",
     *     tags={"Homepage Content"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="section",
     *         in="query",
     *         description="Filter by section",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Homepage content retrieved successfully"
     *     )
     * )
     */
    public function adminIndex(Request $request)
    {
        $query = HomepageContent::ordered();
        
        if ($request->has('section')) {
            $query->bySection($request->section);
        }
        
        $content = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $content
        ]);
    }

    /**
     * @OA\Post(
     *     path="/admin/homepage-content",
     *     summary="Create new homepage content",
     *     tags={"Homepage Content"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"section", "title", "type"},
     *             @OA\Property(property="section", type="string", example="real_spaces"),
     *             @OA\Property(property="type", type="string", example="video"),
     *             @OA\Property(property="title", type="string", example="Modern Learning Spaces"),
     *             @OA\Property(property="description", type="string", example="Innovative furniture for educational environments"),
     *             @OA\Property(property="video_url", type="string", example="https://www.youtube.com/watch?v=dQw4w9WgXcQ"),
     *             @OA\Property(property="link_url", type="string", example="https://example.com"),
     *             @OA\Property(property="sort_order", type="integer", example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Homepage content created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section' => 'required|string|max:255',
            'type' => 'required|string|in:video,image,text',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'nullable|url',
            'link_url' => 'nullable|url',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('homepage/thumbnails', 'public');
            $data['thumbnail'] = $thumbnailPath;
        }

        // Set default sort order if not provided
        if (!isset($data['sort_order'])) {
            $maxOrder = HomepageContent::where('section', $data['section'])->max('sort_order');
            $data['sort_order'] = $maxOrder ? $maxOrder + 1 : 1;
        }

        $content = HomepageContent::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Homepage content created successfully',
            'data' => $content
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/admin/homepage-content/{id}",
     *     summary="Get specific homepage content",
     *     tags={"Homepage Content"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Content ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Homepage content retrieved successfully"
     *     )
     * )
     */
    public function show($id)
    {
        $content = HomepageContent::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $content
        ]);
    }

    /**
     * @OA\Put(
     *     path="/admin/homepage-content/{id}",
     *     summary="Update homepage content",
     *     tags={"Homepage Content"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Content ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Title"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="video_url", type="string", example="https://www.youtube.com/watch?v=newvideo"),
     *             @OA\Property(property="is_active", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Homepage content updated successfully"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $content = HomepageContent::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'section' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:video,image,text',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'nullable|url',
            'link_url' => 'nullable|url',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($content->thumbnail) {
                Storage::disk('public')->delete($content->thumbnail);
            }
            
            $thumbnailPath = $request->file('thumbnail')->store('homepage/thumbnails', 'public');
            $data['thumbnail'] = $thumbnailPath;
        }

        $content->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Homepage content updated successfully',
            'data' => $content
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/admin/homepage-content/{id}",
     *     summary="Delete homepage content",
     *     tags={"Homepage Content"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Content ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Homepage content deleted successfully"
     *     )
     * )
     */
    public function destroy($id)
    {
        $content = HomepageContent::findOrFail($id);

        // Delete thumbnail file if exists
        if ($content->thumbnail) {
            Storage::disk('public')->delete($content->thumbnail);
        }

        $content->delete();

        return response()->json([
            'success' => true,
            'message' => 'Homepage content deleted successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/admin/homepage-content/reorder",
     *     summary="Reorder homepage content",
     *     tags={"Homepage Content"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"items"},
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="sort_order", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Content reordered successfully"
     *     )
     * )
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:homepage_contents,id',
            'items.*.sort_order' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->items as $item) {
            HomepageContent::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Content reordered successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/admin/homepage-content/upload-video",
     *     summary="Upload video file",
     *     tags={"Homepage Content"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="video",
     *                     description="Video file to upload",
     *                     type="string",
     *                     format="binary"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Video uploaded successfully"
     *     )
     * )
     */
    public function uploadVideo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'video' => 'required|file|mimes:mp4,mov,avi,wmv|max:51200' // 50MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('homepage/videos', 'public');
            
            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully',
                'data' => [
                    'path' => $videoPath,
                    'url' => asset('storage/' . $videoPath)
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No video file provided'
        ], 400);
    }
}
