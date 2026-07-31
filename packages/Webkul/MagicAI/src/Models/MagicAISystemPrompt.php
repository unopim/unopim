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

    /**
     * @var array<int, string>
     */
    protected array $historyTags = ['magicSystemPrompt'];

    protected static function newFactory(): Factory
    {
        return MagicAISystemPromptFactory::new();
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
