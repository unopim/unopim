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
                'purpose'    => 'text_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Create a detailed overview of @name, highlighting its key features, advantages, and target audience like @brand and @color.',
                'title'      => 'Product Overview',
                'type'       => 'product',
                'purpose'    => 'text_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'List the key features and benefits of @name, explaining how they add value to the customer\'s life.',
                'title'      => 'Product Features and Benefits',
                'type'       => 'product',
                'purpose'    => 'text_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Provide a detailed list of technical specifications for @name, including dimensions, materials, and compatibility.',
                'title'      => 'Product Technical Specifications',
                'type'       => 'product',
                'purpose'    => 'text_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Write a guide on how to care for and maintain @name, including tips for cleaning, storage, and troubleshooting.',
                'title'      => 'Product Care and Maintenance',
                'type'       => 'product',
                'purpose'    => 'text_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Write a catchy and concise tagline for @name that captures its essence and benefits.',
                'title'      => 'Product Tagline',
                'type'       => 'product',
                'purpose'    => 'text_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Summarize the key features and benefits of @name in 50-60 words.',
                'title'      => 'Product Summary',
                'type'       => 'product',
                'purpose'    => 'text_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Create an attention-grabbing headline for @name that highlights its unique selling point.',
                'title'      => 'Product Headline',
                'type'       => 'product',
                'purpose'    => 'text_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Write a brief and concise description of @name, focusing on its key advantages and target audience.',
                'title'      => 'Product Brief',
                'type'       => 'product',
                'purpose'    => 'text_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Craft a concise elevator pitch for @name, summarizing its purpose, benefits, and unique selling point in 30-40 words.',
                'title'      => 'Product Elevator Pitch',
                'type'       => 'product',
                'purpose'    => 'text_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('magic_ai_prompts')->insert($prompts);

        $imagePrompts = [
            [
                'prompt'     => 'Generate a professional product photo of @name on a clean white background with soft studio lighting, showing the product from the front angle.',
                'title'      => 'White Background Product Photo',
                'type'       => 'product',
                'purpose'    => 'image_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Create a lifestyle product image of @name in a real-world setting, showing how a customer would use it in everyday life.',
                'title'      => 'Lifestyle Product Image',
                'type'       => 'product',
                'purpose'    => 'image_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Generate a close-up detail shot of @name highlighting its texture, material quality, and craftsmanship.',
                'title'      => 'Close-Up Detail Shot',
                'type'       => 'product',
                'purpose'    => 'image_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Create a product image of @name with dimensions and size reference, showing scale comparison for customer clarity.',
                'title'      => 'Product with Size Reference',
                'type'       => 'product',
                'purpose'    => 'image_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Generate a flat lay composition featuring @name arranged neatly with complementary accessories on a minimal background.',
                'title'      => 'Flat Lay Composition',
                'type'       => 'product',
                'purpose'    => 'image_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Create a 360-degree style multi-angle product image of @name showing the front, side, and back views in a single composition.',
                'title'      => 'Multi-Angle Product View',
                'type'       => 'product',
                'purpose'    => 'image_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Generate a hero banner image of @name with a gradient background and bold visual composition, suitable for e-commerce homepage or category page.',
                'title'      => 'Hero Banner Image',
                'type'       => 'product',
                'purpose'    => 'image_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'prompt'     => 'Create a product packaging mockup image of @name showing the product inside its retail packaging with branding visible.',
                'title'      => 'Packaging Mockup',
                'type'       => 'product',
                'purpose'    => 'image_generation',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('magic_ai_prompts')->insert($imagePrompts);
    }
}
