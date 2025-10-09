<?php

namespace App\Livewire;

use Filament\Schemas\Schema;
use App\Enums\Permission;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

/**
 * @property \Filament\Schemas\Schema $form
 */
class FileEditor extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?string $file = null;
    public ?string $text = '';

    protected array $dir_whitelist = [
        '/config',
        '/app',
        '/resources',
        '/database',
        '/routes',
        '/lang',
        '/tests',
    ];

    protected array $ext_whitelist = [
        'php',
        'js',
        'css',
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('file')
                    ->options($this->fileList())
                    ->searchable()
                    ->live()
                    ->required(),
                CodeEditor::make('text')
                    ->language(fn (Get $get) => match(pathinfo(str_replace('-sep-', '/', $get('file')), PATHINFO_EXTENSION)) {
                        'php' => Language::Php,
                        'js' => Language::JavaScript,
                        'htm' => Language::Html,
                        default => null,
                    })
                    ->live(),
            ]);
    }

    public function getFile()
    {
        $file = str_replace('-sep-', '/', $this->file);
        if (file_exists(base_path($file)) &&
            $this->fileInWhitelist($file) === true &&
            Auth::user()->can(Permission::EditFiles)
        ) {
            $this->text = file_get_contents(base_path($file));
        } else {
            $this->text = '';
        }
    }

    protected function fileInWhitelist(string $file): bool
    {
        $path = pathinfo($file);
        foreach ($this->dir_whitelist as $dir) {
            if (str_starts_with($path['dirname'], $dir) && in_array($path['extension'], $this->ext_whitelist)) {
                return true;
            }
        }
        return false;
    }
    public function saveFile(string $contents)
    {
        $file = str_replace('-sep-', '/', $this->file);
        if (file_exists(base_path($file)) &&
            $this->fileInWhitelist($file) === true &&
            Auth::user()->can(Permission::EditFiles)
        ) {
            file_put_contents(base_path($file), $contents);
        }
    }

    public function fileList(): array
    {
        $files = [];
        foreach ($this->dir_whitelist as $dir) {
            $file_dir = new RecursiveDirectoryIterator(base_path($dir));
            $iterator = new RecursiveIteratorIterator($file_dir);
            $file_list = new RegexIterator($iterator, '/^.+\.('. implode('|', $this->ext_whitelist). ')$/i', RecursiveRegexIterator::GET_MATCH);
            foreach ($file_list as $file => $results) {
                $f = str_replace(base_path(), '', $file);
                $files[str_replace('/', '-sep-', $f)] = $f;
            }
        }
        $files = collect($files)->sortKeys()->all();
        return $files;
    }
    #[Layout('components.layout.base')]
    public function render()
    {
        return view('livewire.file-editor');
    }
}
