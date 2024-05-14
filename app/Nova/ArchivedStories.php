<?php

namespace App\Nova;

use App\Enums\StoryStatus;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class ArchivedStories extends Story
{
    public static function label()
    {
        return __('Archived stories');
    }
    public static function uriKey()
    {
        return 'archived-deadlines';
    }
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query
            ->where('status', StoryStatus::Done);
    }

    public static function authorizedToCreate(Request $request)
    {
        return false;
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [
            new filters\CreatorStoryFilter(),
            new filters\UserFilter(),
            new filters\StoryTypeFilter(),
            new filters\CustomerStoryWithDeadlineFilter(),
        ];
    }

    public function cards(NovaRequest $request)
    {
        $query = $this->indexQuery($request,  Story::query());
        return [
            (new Metrics\StoriesByField('type', 'Type', $query))->width('1/3'),
            (new Metrics\StoriesByUser('creator_id', 'Customer', $query))->width('1/3'),
            (new Metrics\StoriesByUser('user_id', 'Assigned',  $query))->width('1/3'),
        ];
    }
}
