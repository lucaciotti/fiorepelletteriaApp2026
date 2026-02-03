<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\ProductProcessType;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Fieldset::make('')
                ->schema([
                    TextInput::make('code')->label('Codice')
                        ->required(),
                    TextInput::make('description')->label('Descrizione')
                        ->required(),
                ]),
                Section::make('Lavorazioni Collegate')->collapsible()->columns(1)->hiddenOn('create')
                    ->afterHeader([
                        // Action::make('test'),
                        ReplicateAction::make('cloneProcessTypes')->label('Clona Lavorazioni')
                            // ->hidden(fn($record) => $record==null)
                            ->schema([
                                Select::make('product_id')->label('Prodotto da cui clonare')
                                    ->options(Product::query()->pluck('code', 'id'))
                                    ->searchable()
                                    ->live(onBlur: true)
                                    ->required(),
                            ])
                            ->action(function (array $data, Product $record): void {
                                $productProcessTypes = Product::find($data['product_id'])->productProcessTypes->all();
                                $record->productProcessTypes()->delete();
                                foreach ($productProcessTypes as $productProcessType) {
                                    $newRecord = Arr::except($productProcessType->toArray(), ['created_at', 'updated_at', 'id']);
                                    $newRecord['product_id'] = $record->id;
                                    // dd($newRecord);
                                    $record->productProcessTypes()->create($newRecord);
                                    // $record->author()->associate($data['authorId']);
                                    $record->save();
                                }                                
                            })
                            ->successRedirectUrl(fn($record) => ProductResource::getUrl('edit', [$record->id])),
                    ])
                    ->schema([
                        Repeater::make('productProcessTypes')->label('Lavorazioni')->hiddenLabel(true)->relationship()
                            ->table([
                                TableColumn::make('#')->width('30px'),
                                TableColumn::make('Lavorazione'),
                            ])
                            ->compact()
                            ->schema([
                                TextInput::make('position')->readOnly(true)->live(),
                                Select::make('process_type_id')
                                    ->label('Lavorazione')
                                    ->relationship('processType', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                    // ->createOptionForm([
                                    //     TextInput::make('code')
                                    //         ->label('Codice Prodotto')
                                    //         ->required()
                                    //         ->maxLength(255),
                                    //     TextInput::make('description')
                                    //         ->label('Descrizione')
                                    //         ->maxLength(255),
                                    // ]),
                            ])->orderColumn('position'),
                    ])
            ]);
    }
}
