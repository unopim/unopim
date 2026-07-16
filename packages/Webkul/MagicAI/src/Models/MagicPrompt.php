<?php

namespace Webkul\MagicAI\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MagicAI\Contracts\MagicPrompt as MagicPromptContract;
use Webkul\MagicAI\Database\Factories\MagicPromptFactory;

class MagicPrompt extends Model implements HistoryAuditable, MagicPromptContract
{
    use HasFactory;
    use HistoryTrait;

    protected $table = 'magic_ai_prompts';

    /**
     * @var array<int, string>
     */
    protected array $historyTags = ['magicPrompt'];

    protected $fillable = [
        'prompt',
        'title',
        'type',
        'purpose',
        'tone',
    ];

    protected static function newFactory(): Factory
    {
        return MagicPromptFactory::new();
    }
}
