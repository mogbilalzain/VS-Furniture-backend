<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ContactMessage;

class ContactMessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $messages = [
            [
                'name' => 'Ahmed Al-Mansouri',
                'email' => 'ahmed.mansouri@gmail.com',
                'contact_number' => '+971501234567',
                'subject' => 'Office Furniture Inquiry',
                'message' => 'I am interested in your ergonomic office chairs and desks. We are setting up a new office in Dubai Marina and need furniture for 50 employees. Could you please provide a detailed quote?',
                'questions' => 'Do you offer bulk discounts for large orders? What is your delivery timeline?',
                'status' => 'unread',
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@company.com',
                'contact_number' => '+971507654321',
                'subject' => 'Conference Room Setup',
                'message' => 'We need to furnish our new conference room. Looking for a large conference table that can seat 12 people, along with comfortable chairs.',
                'questions' => 'What materials are available for the conference table? Do you provide installation services?',
                'status' => 'read',
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(4),
            ],
            [
                'name' => 'Mohammed Hassan',
                'email' => 'mohammed.hassan@business.ae',
                'contact_number' => '+971509876543',
                'subject' => 'Reception Area Furniture',
                'message' => 'Looking for modern reception area furniture including reception desk, waiting chairs, and coffee tables. Our space is about 30 square meters.',
                'questions' => 'Can you provide 3D visualization of the setup? What is your warranty policy?',
                'status' => 'replied',
                'created_at' => now()->subHours(8),
                'updated_at' => now()->subHours(6),
            ],
            [
                'name' => 'Lisa Chen',
                'email' => 'lisa.chen@startup.com',
                'contact_number' => '+971502345678',
                'subject' => 'Startup Office Package',
                'message' => 'We are a tech startup looking for a complete office furniture package for 15 people. Need desks, chairs, storage solutions, and a small meeting area.',
                'questions' => 'Do you have packages for startups? Can we get flexible payment terms?',
                'status' => 'unread',
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ],
            [
                'name' => 'Omar Al-Zahra',
                'email' => 'omar.alzahra@consulting.ae',
                'contact_number' => '+971505432109',
                'subject' => 'Executive Office Furniture',
                'message' => 'Need high-end executive office furniture for our managing director office. Looking for executive desk, leather chairs, bookshelf, and meeting table.',
                'questions' => 'What premium materials do you offer? Can you arrange a showroom visit?',
                'status' => 'read',
                'created_at' => now()->subHours(1),
                'updated_at' => now()->subMinutes(45),
            ],
            [
                'name' => 'Jennifer Smith',
                'email' => 'jennifer.smith@international.com',
                'contact_number' => '+971508765432',
                'subject' => 'Delivery to Abu Dhabi',
                'message' => 'We have selected furniture from your catalog and need delivery to Abu Dhabi. The order includes 25 workstations and 30 chairs.',
                'questions' => 'What are the delivery charges to Abu Dhabi? How long does delivery take?',
                'status' => 'unread',
                'created_at' => now()->subMinutes(15),
                'updated_at' => now()->subMinutes(15),
            ],
            [
                'name' => 'Khalid Al-Rashid',
                'email' => 'khalid.rashid@government.ae',
                'contact_number' => '+971503456789',
                'subject' => 'Government Office Project',
                'message' => 'We have a large government office project requiring furniture for 200+ employees across multiple floors. Need to discuss bulk pricing and specifications.',
                'questions' => 'Do you work with government entities? Can you provide compliance certificates?',
                'status' => 'replied',
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subHours(12),
            ],
            [
                'name' => 'Maria Rodriguez',
                'email' => 'maria.rodriguez@design.com',
                'contact_number' => '+971506789012',
                'subject' => 'Custom Design Request',
                'message' => 'We are an interior design company and need custom furniture pieces for a luxury office project. Looking for unique designs that match our client specifications.',
                'questions' => 'Do you offer custom design services? What is the lead time for custom pieces?',
                'status' => 'read',
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(2),
            ]
        ];

        foreach ($messages as $messageData) {
            ContactMessage::create($messageData);
        }
    }
}