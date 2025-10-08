<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\ContactFile;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Contact",
 *     description="Contact message management endpoints"
 * )
 */
class ContactController extends Controller
{
    /**
     * @OA\Post(
     *     path="/contact",
     *     summary="Send contact message",
     *     tags={"Contact"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","subject","message"},
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="subject", type="string", maxLength=255),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message sent successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'contact_number' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'questions' => 'nullable|string',
            'files.*' => 'file|max:10240|mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx,txt', // 10MB max per file
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $message = ContactMessage::create([
            'name' => $request->name,
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'subject' => $request->subject,
            'message' => $request->message,
            'questions' => $request->questions,
            'status' => 'unread',
        ]);

        // Handle file uploads
        if ($request->hasFile('files')) {
            $this->handleFileUploads($request->file('files'), $message);
        }

        // Send email notification to admin users
        try {
            $adminUsers = User::where('role', 'admin')->get();
            if ($adminUsers->count() > 0) {
                Notification::send($adminUsers, new ContactMessageReceived($message));
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            \Log::error('Failed to send contact notification email: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully'
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/contact",
     *     summary="Get all contact messages (admin only)",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Messages retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - Admin access required"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $messages = ContactMessage::latest()
            ->paginate($request->get('limit', 10));

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * @OA\Get(
     *     path="/contact/{contactMessage}",
     *     summary="Get single contact message (admin only)",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="contactMessage",
     *         in="path",
     *         description="Contact Message ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message retrieved successfully"
     *     )
     * )
     */
    public function show(ContactMessage $contactMessage)
    {
        // Mark as read when viewed
        if ($contactMessage->status === 'unread') {
            $contactMessage->update(['status' => 'read']);
        }

        // Load files relationship
        $contactMessage->load('files');

        return response()->json([
            'success' => true,
            'data' => $contactMessage
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/contact/{contactMessage}/status",
     *     summary="Update message status (admin only)",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="contactMessage",
     *         in="path",
     *         description="Contact Message ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"unread","read","replied"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message status updated successfully"
     *     )
     * )
     */
    public function updateStatus(Request $request, ContactMessage $contactMessage)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:unread,read,replied',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $contactMessage->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Message status updated successfully'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/contact/{contactMessage}",
     *     summary="Delete contact message (admin only)",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="contactMessage",
     *         in="path",
     *         description="Contact Message ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message deleted successfully"
     *     )
     * )
     */
    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/contact/unread-count",
     *     summary="Get unread messages count (admin only)",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Unread count retrieved successfully"
     *     )
     * )
     */
    public function unreadCount()
    {
        $count = ContactMessage::where('status', 'unread')->count();

        return response()->json([
            'success' => true,
            'data' => ['count' => $count]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/contact/recent",
     *     summary="Get recent contact messages for notifications (admin only)",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Recent messages retrieved successfully"
     *     )
     * )
     */
    public function recent()
    {
        $messages = ContactMessage::where('status', 'unread')
            ->latest()
            ->take(5)
            ->select('id', 'name', 'email', 'subject', 'message', 'created_at')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'name' => $message->name,
                    'email' => $message->email,
                    'subject' => $message->subject,
                    'preview' => \Str::limit($message->message, 50),
                    'time_ago' => $message->created_at->diffForHumans(),
                    'created_at' => $message->created_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * @OA\Get(
     *     path="/contact/stats/overview",
     *     summary="Get contact message statistics (admin only)",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Contact statistics retrieved successfully"
     *     )
     * )
     */
    public function stats()
    {
        $stats = [
            'total_messages' => ContactMessage::count(),
            'unread_messages' => ContactMessage::where('status', 'unread')->count(),
            'read_messages' => ContactMessage::where('status', 'read')->count(),
            'replied_messages' => ContactMessage::where('status', 'replied')->count(),
            'today_messages' => ContactMessage::whereDate('created_at', today())->count(),
            'this_week_messages' => ContactMessage::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'this_month_messages' => ContactMessage::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Handle file uploads for contact message
     */
    private function handleFileUploads($files, ContactMessage $message)
    {
        // Ensure files is always an array
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if ($file->isValid()) {
                // Generate unique filename
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $storedName = time() . '_' . uniqid() . '.' . $extension;
                
                // Store file in contact-files directory
                $path = $file->storeAs('contact-files', $storedName, 'public');
                
                // Create file record
                ContactFile::create([
                    'contact_message_id' => $message->id,
                    'original_name' => $originalName,
                    'stored_name' => $storedName,
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'file_extension' => $extension,
                    'is_safe' => $this->isFileSafe($file),
                ]);
            }
        }
    }

    /**
     * Check if uploaded file is safe
     */
    private function isFileSafe($file): bool
    {
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png', 
            'image/jpg',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain'
        ];

        $dangerousExtensions = ['exe', 'bat', 'cmd', 'scr', 'pif', 'com'];
        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($file->getMimeType(), $allowedMimeTypes) && 
               !in_array($extension, $dangerousExtensions);
    }

    /**
     * Download contact file
     */
    public function downloadFile($messageId, $fileId)
    {
        $message = ContactMessage::findOrFail($messageId);
        $file = $message->files()->findOrFail($fileId);
        
        $filePath = storage_path('app/public/' . $file->file_path);
        
        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        return response()->download($filePath, $file->original_name);
    }
}
