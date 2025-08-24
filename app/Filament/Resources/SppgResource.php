<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SppgResource\Pages;
use App\Filament\Resources\SppgResource\RelationManagers;
use App\Models\Sppg;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;


class SppgResource extends Resource
{
    protected static ?string $model = Sppg::class;

    protected static ?string $navigationIcon  = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'User';


    protected static ?string $navigationLabel = 'SPPG';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')
                ->label('Kode SPPG')
                ->required()
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')
                ->label('Nama')
                ->maxLength(150),

            // API KEY: disabled (read-only), tapi tetap tersimpan saat diganti via Generate
            Forms\Components\TextInput::make('api_key')
                ->label('API Key')
                ->disabled()
                ->dehydrated() // tetap kirim ke server meski disabled
                ->helperText('Bagikan ke aplikasi SPPG terkait. Simpan baik-baik.')
                ->suffixAction(
                    Forms\Components\Actions\Action::make('generateApiKey')
                        ->label('Generate')
                        ->icon('heroicon-o-key')
                        ->action(function ($get, $set) {
                            $set('api_key', Str::random(48));
                        })
                )
                ->required(),

            // (Opsional) HMAC Secret jika nanti mau diaktifkan
            Forms\Components\TextInput::make('hmac_secret')
                ->label('HMAC Secret (opsional)')
                ->password()
                ->revealable()
                ->dehydrateStateUsing(fn($state) => $state ?: null)
                ->suffixAction(
                    Forms\Components\Actions\Action::make('generateHmac')
                        ->label('Generate')
                        ->icon('heroicon-o-shield-check')
                        ->action(function ($get, $set) {
                            $set('hmac_secret', Str::random(64));
                        })
                ),

            Forms\Components\Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),

            Forms\Components\TextInput::make('rate_limit_per_minute')
                ->label('Rate limit / menit')
                ->numeric()
                ->default(60),

            Forms\Components\TextInput::make('timezone')
                ->label('Timezone')
                ->default('Asia/Jakarta'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Kode')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
                Tables\Columns\TextColumn::make('rate_limit_per_minute')->label('Rate/min'),
                Tables\Columns\TextColumn::make('api_key')
                    ->label('API Key')
                    ->formatStateUsing(fn($state) => $state ? substr($state, 0, 6) . '••••' . substr($state, -4) : '-')
                    ->tooltip('Disembunyikan. Gunakan aksi Rotate/Salin untuk melihat.'),
                Tables\Columns\TextColumn::make('created_at')->label('Dibuat')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('showApiKey')
                    ->label('Copy API Key')
                    ->icon('heroicon-o-clipboard')
                    ->color('gray')
                    ->action(function (Sppg $record) {
                        \Filament\Notifications\Notification::make()
                            ->title('API Key')
                            ->body($record->api_key ?: 'Belum ada API key.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSppgs::route('/'),
        ];
    }
}
