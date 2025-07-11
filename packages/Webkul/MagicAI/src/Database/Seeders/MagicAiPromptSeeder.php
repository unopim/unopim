<?php

namespace Webkul\MagicAI\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MagicAiPromptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($parameters = []): void
    {
        $prompts = [
            [
                'prompt'     => 'Write a comprehensive description of @name, including its features, benefits, technical specifications, and usage instructions.',
                'title'      => 'Detailed Product Description',
                'type'       => 'product',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'prompt'     => 'Create a detailed overview of @name, highlighting its key features, advantages, and target audience like @brand and @color.',
                'title'      => 'Product Overview',
                'type'       => 'product',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'prompt'     => 'List the key features and benefits of @name, explaining how they add value to the customer\'s life.',
                'title'      => 'Product Features and Benefits',
                'type'       => 'product',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'prompt'     => 'Provide a detailed list of technical specifications for @name, including dimensions, materials, and compatibility.',
                'title'      => 'Product Technical Specifications',
                'type'       => 'product',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'prompt'     => 'Write a guide on how to care for and maintain @name, including tips for cleaning, storage, and troubleshooting.',
                'title'      => 'Product Care and Maintenance',
                'type'       => 'product',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'prompt'     => 'Write a catchy and concise tagline for @name that captures its essence and benefits.',
                'title'      => 'Product Tagline',
                'type'       => 'product',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'prompt'     => 'Summarize the key features and benefits of @name in 50-60 words.',
                'title'      => 'Product Summary',
                'type'       => 'product',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'prompt'     => 'Create an attention-grabbing headline for @name that highlights its unique selling point.',
                'title'      => 'Product Headline',
                'type'       => 'product',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'prompt'     => 'Write a brief and concise description of @name, focusing on its key advantages and target audience.',
                'title'      => 'Product Brief',
                'type'       => 'product',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'prompt'     => 'Craft a concise elevator pitch for @name, summarizing its purpose, benefits, and unique selling point in 30-40 words.',
                'title'      => 'Product Elevator Pitch',
                'type'       => 'product',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('magic_ai_prompts')->insert($prompts);
    }
}
