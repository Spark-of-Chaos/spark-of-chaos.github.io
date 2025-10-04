<?php

namespace App\Fields;

use Backstage\Fields\Fields\Base;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs\Tab;

class UploadField extends Base
{

    public static function make(string $name, ?Field $field = null): FileUpload
    {
        $input = self::applyDefaultSettings(FileUpload::make($name), $field);

        $input = $input->label($field->name ?? null);

        if ($field->config['isImage'] ?? false) {
            $input = $input->image();
        }
        if ($field->config['isImage'] ?? false && $field->config['imageEditor'] ?? false) {
            $input = $input->imageEditor();
        }
        if ($field->config['isImage'] ?? false && $field->config['imageEditor'] ?? false && $field->config['imageEditorAspectRatios'] ?? false) {
            $input = $input->imageEditorAspectRatios(explode(',', $field->config['imageEditorAspectRatios']));
        }
        if ($field->config['preserveFilenames'] ?? false) {
            $input = $input->preserveFilenames();
        }

        $input = $input->multiple($field->config['multiple'] ?? self::getDefaultConfig()['multiple']);

        return $input;
    }

    public function getForm(): array
    {
        return [
            Tabs::make()
                ->schema([
                    Tab::make('General')
                        ->label(__('General'))
                        ->schema([
                            ...parent::getForm(),
                        ]),
                    Tab::make('Field specific')
                        ->label(__('Field specific'))
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Toggle::make('config.multiple')
                                        ->label(__('Multiple'))
                                        ->columnSpan(2)
                                        ->inline(false),
                                    Toggle::make('config.isImage')
                                        ->label(__('Is image?'))
                                        ->columnSpan(2)
                                        ->inline(false),
                                    Toggle::make('config.imageEditor')
                                        ->label(__('Enable image editor?'))
                                        ->columnSpan(2)
                                        ->inline(false),
                                    Toggle::make('config.preserveFilenames')
                                        ->label(__('Preserve filenames?'))
                                        ->columnSpan(2)
                                        ->inline(false),
                                    TextInput::make('config.imageEditorAspectRatios')
                                        ->label(__('Image editor aspect ratios (comma separated)'))
                                        ->columnSpan(3)
                                        ->helperText(__('E.g. 1:1, 4:3, 16:9'))
                                ])->columnSpanFull(),
                        ]),
                ])->columnSpanFull(),
        ];
    }

    
    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'multiple' => false,
            'isImage' => false,
            'imageEditor' => false,
            'imageEditorAspectRatios' => null,
            'preserveFilenames' => false,

        ];
    }
}