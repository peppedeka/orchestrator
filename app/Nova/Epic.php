<?php

namespace App\Nova;

use App\Enums\StoryStatus;
use Laravel\Nova\Fields\ID;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsTo;
use App\Nova\Actions\EpicDoneAction;
use App\Nova\Actions\EditEpicsFromIndex;
use App\Nova\Actions\CreateStoriesFromText;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Markdown as FieldsMarkdown;

class Epic extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Epic>
     */
    public static $model = \App\Models\Epic::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'description', 'milestone.name', 'user.name', 'project.name' // <-- searchable by project name
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Milestone'),
            BelongsTo::make('Project')->searchable(),
            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Markdown::make('Description')
                ->hideFromIndex()->alwaysShow(),


            // Text::make('URL', 'pull_request_link', function () {
            //     return '<a href="' . $this->pull_request_link . '">Link</a>';
            // })->asHtml()->nullable()->hideFromIndex(),
            Text::make('URL', 'pull_request_link')->nullable()->hideFromIndex()->displayUsing(function () {
                return '<a class="link-default" target="_blank" href="' . $this->pull_request_link . '">' . $this->pull_request_link . '</a>';
            })->asHtml(),

            //display the relations in nova field
            BelongsTo::make('User'),

            Text::make('SAL', function () {
                return $this->wip();
            })->hideWhenCreating()->hideWhenUpdating(),

            Text::make('Status')->hideWhenCreating()->hideWhenUpdating(),

            HasMany::make('Stories'),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
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
            new filters\MilestoneFilter,
            new filters\ProjectFilter,
            new filters\EpicStatusFilter,
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [
            new Lenses\MyEpicLens,
        ];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [
            (new CreateStoriesFromText)
                ->onlyOnDetail(),
            (new EpicDoneAction)
                ->onlyOnDetail(),
            (new EditEpicsFromIndex)
                ->confirmText('Seleziona stato, milestone, project e utente da assegnare alle epiche che hai selezionato. Clicca sul tasto "Conferma" per salvare o "Annulla" per annullare.')
                ->confirmButtonText('Conferma')
                ->cancelButtonText('Annulla'),
        ];
    }
}