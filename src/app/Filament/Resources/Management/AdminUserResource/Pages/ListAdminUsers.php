<?php

namespace App\Filament\Resources\Management\AdminUserResource\Pages;

use App\Filament\Resources\Management\AdminUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdminUsers extends ListRecords
{
    protected static string $resource = AdminUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
