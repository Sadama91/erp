<?php
namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Category;

class CategoryComponent extends Component
{
    public $categories, $categoryId, $name, $slug;
    public $showModal = false;

    public function mount()
    {
        $this->categories = Category::all();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->showModal = true;
    }

    public function delete($id)
    {
        Category::find($id)->delete();
        $this->categories = Category::all();
    }

    public function save()
    {
        Category::updateOrCreate(
            ['id' => $this->categoryId],
            [
                'name' => $this->name,
                'slug' => str_slug($this->name),
            ]
        );

        $this->resetInputFields();
        $this->showModal = false;
        $this->categories = Category::all();
    }

    private function resetInputFields()
    {
        $this->categoryId = null;
        $this->name = '';
        $this->slug = '';
    }

    public function render()
    {
        return view('livewire.category-component'); // Dit moet de juiste view zijn
    }
}
