<?php
namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Brand;

class BrandComponent extends Component
{
    public $brands, $brandId, $name, $slug;
    public $showModal = false;

    public function mount()
    {
        $this->brands = Brand::all();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $brand = Brand::findOrFail($id);
        $this->brandId = $brand->id;
        $this->name = $brand->name;
        $this->slug = $brand->slug;
        $this->showModal = true;
    }

    public function delete($id)
    {
        Brand::find($id)->delete();
        $this->brands = Brand::all();
    }

    public function save()
    {
        Brand::updateOrCreate(
            ['id' => $this->brandId],
            [
                'name' => $this->name,
                'slug' => str_slug($this->name),
            ]
        );

        $this->resetInputFields();
        $this->showModal = false;
        $this->brands = Brand::all();
    }

    private function resetInputFields()
    {
        $this->brandId = null;
        $this->name = '';
        $this->slug = '';
    }

    public function render()
    {
        return view('livewire.brand-component');
    }
}
