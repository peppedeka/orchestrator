<?php

namespace App\Nova;

use App\Enums\EpicStatus;
use Laravel\Nova\Panel;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\BelongsTo;
use App\Nova\Actions\EpicDoneAction;
use App\Nova\Actions\EditEpicsFromIndex;
use Datomatic\NovaMarkdownTui\MarkdownTui;
use App\Nova\Actions\CreateStoriesFromText;
use Laravel\Nova\Http\Requests\NovaRequest;
use Khalin\Nova4SearchableBelongsToFilter\NovaSearchableBelongsToFilter;
use Laravel\Nova\Fields\Status;
use Datomatic\NovaMarkdownTui\Enums\EditorType;
use Eminiarts\Tabs\Tab;
use Eminiarts\Tabs\Tabs;
use Eminiarts\Tabs\Traits\HasTabs;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\MorphToMany;

class Epic extends Resource
{
    use HasTabs;
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
     * The number of resources to show per page via relationships.
     *
     * @var int
     */
    public static $perPageViaRelationship = 50;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'description', 'milestone.name', 'user.name', 'project.name', 'status'
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
            new Tabs('Info', [
                Tab::make('MAIN INFO', [
                    ID::make()->sortable(),
                    //display the relations in nova field
                    BelongsTo::make('User'),
                    BelongsTo::make('Milestone'),
                    BelongsTo::make('Project')->searchable(),
                    Text::make('Name')
                        ->sortable()
                        ->rules('required', 'max:255')
                        ->onlyOnIndex()
                        ->displayUsing(function ($name, $a, $b) {
                            $wrappedName = wordwrap($name, 50, "\n", true);
                            $htmlName = str_replace("\n", '<br>', $wrappedName);
                            return $htmlName;
                        })
                        ->asHtml(),
                    Text::make('Name')
                        ->sortable()
                        ->rules('required', 'max:255')
                        ->hideFromIndex(),

                    Text::make('SAL', function () {
                        return $this->wip();
                    })->hideWhenCreating()->hideWhenUpdating(),
                    Text::make('URL', 'pull_request_link')->nullable()->hideFromIndex()->displayUsing(function () {
                        return '<a class="link-default" target="_blank" href="' . $this->pull_request_link . '">' . $this->pull_request_link . '</a>';
                    })->asHtml(),
                    Select::make('Status', 'status')
                        ->options([
                            'new' => EpicStatus::New,
                            'project' => EpicStatus::Project,
                            'progress' => EpicStatus::Progress,
                            'testing' => EpicStatus::Test,
                            'rejected' => EpicStatus::Rejected,
                            'done' => EpicStatus::Done,
                        ])
                        ->rules('required')
                        ->onlyOnForms()
                        ->default('project'),
                    Status::make('Status')
                        ->loadingWhen(['status' => 'project'])
                        ->failedWhen(['status' => 'rejected'])
                ]),
                Tab::make('Stories', [
                    HasMany::make('Stories'),
                ]),
                Tab::make('Deadlines', [
                    MorphToMany::make('Deadlines'),
                ]),
                Tab::make('Tag projects', [
                    BelongsToMany::make('Tag projects', 'tagProjects', 'App\Nova\Project')->searchable(),
                ]),
            ]),
            new Panel('DESCRIPTION', [
                MarkdownTui::make('Description')
                    ->initialEditType(EditorType::MARKDOWN)
            ]),
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
            new filters\UserFilter,
            new filters\MilestoneFilter,
            new filters\EpicStatusFilter,
            (new NovaSearchableBelongsToFilter('Project'))
                ->fieldAttribute('project')
                ->filterBy('project_id'),
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
                //inlining the action
                ->onlyOnTableRow()
                ->showOnDetail(),
            (new EditEpicsFromIndex)
                ->confirmText('Select status, milestone, project and User to assign to the epics you have selected. Click on the "Confirm" button to save or "Cancel" to delete.')
                ->confirmButtonText('Confirm')
                ->cancelButtonText('Cancel'),
        ];
    }

    public function indexBreadcrumb()
    {
        return null;
    }
}
