<?php

namespace Webkul\MagicAI\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MagicAI\Contracts\MagicAISystemPrompt as MagicAISystemPromptContract;
use Webkul\MagicAI\Database\Factories\MagicAISystemPromptFactory;

#[Fillable([
    'title',
    'tone',
    'max_tokens',
    'temperature',
    'is_enabled',
])]
#[Table(name: 'magic_ai_system_prompts')]
class MagicAISystemPrompt extends Model implements HistoryAuditable, MagicAISystemPromptContract
{
    use HasFactory;
    use HistoryTrait;

    public const MAX_TOKENS_LOWER_BOUND = 100;

    public const MAX_TOKENS_UPPER_BOUND = 5000;

    /**
     * @var array<int, string>
     */
    protected array $historyTags = ['magicSystemPrompt'];

    /**
     * Get the validation rule bounding max_tokens, shared by the server and the admin forms.
     */
    public static function maxTokensRangeRule(): string
    {
        return 'between:'.self::MAX_TOKENS_LOWER_BOUND.','.self::MAX_TOKENS_UPPER_BOUND;
    }

    protected static function newFactory(): Factory
    {
        return MagicAISystemPromptFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    protected static function booted()
    {
        static::saving(function ($model): void {
            if ($model->is_enabled) {
                static::where('id', '!=', $model->id)->update(['is_enabled' => false]);
            }
        });
    }
}
