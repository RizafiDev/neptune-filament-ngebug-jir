<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReleaseResource\Pages;
use App\Filament\Resources\ReleaseResource\RelationManagers;
use App\Models\Release;
use Filament\Forms;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReleaseResource extends Resource
{
    protected static ?string $model = Release::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')->label('Release Tittle')->required()->maxLength(255),
                Forms\Components\TextInput::make('upc')->label('UPC')->maxLength(255)->nullable(),
                Forms\Components\TextInput::make('artist_name')->label('Artist')->required() ->maxLength(255),
                Forms\Components\TextInput::make('name')->label('Full Name')->required() ->maxLength(255),
                Forms\Components\TextInput::make('featuring')->label('Artist Featuring') ->maxLength(255),
                Forms\Components\FileUpload::make('image_file_path')->label('Artwork')->required()->preserveFilenames(),
                Forms\Components\FileUpload::make('file_path')->label('Music')->required()->preserveFilenames(),
                Forms\Components\Select::make('type')->label('Type')->options(Release::TYPE)->required(),
                Forms\Components\Select::make('explicit')->label('Explicit')->options(Release::EXPLICIT)->required(),
                Forms\Components\TextInput::make('email')->required()->maxLength(255)->email(),
            ]);
    }   

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                BadgeColumn::make('status')
                ->getStateUsing(function ($record) {
                    // Menyesuaikan status berdasarkan nilai di database
                    return match ($record->status) {
                        'review' => 'Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        default => 'Review',
                    };
                })
                ->colors([
                    'warning' => 'Review',
                    'success' => 'Approved',
                    'danger' => 'Rejected',
                ]),
                Tables\Columns\TextColumn::make('upc')->label('UPC'),
                Tables\Columns\TextColumn::make('title')->label('Release Tittle'),
                Tables\Columns\TextColumn::make('artist_name')->label('Artist'),
                Tables\Columns\TextColumn::make('name')->label('Full Name'),
                Tables\Columns\TextColumn::make('featuring')->label('Artist Featuring'),
                Tables\Columns\TextColumn::make('image_file_path')
    ->formatStateUsing(fn ($state) => $state 
        ? '<img src="' . url('storage/' . $state) . '" alt="Artwork" style="max-width: 150px; max-height: 150px; object-fit: cover;">'
        : 'No image')
    ->html()
    ->label('Artwork'),

                Tables\Columns\TextColumn::make('type')->label('Type'),
                Tables\Columns\TextColumn::make('explicit')->label('Explicit'),
                Tables\Columns\TextColumn::make('file_path')
                ->formatStateUsing(fn ($state) => $state 
                    ? '<audio controls><source src="' . url('storage/' . $state) . '" type="audio/mpeg">Your browser does not support the audio element.</audio>'
                    : 'No file')
                ->html()
                ->label('Music'),
            
                
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('created_at')
                ->label('Create At'),

            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('approve')
                ->label('Approve')
                ->action(fn ($record) => $record->update(['status' => 'approved']))
                ->requiresConfirmation()
                ->color('success')
                ->visible(fn ($record) => $record->status !== 'approved')
                ->visible(fn () => in_array(auth()->user()->role, [User::ROLE_ADMIN, User::ROLE_EDITOR])),

            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->action(fn ($record) => $record->update(['status' => 'rejected']))
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status !== 'rejected')
                ->visible(fn () => in_array(auth()->user()->role, [User::ROLE_ADMIN, User::ROLE_EDITOR])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => in_array(auth()->user()->role, [User::ROLE_ADMIN, User::ROLE_EDITOR])),
                ])
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReleases::route('/'),
            'create' => Pages\CreateRelease::route('/create'),
            'edit' => Pages\EditRelease::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()->role, [User::ROLE_ARTIST, User::ROLE_ADMIN, User::ROLE_EDITOR]);

    }

    public static function canView($record): bool
    {
        return in_array(auth()->user()->role, [User::ROLE_ARTIST, User::ROLE_ADMIN, User::ROLE_EDITOR]);

    }

}
