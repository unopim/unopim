<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Contracts\PimTool;

/**
 * Captures user feedback on AI-generated content quality.
 *
 * Stores ratings as memories so the agent can learn preferred
 * content styles and avoid patterns the user dislikes.
 */
class RateContent implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('rate_content')
            ->for('Record user feedback on AI-generated content quality. Use this when the user says content was good, bad, too long, too short, wrong tone, etc. This helps improve future content generation.')
            ->withStringParameter('sku', 'Product SKU the content was generated for')
            ->withEnumParameter('rating', 'Overall rating', ['positive', 'negative', 'mixed'])
            ->withStringParameter('feedback', 'Specific feedback (e.g. "too formal", "missing key features", "perfect length and tone")')
            ->withStringParameter('content_type', 'What type of content was rated (e.g. "description", "title", "meta_description")')
            ->using(function (
                ?string $sku = null,
                string $rating = 'positive',
                ?string $feedback = null,
                string $content_type = 'description',
            ) use ($context): string {
                if (! $feedback) {
                    return json_encode(['error' => 'Please provide specific feedback about what was good or bad']);
                }

                // Store rating as a content_feedback memory
                $key = "content_feedback:{$content_type}:{$rating}";
                $value = $feedback;
                if ($sku) {
                    $value = "[SKU:{$sku}] {$feedback}";
                }

                DB::table('ai_agent_memories')->insert([
                    'scope'      => 'catalog',
                    'key'        => $key,
                    'user_id'    => $context->user?->id,
                    'value'      => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Also store aggregate preference if positive
                if ($rating === 'positive' && $feedback) {
                    $existing = DB::table('ai_agent_memories')
                        ->where('scope', 'catalog')
                        ->where('key', 'content_style_preference')
                        ->where('user_id', $context->user?->id)
                        ->first();

                    $styleHints = $existing ? $existing->value.'; '.$feedback : $feedback;

                    if ($existing) {
                        // Keep only last 500 chars of preferences
                        $styleHints = mb_substr($styleHints, -500);
                        DB::table('ai_agent_memories')->where('id', $existing->id)->update([
                            'value'      => $styleHints,
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('ai_agent_memories')->insert([
                            'scope'      => 'catalog',
                            'key'        => 'content_style_preference',
                            'user_id'    => $context->user?->id,
                            'value'      => $styleHints,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                return json_encode([
                    'result' => [
                        'recorded'     => true,
                        'rating'       => $rating,
                        'content_type' => $content_type,
                        'message'      => 'Feedback recorded. Future content generation will take this into account.',
                    ],
                ]);
            });
    }
}
