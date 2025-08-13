<?php

namespace App\Livewire;

use Filament\Schemas\Schema;
use App\Enums\Permission;
use Filament\Forms\Components\Select;
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

    public ?string $filepath = null;
    public ?string $file = null;
    public string $text = '';

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
        'html',
        'htm',
        'log',
        'txt',
        'json',
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
                    ->required(),
            ]);
    }

    public function getFile()
    {
        $this->form->getState();
        $files = $this->fileList();
        $file = $files[$this->file];
        $path = pathinfo($file);
        if (str_ends_with($path['filename'], '.blade') && $path['extension'] == 'php') {
            $mode = 'php_laravel_blade';
        } else {
            switch ($path['extension']) {
                case 'js':
                    $mode = 'javascript';
                    break;
                case 'htm':
                    $mode = 'html';
                    break;
                case 'txt':
                case 'log':
                    $mode = 'text';
                    break;
                default:
                    $mode = $path['extension'];
            }
        }
        if (file_exists(base_path($file)) &&
            $this->fileInWhitelist() === true &&
            Auth::user()->can(Permission::EditFiles)
        ) {
            $contents = file_get_contents(base_path($file));
            $this->dispatch('file-loaded', contents: $contents, mode: $mode);
        } else {
            $this->dispatch('file-loaded', contents: '', mode: 'text');
        }
    }

    protected function fileInWhitelist(): bool
    {
        $files = $this->fileList();
        $file = $files[$this->file];
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
        $files = $this->fileList();
        $file = $files[$this->file];
        if (file_exists(base_path($file)) &&
            $this->fileInWhitelist() === true &&
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
                $files[] = str_replace(base_path(), '', $file);
            }
        }
        sort($files);
        return $files;
    }
    #[Layout('components.layout.base')]
    public function render()
    {
        return view('livewire.file-editor');
    }
}
