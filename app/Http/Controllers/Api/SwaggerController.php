<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\Schema(
 *     schema="User",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="username", type="string", example="admin"),
 *     @OA\Property(property="name", type="string", example="Admin User"),
 *     @OA\Property(property="email", type="string", format="email", example="admin@vsfurniture.com"),
 *     @OA\Property(property="role", type="string", enum={"admin","user"}, example="admin"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Product",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Office Chair"),
 *     @OA\Property(property="description", type="string", example="Ergonomic office chair"),
 *     @OA\Property(property="model", type="string", example="CH-001"),
 *     @OA\Property(property="price", type="number", format="float", example=299.99),
 *     @OA\Property(property="stock_quantity", type="integer", example=50),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="category_name", type="string", example="Office Chairs"),
 *     @OA\Property(property="image", type="string", nullable=true),
 *     @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Category",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Office Chairs"),
 *     @OA\Property(property="description", type="string", example="Ergonomic office chairs"),
 *     @OA\Property(property="slug", type="string", example="office-chairs"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Order",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order_number", type="string", example="ORD-20250808-0001"),
 *     @OA\Property(property="customer_name", type="string", example="John Doe"),
 *     @OA\Property(property="customer_email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="customer_phone", type="string", example="+1234567890"),
 *     @OA\Property(property="customer_address", type="string", example="123 Main St"),
 *     @OA\Property(property="total_amount", type="number", format="float", example=599.98),
 *     @OA\Property(property="status", type="string", enum={"pending","confirmed","shipped","delivered","cancelled"}, example="pending"),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="ContactMessage",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="subject", type="string", example="Inquiry about products"),
 *     @OA\Property(property="message", type="string", example="I would like to know more about your office chairs"),
 *     @OA\Property(property="status", type="string", enum={"unread","read","replied"}, example="unread"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class SwaggerController extends Controller
{
    //
}
