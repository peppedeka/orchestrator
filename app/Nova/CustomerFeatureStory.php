<?php

namespace App\Nova;

use App\Enums\UserRole;
use App\Enums\StoryStatus;
use App\Enums\StoryType;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Query\Search\SearchableRelation;

class CustomerFeatureStory extends Story
{

    public $hideFields = ['updated_at'];

    public static function label()
    {
        return __('Customer Feature stories');
    }


    public static function searchableColumns()
    {
        return [
            'id', 'name', new SearchableRelation('creator', 'name'),
        ];
    }

    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->whereNotNull('creator_id')
            ->whereHas('creator', function ($query) {
                $query->whereJsonContains('roles', UserRole::Customer);
            })
            ->where('status', '!=', StoryStatus::Done->value)
            ->where('type', '=', StoryType::Feature->value);
    }

    public function cards(NovaRequest $request)
    {
        $query = $this->indexQuery($request,  Story::query());
        return [
            new Metrics\StoriesByType('type', 'Type', $query),
            new Metrics\StoriesByUser('creator_id', 'Customer', $query),
            new Metrics\StoriesByUser('user_id', 'Assigned',  $query),
        ];
    }
}
