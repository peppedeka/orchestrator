<?php

namespace App\Nova;


use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\KeyValue;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Quote extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Quote>
     */
    public static $model = \App\Models\Quote::class;

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
        'id', 'title'
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
            Text::make('title'),
            Text::make('Google Drive Url', 'google_drive_url')->nullable()->hideFromIndex()->displayUsing(function () {
                return '<a class="link-default" target="_blank" href="' . $this->google_drive_url . '">' . $this->google_drive_url . '</a>';
            })->asHtml(),
            BelongsTo::make('Customer'),
            BelongsToMany::make('Products')->fields(function () {
                return [
                    Number::make('Quantity', 'quantity')->rules('required', 'numeric', 'min:1'),
                ];
            }),
            BelongsToMany::make('Recurring Products')->fields(function () {
                return [
                    Number::make('Quantity', 'quantity')->rules('required', 'numeric', 'min:1'),
                ];
            }),
            Currency::make('Total products price')
                ->currency('EUR')
                ->locale('it')
                ->exceptOnForms()
                ->displayUsing(function () {
                    $price = empty($this->products) ? 0 : $this->getTotalPrice();
                    return number_format($price, 2, ',', '.') . ' €';
                })->sortable(),
            Currency::make('Total recurring products price')
                ->currency('EUR')
                ->locale('it')
                ->exceptOnForms()
                ->displayUsing(function () {
                    $price = empty($this->recurringProducts) ? 0 : $this->getTotalRecurringPrice();
                    return number_format($price, 2, ',', '.') . ' €';
                })->sortable(),
            Currency::make('Total quote price')
                ->currency('EUR')
                ->locale('it')
                ->exceptOnForms()
                ->displayUsing(function () {
                    $quotePrice = $this->getTotalPrice() + $this->getTotalRecurringPrice();
                    return number_format($quotePrice, 2, ',', '.') . ' €';
                })->sortable(),
            Currency::make('Discount')
                ->currency('EUR')
                ->locale('it')
                ->hideFromIndex()
                ->displayUsing(function () {
                    return number_format($this->discount, 2, ',', '.') . ' €';
                }),
            KeyValue::make('Additional Services', 'additional_services')
                ->hideFromIndex()
                ->rules('json')
                ->keyLabel('Description')
                ->valueLabel('Price (€)'),
            Currency::make('Additional Services Total Price')
                ->currency('EUR')
                ->locale('it')
                ->onlyonDetail()
                ->displayUsing(function () {
                    return number_format($this->getTotalAdditionalServicesPrice(), 2, ',', '.') . ' €';
                }),

            Currency::make('IVA')
                ->currency('EUR')
                ->locale('it')
                ->onlyonDetail()
                ->displayUsing(function () {
                    $iva = $this->getQuoteNetPrice() * 0.22;
                    return number_format($iva, 2, ',', '.') . ' €';
                }),

            Currency::make('Final Price')
                ->currency('EUR')
                ->locale('it')
                ->onlyonDetail()
                ->displayUsing(function () {
                    $iva = $this->getQuoteNetPrice() * 0.22;
                    return number_format($this->getQuoteNetPrice() + $iva, 2, ',', '.') . ' €';
                }),
            Text::make('Link')
                ->resolveUsing(function ($value, $resource, $attribute) {
                    return '<a class="link-default" href="' . route('quote', ['id' => $resource->id]) . '">View Quote</a>';
                })
                ->asHtml()
                ->exceptOnForms()
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
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }
}
